<?php

namespace Cache;

const ONE_DAY = 86400;
const ONE_WEEK = 604800;

require_once __DIR__ . '/ICacheBackend.php';
require_once __DIR__ . '/Backend/File.php';

function write($file, $content = '', $flags = 0){
	if (!file_exists($file)){
		$directory = dirname($file);
		if (!is_dir($directory)){
			$mask = umask(0);
			mkdir($directory, 0777, true);
			umask($mask);
		}
		
		touch($file);
		chmod($file, 0777);
	}
	
	file_put_contents($file, $content, $flags);
}

function unlink($file){
	$file = realpath($file);
	if (is_dir($file)){
		foreach ((glob($file . '/*') ?: array()) as $f)
			unlink($f);

		rmdir($file);
		return;
	}
	
	if (file_exists($file))
		\unlink($file);
}

class Cache {

	protected $options = array(
		'default' => null,
		'persistent' => 'file',
		'prefix' => '',
		'servers' => array() // For memcached
	);

	protected $storage = array();
	protected $engines = array();
	protected $time = null;
	
	protected static $availableEngines = array();
	
	public function __construct($root, $options = array()){
		$this->options = array_merge($this->options, (array)$options);
		$this->options['root'] = realpath($root);
		$this->time = time();
		
		$this->loadEngines();
		
		if (empty($this->engines[$this->options['default']]))
			$this->options['default'] = key($this->engines);
	}
	
	// Engines
	public function loadEngines(){
		foreach (static::$availableEngines as $engine){
			$class = 'Cache\\Backend\\' . $engine;
			$name = strtolower($engine);
			
			if (empty($this->engines[$name]))
				$this->engines[$name] = new $class($this->options);
		}
	}
	
	public function getEngine($name = null){
		$name = strtolower($name);
		if (empty($this->engines[$name]))
			$name = $this->options['default'];
		
		return $this->engines[$name];
	}
	
	// I/O
	public function retrieve($key, $callback = null, $engine = null){
		if ($callback && is_string($callback)){
			$engine = $callback;
			$callback = null;
		}
		
		if (!empty($this->storage[$key])) return $this->storage[$key];

		$content = $this->getEngine($engine)->retrieve($key);
		if ($content) $content = unserialize($content);
		else if ($callback && $callback instanceof \Closure)
			$content = $callback($this, $key);
		
		$this->storage[$key] = $content;
		return $content ?: null;
	}
	
	public function store($key, $input, $options = null){
		$options = array_merge(array(
			'engine' => null,
			'tags' => null,
			'ttl' => 3600
		), is_numeric($options) ? array('ttl' => $options) : (array)$options);
		
		if (!$options['ttl'])
			$options['engine'] = $this->options['persistent'];
		
		if (!empty($options['tags']))
			$this->addKeyToTags($key, (array)$options['tags']);
		
		$this->getEngine($options['engine'])->store($key, serialize($input), $options['ttl']);
		
		return ($this->storage[$key] = $input);
	}
	
	// Erase
	public function erase($keys){
		$keys = (array)$keys;
		foreach ($keys as $key)
			unset($this->storage[$key]);

		foreach ($this->engines as $engine)
			$engine->erase($keys);
		
		return $this;
	}

	public function eraseByTag($tag){
		$keys = $this->getKeysByTag($tag);
		if (count($keys)) $this->erase($keys);

		return $this;
	}

	public function eraseByTags($tags){
		$keys = null;
		foreach($tags as $tag){
			$list = $this->getKeysByTag($tag);
			$keys = empty($keys) ? $list : array_intersect($keys, $list);
		}

		return $this->erase($keys);
	}
	
	public function eraseAll(){
		unlink($this->getTagFolder());

		foreach ($this->engines as $engine)
			$engine->eraseAll();
		
		return $this->flush();
	}

	public function flush(){
		$this->storage = array();
		return $this;
	}
	
	// Tags
	protected function getTagFolder(){
		return $this->options['root'] . '/' . $this->options['prefix'] . '/Tags/';
	}

	protected function getKeysByTag($tag){
		$file = $this->getTagFolder() . $tag;
		return file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES) : array();
	}

	protected function addKeyToTag($key, $tag){
		$file = $this->getTagFolder() . $tag;
		write($file, $key . "\n", FILE_APPEND);
	}
	
	protected function addKeyToTags($key, $tags){
		foreach ($tags as $tag)
			$this->addKeyToTag($key, $tag);
	}
	
	// Static
	public static function register($engine){
		static::$availableEngines[] = $engine;
	}

	public static function getAvailableEngines(){
		return static::$availableEngines;
	}
	
}
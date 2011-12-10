<?php

namespace Cache\Backend;

class Memcached extends \Cache\ICacheBackend {
	
	protected $server;
	
	public function __construct($options){
		$this->prefix = rtrim($options['prefix'], '/') . '/';
		$this->server = new Memcached;
		
		if (empty($options['servers']))
			$options['servers'] = array(array());
		
		foreach ($options['servers'] as $server){
			$data = array_merge(array(
				'host' => 'localhost',
				'port' => 11211,
				'weight' => 1,
			), $server);
			
			$this->server->addServer(
				$data['host'], $data['port'], $data['weight']
			);
		}
	}
	
	public function retrieve($key){
		return $this->server->get($this->prefix . $key);
	}
	
	public function store($key, $content, $ttl = null){
		$this->server->set($this->prefix . $key, $content, $ttl);
	}
	
	public function erase($keys){
		foreach ($keys as $key)
			$this->server->delete($this->prefix . $key);
	}

	public function eraseAll(){
		$this->server->flush();
	}
	
}

if (class_exists('Memcached', false))
	\Cache\Cache::register('Memcached');

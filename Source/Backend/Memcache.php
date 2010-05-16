<?php

namespace Cache\Backend;

class Memcache extends \Cache\ICacheBackend {
	
	protected $server;
	
	public function __construct($options){
		$this->prefix = rtrim($options['prefix'], '/') . '/';
		$this->server = new Memcache;
		
		if (empty($options['servers']))
			$options['servers'] = array(array());
		
		foreach ($options['servers'] as $server){
			$data = array_merge(array(
				'host' => 'localhost',
				'port' => 11211,
				'persistent' => true,
				'weight' => 1,
				'timeout' => 15,
				'retryInterval' => 15,
				'status' => true,
				'callback' => null
			), $server);
			
			$this->server->addServer(
				$data['host'], $data['port'], $data['persistent'],
				$data['weight'], $data['timeout'], $data['retryInterval'],
				$data['status'], $data['callback']
			);
		}
	}
	
	public function retrieve($key){
		return $this->server->get($this->prefix . $key);
	}
	
	public function store($key, $content, $ttl = null){
		$this->server->set($this->prefix . $key, $content, false, $ttl);
	}
	
	public function erase($keys){
		foreach ($keys as $key)
			$this->server->delete($this->prefix . $key);
	}

	public function eraseAll(){
		$this->server->flush();
	}
	
}

if (class_exists('Memcache', false))
	\Cache\Cache::register('Memcache');
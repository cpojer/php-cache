<?php

namespace Cache\Backend;

class APC extends \Cache\ICacheBackend {
	
	public function __construct($options){
		$this->prefix = rtrim($options['prefix'], '/') . '/';
	}
	
	public function retrieve($key){
		return apc_fetch($this->prefix . $key);
	}
	
	public function store($key, $content, $ttl = null){
		apc_store($this->prefix . $key, $content, $ttl);
	}
	
	public function erase($keys){
		foreach ($keys as $key)
			apc_delete($this->prefix . $key);
	}

	public function eraseAll(){
		apc_clear_cache();
	}
	
}

if (function_exists('apc_fetch'))
	\Cache\Cache::register('APC');
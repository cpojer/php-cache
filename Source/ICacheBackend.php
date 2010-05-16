<?php

namespace Cache;

abstract class ICacheBackend {

	protected $prefix;
	
	abstract public function __construct($options);
	abstract public function retrieve($id);
	abstract public function store($id, $content, $ttl = null);
	abstract public function erase($list);
	abstract public function eraseAll();

}
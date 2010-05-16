<?php

namespace Tests\Cache;

require_once __DIR__ . '/../Source/Backend/APC.php';
require_once __DIR__ . '/../Source/Backend/Memcache.php';

use Cache\Cache;

class CacheArrayTest implements \Serializable {
	
	protected $data;
	
	public function __construct($data){
		$this->data = $data;
	}
	
	public function serialize(){
		return serialize($this->data);
	}

	public function unserialize($serialized){
		$this->data = unserialize($serialized);
	}
	
}

class CacheTest extends \PHPUnit_Framework_TestCase {
	
	public function setUp(){
		$this->cache = new Cache(__DIR__ . '/FileCacheTest/', array(
			'prefix' => 'Tests'
		));
	}
	
	public function tearDown(){
		$this->cache->eraseAll();
	}
	
	public function testCache(){
		$engines = Cache::getAvailableEngines();
		
		$this->assertTrue(in_array('File', $engines));
		
		if (class_exists('Memcache', false))
			$this->assertTrue(in_array('Memcache', $engines));
		
		if (function_exists('apc_fetch'))
			$this->assertTrue(in_array('APC'));
		
		$this->assertTrue($this->cache->getEngine('file') instanceof \Cache\Backend\File);
	}
	
	public function testStore(){
		$c = $this->cache;
		$key = 'key';
		$data = 'value';
		
		$c->store($key, $data);
		
		$this->assertEquals($c->retrieve($key), $data);
		
		$c->flush(); // Force Backend reload
		$this->assertEquals($c->retrieve($key), $data);
		
		$array = array(
			array('id' => 1, 'title' => 'C'),
			array('id' => 2, 'title' => 'D', 'test' => 2)
		);
		
		$c->store($key, $array);
		$this->assertEquals($c->flush()->retrieve($key), $array);
		$c->erase($key);
		$this->assertNull($c->flush()->retrieve($key));
		
		$c->store($key, $array, array('engine' => 'file'));
		$this->assertEquals($c->flush()->retrieve($key, 'file'), $array);
	}
	
	public function testStoreCallback(){
		$c = $this->cache;
		$key = 'key';
		
		$this->assertEquals($c->retrieve($key, function($c, $key){
			return $c->store($key, 'data');
		}), 'data');
		
		$this->assertEquals($c->flush()->retrieve($key, function($c, $key){
			return $c->store($key, 'thisDoesNotGetExecuted');
		}), 'data');
	}
	
	public function testClassStore(){
		$c = $this->cache;
		$key = 'key';
		
		$cacheArray = new CacheArrayTest(array(1, 2, 3));
		$c->store($key, $cacheArray);
		$cached = $c->flush()->retrieve($key);
		$this->assertEquals($cached, $cacheArray);
		$this->assertNotSame($cached, $cacheArray);
		$this->assertTrue($cached instanceof CacheArrayTest);
	}
	
	public function testErase(){
		$c = $this->cache;
		$c->store('key', 'value');
		$c->store('a', 'b');
		$c->store('c', 'd');
		$c->erase('key');
		
		$this->assertNull($c->retrieve('key'));
		$this->assertEquals($c->retrieve('a'), 'b');
		
		$c->erase(array('a', 'c'));
		$this->assertNull($c->retrieve('a'));
		$this->assertNull($c->retrieve('c'));
	}
	
	public function testEraseAll(){
		$c = $this->cache;
		$c->store('key', 'value');
		$c->eraseAll();
		
		$this->assertNull($c->retrieve('key'));
	}
	
	public function testTags(){
		$c = $this->cache;
		
		$key = 'test';
		$data = file_get_contents(__FILE__);
		$c->store($key, $data, array('tags' => 'tag'));
		$this->assertEquals($c->flush()->retrieve($key), $data);
		$c->eraseByTag('tag');
		$this->assertNull($c->flush()->retrieve($key));
		
		$c->store($key, $data, array('tags' => array('random', 'tag')));
		$this->assertEquals($c->flush()->retrieve($key), $data);
		$c->eraseByTag('random');
		$this->assertNull($c->flush()->retrieve($key));

		$c->store($key, $data, array('tags' => array('random', 'test', 'tag')));
		$this->assertEquals($c->flush()->retrieve($key), $data);
		$c->eraseByTags(array('random', 'tag'));
		$this->assertNull($c->flush()->retrieve($key));

		$c->store($key, $data, array('tags' => array('random', 'test', 'tag')));
		$this->assertEquals($c->flush()->retrieve($key), $data);
		$c->eraseByTags(array('random', 'doNotMatchMe'));
		$this->assertEquals($c->flush()->retrieve($key), $data);
	}

}
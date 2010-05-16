PHP-Cache (PHP 5.3)
===================

Abstract base library to cache any data to any backend. Uses file system as a fall back and for 'persistent' data.

### Specs

* Run the "run" script in the Specs directory

### Example
	
	use Cache\Cache;
	
	$c = new Cache('/path/to/file/cache', array(
		'prefix' => 'MyAppPrefix'
	));
	
	$c->store('anyKey', $anyData);
	$c->retrieve('anyKey'); // returns $anyData !
	$c->erase('anyKey');
	
### Actual Use-Case Example

	$data = $c->retrieve('data');
	if (!$data){
		$data = doSomeExpensiveCalculation();
		$c->store('data', $data);
	}
	
	$data; // Has expensive data!
	
### Or, with PHP 5.3 Style

	$data = $c->retrieve('myKey', function($c, $key){
		return $c->store($key, doSomeExpensiveCalculation());
	});

The above function is only being executed if there is no associated data to 'myKey'. If
a function gets passed like this, the 'retrieve' method returns whatever the passed in
function returns. As a convenience the 'store' method returns the input (= second argument).

### Here There Be Tags

	$c->store('a', 'banana banana banana', array(
		'tags' => 'bananas'
	));
	
	$c->store('b', 'apple apple banana', array(
		'tags' => array('apples', 'bananas')
	));
	
	$c->eraseByTag('bananas');
	
	$c->retrieve('a'); // null

See the Source or Specs for more
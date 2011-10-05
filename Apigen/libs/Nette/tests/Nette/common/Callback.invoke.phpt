<?php

/**
 * Test: Nette\Callback tests.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Callback;



require __DIR__ . '/../bootstrap.php';



class Test
{
	static function add($a, $b)
	{
		return $a + $b;
	}
}


$cb = new Callback(new Test, 'add');

Assert::same( 8, $cb/*5.2*->invoke*/(3, 5) );
Assert::same( 8, $cb->invokeArgs(array(3, 5)) );
Assert::same( 3, $cb->invokeNamedArgs(array('b' => 3)) );
Assert::true( $cb->isCallable() );


try {
	callback('undefined')->invoke();
	Assert::fail('Expected exception');
} catch (Exception $e) {
	Assert::exception('Nette\InvalidStateException', "Callback 'undefined' is not callable.", $e );
}

try {
	callback(NULL)->invoke();
	Assert::fail('Expected exception');
} catch (Exception $e) {
	Assert::exception('InvalidArgumentException', 'Invalid callback.', $e );
}

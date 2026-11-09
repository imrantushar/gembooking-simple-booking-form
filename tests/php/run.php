<?php
/**
 * Runs every tests/php/*-test.php file. Usage: php tests/php/run.php
 */

require __DIR__ . '/bootstrap.php';

foreach ( glob( __DIR__ . '/*-test.php' ) as $file ) {
	echo basename( $file ) . "\n";
	require $file;
}

$r = $GLOBALS['gsbf_tests'];
echo "\n{$r['pass']} passed, {$r['fail']} failed\n";
exit( $r['fail'] > 0 ? 1 : 0 );

<?php
/**
 * Minimal bootstrap for the plain-PHP tests: no WordPress, just the few
 * functions the pure classes call, stubbed.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'GSBF_INCLUDES_DIR_PATH', dirname( __DIR__, 2 ) . '/includes/' );

function wp_salt( $scheme = 'auth' ) {
	return 'test-salt-' . $scheme;
}

function wp_json_encode( $value ) {
	return json_encode( $value );
}

function esc_url_raw( $url ) {
	return (string) $url;
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function sanitize_textarea_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function sanitize_hex_color( $color ) {
	return preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', (string) $color ) ? $color : null;
}

require_once GSBF_INCLUDES_DIR_PATH . 'autoload.php';

/** Tiny test harness. */
$GLOBALS['gsbf_tests'] = [ 'pass' => 0, 'fail' => 0 ];

function test( string $name, callable $fn ): void {
	try {
		$fn();
		$GLOBALS['gsbf_tests']['pass']++;
		echo "  ok   $name\n";
	} catch ( \Throwable $e ) {
		$GLOBALS['gsbf_tests']['fail']++;
		echo "  FAIL $name\n       " . $e->getMessage() . "\n";
	}
}

function assert_same( $expected, $actual, string $message = '' ): void {
	if ( $expected !== $actual ) {
		throw new \Exception( ( $message ? $message . ': ' : '' ) . 'expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
	}
}

function assert_true( $value, string $message = '' ): void {
	assert_same( true, (bool) $value, $message );
}

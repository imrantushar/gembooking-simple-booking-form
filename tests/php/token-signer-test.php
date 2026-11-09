<?php

use GembookingSimpleBookingForm\Classes\BookingToken;
use GembookingSimpleBookingForm\Classes\Config;
use GembookingSimpleBookingForm\Classes\ConfigSigner;

test( 'token verifies for the same booking and action', function () {
	$t = BookingToken::generate( 7, 'confirm', time() + 60 );
	assert_true( BookingToken::verify( 7, $t, 'confirm' ) );
} );

test( 'token is bound to booking id and action', function () {
	$t = BookingToken::generate( 7, 'confirm', time() + 60 );
	assert_true( ! BookingToken::verify( 8, $t, 'confirm' ), 'other booking' );
	assert_true( ! BookingToken::verify( 7, $t, 'decline' ), 'other action' );
	assert_true( ! BookingToken::verify( 7, $t, 'nonsense' ), 'unknown action' );
} );

test( 'expired and garbage tokens are rejected', function () {
	assert_true( ! BookingToken::verify( 7, BookingToken::generate( 7, 'view', time() - 5 ), 'view' ) );
	assert_true( ! BookingToken::verify( 7, '', 'view' ) );
	assert_true( ! BookingToken::verify( 7, 'AAAA', 'view' ) );
} );

test( 'a tampered expiry is rejected', function () {
	$t       = BookingToken::generate( 7, 'view', time() + 60 );
	$decoded = base64_decode( strtr( $t, '-_', '+/' ) );
	[ , $sig ] = explode( '.', $decoded, 2 );
	$forged  = rtrim( strtr( base64_encode( ( time() + 999999 ) . '.' . $sig ), '+/', '-_' ), '=' );
	assert_true( ! BookingToken::verify( 7, $forged, 'view' ) );
} );

test( 'signed config verifies and is re-sanitised', function () {
	$config = Config::sanitize( [ 'formId' => 'f1', 'timezone' => 'Asia/Dhaka' ] );
	$signed = ConfigSigner::sign( $config, 'https://example.test/book/' );
	$result = ConfigSigner::verify( $signed['payload'], $signed['sig'] );
	assert_same( 'Asia/Dhaka', $result['config']['timezone'] );
	assert_same( 'https://example.test/book/', $result['page_url'] );
} );

test( 'a changed config fails verification', function () {
	$config = Config::sanitize( [ 'formId' => 'f1', 'endHour' => 17 ] );
	$signed = ConfigSigner::sign( $config, 'https://example.test/' );
	$forged = str_replace( '"endHour":17', '"endHour":24', $signed['payload'] );
	assert_true( $forged !== $signed['payload'] );
	assert_same( null, ConfigSigner::verify( $forged, $signed['sig'] ) );
	assert_same( null, ConfigSigner::verify( $signed['payload'], 'bad' ) );
	assert_same( null, ConfigSigner::verify( '', '' ) );
} );

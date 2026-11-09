<?php

use GembookingSimpleBookingForm\Classes\Config;

test( 'defaults survive sanitising', function () {
	$c = Config::sanitize( [] );
	assert_same( 30, $c['duration'] );
	assert_same( [ 1, 2, 3, 4, 5 ], $c['days'] );
	assert_same( 'UTC', $c['timezone'] );
	assert_same( '#141A24', $c['accent'] );
} );

test( 'duration is rounded to 5 and clamped to 5..480', function () {
	assert_same( 5, Config::sanitize( [ 'duration' => 0 ] )['duration'] );
	assert_same( 10, Config::sanitize( [ 'duration' => 8 ] )['duration'] );
	assert_same( 480, Config::sanitize( [ 'duration' => 1000 ] )['duration'] );
} );

test( 'days are de-duplicated, bounded and sorted', function () {
	assert_same( [ 0, 3, 6 ], Config::sanitize( [ 'days' => [ 6, 3, 3, 9, -1, 0 ] ] )['days'] );
} );

test( 'end hour is always after start hour', function () {
	$c = Config::sanitize( [ 'startHour' => 10, 'endHour' => 8 ] );
	assert_same( 10, $c['startHour'] );
	assert_same( 11, $c['endHour'] );
	assert_same( 24, Config::sanitize( [ 'startHour' => 9, 'endHour' => 99 ] )['endHour'] );
} );

test( 'unknown timezone falls back to UTC, valid is kept', function () {
	assert_same( 'UTC', Config::sanitize( [ 'timezone' => 'Mars/Base' ] )['timezone'] );
	assert_same( 'Asia/Dhaka', Config::sanitize( [ 'timezone' => 'Asia/Dhaka' ] )['timezone'] );
} );

test( 'accent must be a hex colour', function () {
	assert_same( '#141A24', Config::sanitize( [ 'accent' => 'red' ] )['accent'] );
	assert_same( '#006BFF', Config::sanitize( [ 'accent' => '#006bff' ] )['accent'] );
} );

test( 'form id is stripped to safe characters', function () {
	assert_same( 'abc-123script', Config::sanitize( [ 'formId' => 'abc-123"><script>' ] )['formId'] );
} );

test( 'layout and flags are normalised', function () {
	assert_same( 'step', Config::sanitize( [ 'layout' => 'evil' ] )['layout'] );
	assert_same( 'single', Config::sanitize( [ 'layout' => 'single' ] )['layout'] );
	assert_same( false, Config::sanitize( [ 'phoneRequired' => 'false' ] )['phoneRequired'] );
	assert_same( true, Config::sanitize( [ 'phoneRequired' => true ] )['phoneRequired'] );
} );

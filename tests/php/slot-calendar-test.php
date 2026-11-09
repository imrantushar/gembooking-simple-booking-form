<?php

use GembookingSimpleBookingForm\Classes\Config;
use GembookingSimpleBookingForm\Classes\SlotCalendar;

$cfg = static function ( array $over = [] ): array {
	return Config::sanitize( array_merge( [ 'formId' => 'f1', 'timezone' => 'Asia/Dhaka' ], $over ) );
};
// Sunday 2026-11-08 12:00 UTC = 18:00 in Dhaka.
$now = new DateTimeImmutable( '2026-11-08 12:00:00', new DateTimeZone( 'UTC' ) );

test( 'weekday inside the notice window is open, weekend is not', function () use ( $cfg, $now ) {
	assert_true( SlotCalendar::is_date_open( $cfg(), '2026-11-09', $now ), 'Mon open' );
	assert_true( ! SlotCalendar::is_date_open( $cfg(), '2026-11-14', $now ), 'Sat closed' );
	assert_true( ! SlotCalendar::is_date_open( $cfg(), '2026-11-08', $now ), 'today is under 1 day notice' );
} );

test( 'max advance is inclusive', function () use ( $cfg, $now ) {
	assert_true( SlotCalendar::is_date_open( $cfg(), '2027-01-07', $now ), 'day 60 open' );
	assert_true( ! SlotCalendar::is_date_open( $cfg(), '2027-01-08', $now ), 'day 61 closed' );
} );

test( 'invalid date strings are closed', function () use ( $cfg, $now ) {
	assert_true( ! SlotCalendar::is_date_open( $cfg(), '2026-02-31', $now ) );
	assert_true( ! SlotCalendar::is_date_open( $cfg(), 'nope', $now ) );
} );

test( '9-17 with 30 min gives 16 slots, first at 09:00 local', function () use ( $cfg, $now ) {
	$slots = SlotCalendar::slots_for_date( $cfg(), '2026-11-09', $now );
	assert_same( 16, count( $slots ) );
	assert_same( 540, $slots[0]['minutes'] );
	assert_same( '2026-11-09 03:00', gmdate( 'Y-m-d H:i', $slots[0]['start'] ), 'Dhaka is UTC+6' );
	assert_same( 1800, $slots[0]['end'] - $slots[0]['start'] );
	assert_same( 990, $slots[15]['minutes'], 'last slot 16:30' );
} );

test( 'buffer widens the step', function () use ( $cfg, $now ) {
	$slots = SlotCalendar::slots_for_date( $cfg( [ 'buffer' => 15 ] ), '2026-11-09', $now );
	assert_same( 11, count( $slots ) );
	assert_same( 585, $slots[1]['minutes'], 'second slot at 09:45' );
} );

test( 'slots that already started are dropped', function () use ( $cfg ) {
	$now   = new DateTimeImmutable( '2026-11-09 04:10:00', new DateTimeZone( 'UTC' ) ); // 10:10 Dhaka.
	$slots = SlotCalendar::slots_for_date( $cfg( [ 'notice' => 0 ] ), '2026-11-09', $now );
	assert_same( 630, $slots[0]['minutes'], 'first remaining slot is 10:30' );
} );

test( 'DST end day keeps wall-clock times', function () {
	$config = Config::sanitize( [ 'formId' => 'f1', 'timezone' => 'America/New_York', 'days' => [ 0, 1, 2, 3, 4, 5, 6 ] ] );
	$now    = new DateTimeImmutable( '2026-10-20 00:00:00', new DateTimeZone( 'UTC' ) );
	$slots  = SlotCalendar::slots_for_date( $config, '2026-11-01', $now ); // DST ends this Sunday.
	assert_same( 'Sun 09:00', ( new DateTimeImmutable( '@' . $slots[0]['start'] ) )->setTimezone( new DateTimeZone( 'America/New_York' ) )->format( 'D H:i' ) );
	assert_same( '14:00', gmdate( 'H:i', $slots[0]['start'] ), '09:00 EST = 14:00 UTC' );
} );

test( 'find_slot only accepts a real slot start', function () use ( $cfg, $now ) {
	$slots = SlotCalendar::slots_for_date( $cfg(), '2026-11-09', $now );
	assert_true( SlotCalendar::find_slot( $cfg(), $slots[3]['start'], $now ) !== null );
	assert_same( null, SlotCalendar::find_slot( $cfg(), $slots[3]['start'] + 60, $now ), 'off-grid' );
	assert_same( null, SlotCalendar::find_slot( $cfg(), strtotime( '2026-11-14 04:00:00 UTC' ), $now ), 'closed day' );
} );

test( 'open_dates lists the open dates of a month', function () use ( $cfg, $now ) {
	$dates = SlotCalendar::open_dates( $cfg(), '2026-11', $now );
	assert_same( '2026-11-09', $dates[0] );
	assert_true( ! in_array( '2026-11-14', $dates, true ) );
	assert_same( [], SlotCalendar::open_dates( $cfg(), '2026-13', $now ) );
} );

test( 'overlaps is half-open', function () {
	assert_true( SlotCalendar::overlaps( 100, 200, [ [ 'start' => 150, 'end' => 250 ] ] ) );
	assert_true( ! SlotCalendar::overlaps( 100, 200, [ [ 'start' => 200, 'end' => 300 ] ] ), 'touching is fine' );
	assert_true( ! SlotCalendar::overlaps( 100, 200, [] ) );
} );

<?php

namespace GembookingSimpleBookingForm\Classes;

use GembookingSimpleBookingForm\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the links placed in emails: confirm, decline, view, reschedule, cancel.
 *
 * Every link points at the page the booking was made on (its signed
 * `page_url`) and carries only the booking id and a signed token.
 *   - confirm / decline / cancel  => ActionPage (a landing page; the button POSTs)
 *   - view / reschedule           => the page's booking form opens in that mode
 */
class BookingActionUrl {

	const QUERY_ACTION  = 'gsbf_action';
	const QUERY_BOOKING = 'gsbf_booking';
	const QUERY_TOKEN   = 'gsbf_token';

	/**
	 * When a token for this booking and action stops working.
	 *
	 * @param array  $booking Booking row.
	 * @param string $action  One of BookingToken::ACTIONS.
	 * @return int Unix timestamp.
	 */
	public static function expiry( array $booking, string $action ): int {
		switch ( $action ) {
			case 'confirm':
			case 'decline':
				return Helper::ts( (string) $booking['requested_at'] ) + Helper::PENDING_EXPIRY_HOURS * HOUR_IN_SECONDS;
			case 'view':
				return Helper::ts( (string) $booking['end_utc'] );
			default: // reschedule, cancel.
				return Helper::ts( (string) $booking['start_utc'] );
		}
	}

	public static function url( array $booking, string $action ): string {
		$base = ! empty( $booking['page_url'] ) ? (string) $booking['page_url'] : home_url( '/' );

		return add_query_arg(
			[
				self::QUERY_ACTION  => $action,
				self::QUERY_BOOKING => (int) $booking['id'],
				self::QUERY_TOKEN   => BookingToken::generate( (int) $booking['id'], $action, self::expiry( $booking, $action ) ),
			],
			$base
		);
	}

	/**
	 * Plain link to the booking page (no token), for "book again" buttons.
	 *
	 * @param array $booking Booking row.
	 */
	public static function page_url( array $booking ): string {
		return ! empty( $booking['page_url'] ) ? (string) $booking['page_url'] : home_url( '/' );
	}
}

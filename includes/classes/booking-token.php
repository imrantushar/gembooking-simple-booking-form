<?php

namespace GembookingSimpleBookingForm\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HMAC-signed, action-bound, expiring token for the links in emails
 * (confirm, decline, view, reschedule, cancel).
 *
 * The URL only carries the booking id and this token; everything else is
 * read from the database. Expiry is baked into the token when it is made.
 */
class BookingToken {

	const ACTIONS = [ 'confirm', 'decline', 'view', 'reschedule', 'cancel' ];

	public static function generate( int $booking_id, string $action, int $expires ): string {
		$payload = $booking_id . '|' . $action . '|' . $expires;
		$sig     = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- URL-safe token encoding, not obfuscation.
		return rtrim( strtr( base64_encode( $expires . '.' . $sig ), '+/', '-_' ), '=' );
	}

	public static function verify( int $booking_id, string $token, string $action ): bool {
		if ( '' === $token || ! in_array( $action, self::ACTIONS, true ) ) {
			return false;
		}

		$padded  = str_pad( $token, strlen( $token ) + ( ( 4 - ( strlen( $token ) % 4 ) ) % 4 ), '=', STR_PAD_RIGHT );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decoding our own URL-safe token.
		$decoded = base64_decode( strtr( $padded, '-_', '+/' ), true );
		if ( false === $decoded || false === strpos( $decoded, '.' ) ) {
			return false;
		}

		list( $expires, $sig ) = explode( '.', $decoded, 2 );
		if ( ! ctype_digit( $expires ) || (int) $expires < time() ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $booking_id . '|' . $action . '|' . $expires, wp_salt( 'auth' ) );

		return hash_equals( $expected, $sig );
	}
}

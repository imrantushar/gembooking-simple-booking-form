<?php

namespace GembookingSimpleBookingForm\Classes;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cheap, cookie-free abuse protection for the public booking endpoint:
 * honeypot, minimum fill time and per-IP / per-email rate limits.
 */
class SpamGuard {

	const MIN_FILL_MS     = 2000;
	const IP_LIMIT        = 12;
	const IP_WINDOW       = 10 * MINUTE_IN_SECONDS;
	const EMAIL_LIMIT     = 5;
	const EMAIL_WINDOW    = HOUR_IN_SECONDS;

	/**
	 * Salted hash of the visitor IP (never the IP itself).
	 */
	public static function ip_hash(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return '' === $ip ? '' : hash( 'sha256', $ip . wp_salt( 'auth' ) );
	}

	public static function check( string $honeypot, int $fill_ms, string $email ) {
		if ( '' !== $honeypot ) {
			return new WP_Error( 'gsbf_spam', __( 'Could not send the request. Please try again.', 'gembooking-simple-booking-form' ), [ 'status' => 400 ] );
		}

		if ( $fill_ms < self::MIN_FILL_MS ) {
			return new WP_Error( 'gsbf_too_fast', __( 'That was very quick. Please check your details and try again.', 'gembooking-simple-booking-form' ), [ 'status' => 400 ] );
		}

		$ip = self::ip_hash();
		if ( '' !== $ip && self::hit( 'ip_' . $ip, self::IP_LIMIT, self::IP_WINDOW ) ) {
			return self::too_many();
		}
		if ( self::hit( 'em_' . hash( 'sha256', strtolower( $email ) ), self::EMAIL_LIMIT, self::EMAIL_WINDOW ) ) {
			return self::too_many();
		}

		return true;
	}

	/**
	 * Count one request; true when the limit is already exceeded.
	 *
	 * @param string $key    Counter key.
	 * @param int    $limit  Allowed requests.
	 * @param int    $window Window in seconds.
	 */
	private static function hit( string $key, int $limit, int $window ): bool {
		$key   = 'gsbf_rl_' . substr( hash( 'sha256', $key ), 0, 40 );
		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return true;
		}

		set_transient( $key, $count + 1, $window );

		return false;
	}

	private static function too_many(): WP_Error {
		return new WP_Error( 'gsbf_rate_limited', __( 'Too many requests. Please try again later.', 'gembooking-simple-booking-form' ), [ 'status' => 429 ] );
	}
}

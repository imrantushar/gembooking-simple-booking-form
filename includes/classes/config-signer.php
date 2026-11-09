<?php

namespace GembookingSimpleBookingForm\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Signs and verifies the block config that is printed on the page.
 *
 * The config only exists in post content (a post, template, pattern or
 * widget), so the server cannot look it up by id when a booking arrives.
 * Instead the config is signed at render time and sent back by the browser
 * with every request; a visitor cannot change the hours, duration or
 * timezone without invalidating the signature.
 */
class ConfigSigner {

	/**
	 * Sign a config together with the permalink of the page it is rendered on.
	 *
	 * @param array  $config   Sanitized config.
	 * @param string $page_url Permalink of the page (used to build reschedule links).
	 * @return array{payload:string,sig:string}
	 */
	public static function sign( array $config, string $page_url ): array {
		$payload = (string) wp_json_encode(
			[
				'c' => $config,
				'u' => $page_url,
			]
		);

		return [
			'payload' => $payload,
			'sig'     => self::signature( $payload ),
		];
	}

	/**
	 * Verify a signed payload.
	 *
	 * @param string $payload JSON payload exactly as printed on the page.
	 * @param string $sig     Hex signature.
	 * @return array{config:array,page_url:string}|null Null when the signature is wrong.
	 */
	public static function verify( string $payload, string $sig ): ?array {
		if ( '' === $payload || '' === $sig || ! hash_equals( self::signature( $payload ), $sig ) ) {
			return null;
		}

		$decoded = json_decode( $payload, true );
		if ( ! is_array( $decoded ) || ! isset( $decoded['c'] ) || ! is_array( $decoded['c'] ) ) {
			return null;
		}

		return [
			'config'   => Config::sanitize( $decoded['c'] ),
			'page_url' => esc_url_raw( (string) ( $decoded['u'] ?? '' ) ),
		];
	}

	private static function signature( string $payload ): string {
		return hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
	}
}

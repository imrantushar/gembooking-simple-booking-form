<?php

namespace GembookingSimpleBookingForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Small shared helpers: settings access and a few formatting utilities.
 */
class Helper {

	/** Hours a `pending` booking request waits for the admin before it is dropped. */
	const PENDING_EXPIRY_HOURS = 48;

	/**
	 * Default site-wide settings (the email modal's "Settings" tab).
	 *
	 * @return array
	 */
	public static function default_settings(): array {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$host = is_string( $host ) ? $host : '';

		return [
			// manual: the admin confirms by email. auto: confirmed instantly.
			'approval'   => 'manual',
			'adminTo'    => (string) get_option( 'admin_email', '' ),
			'fromName'   => wp_specialchars_decode( (string) get_option( 'blogname', '' ), ENT_QUOTES ),
			'fromEmail'  => (string) get_option( 'admin_email', '' ),
			'type'       => 'html',
			'logo'       => 0,
			'footerHtml' => sprintf(
				/* translators: 1: link to the site, 2: site host */
				__( 'Thank you for using GemBooking. <a href="%1$s">%2$s</a>', 'gembooking-simple-booking-form' ),
				esc_url( home_url( '/' ) ),
				esc_html( $host )
			),
		];
	}

	/**
	 * Saved settings merged over the defaults.
	 *
	 * @return array
	 */
	public static function get_settings(): array {
		$saved = get_option( GSBF_SETTINGS_NAME, [] );
		$saved = is_array( $saved ) ? $saved : [];

		return self::sanitize_settings( array_merge( self::default_settings(), $saved ) );
	}

	/**
	 * Sanitize a settings array (from REST input or the database).
	 *
	 * @param array $raw Raw values.
	 * @return array
	 */
	public static function sanitize_settings( array $raw ): array {
		$defaults = self::default_settings();

		$approval = $raw['approval'] ?? $defaults['approval'];
		$type     = $raw['type'] ?? $defaults['type'];

		$admin_to   = sanitize_email( (string) ( $raw['adminTo'] ?? '' ) );
		$from_email = sanitize_email( (string) ( $raw['fromEmail'] ?? '' ) );
		$from_name  = sanitize_text_field( (string) ( $raw['fromName'] ?? '' ) );

		return [
			'approval'   => in_array( $approval, [ 'manual', 'auto' ], true ) ? $approval : 'manual',
			'adminTo'    => is_email( $admin_to ) ? $admin_to : $defaults['adminTo'],
			'fromName'   => '' !== $from_name ? $from_name : $defaults['fromName'],
			'fromEmail'  => is_email( $from_email ) ? $from_email : $defaults['fromEmail'],
			'type'       => in_array( $type, [ 'html', 'plain' ], true ) ? $type : 'html',
			'logo'       => max( 0, (int) ( $raw['logo'] ?? 0 ) ),
			'footerHtml' => wp_kses( (string) ( $raw['footerHtml'] ?? $defaults['footerHtml'] ), self::footer_allowed_html() ),
		];
	}

	/**
	 * Tags allowed in the email footer.
	 *
	 * @return array
	 */
	public static function footer_allowed_html(): array {
		return [
			'a'      => [
				'href'   => true,
				'target' => true,
				'rel'    => true,
			],
			'strong' => [],
			'b'      => [],
			'em'     => [],
			'i'      => [],
			'u'      => [],
			'ul'     => [],
			'ol'     => [],
			'li'     => [],
			'br'     => [],
			'p'      => [],
		];
	}

	/**
	 * A valid hex colour, or the default accent.
	 *
	 * @param string $color Candidate colour.
	 * @return string
	 */
	public static function sanitize_accent( string $color ): string {
		$clean = sanitize_hex_color( $color );

		return $clean ? $clean : '#141A24';
	}

	/**
	 * Current UTC time as a MySQL DATETIME string.
	 *
	 * @param int|null $timestamp Unix timestamp, now when null.
	 * @return string
	 */
	public static function utc( ?int $timestamp = null ): string {
		return gmdate( 'Y-m-d H:i:s', $timestamp ?? time() );
	}

	/**
	 * Unix timestamp of a UTC MySQL DATETIME string.
	 *
	 * @param string $datetime Y-m-d H:i:s in UTC.
	 * @return int
	 */
	public static function ts( string $datetime ): int {
		$date = date_create_immutable( $datetime, new \DateTimeZone( 'UTC' ) );
		return $date ? $date->getTimestamp() : 0;
	}
}

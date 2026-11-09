<?php

namespace GembookingSimpleBookingForm\Classes;

use GembookingSimpleBookingForm\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds and sends every email through wp_mail().
 */
class Mailer {

	/**
	 * Send the email for a booking event.
	 *
	 * @param string $event   Key of MailDefaults::AUDIENCE.
	 * @param array  $booking Booking row.
	 * @param array  $ctx     Extra context (see MailDefaults::build()).
	 * @return bool
	 */
	public static function send( string $event, array $booking, array $ctx = [] ): bool {
		$settings = Helper::get_settings();
		$content  = MailDefaults::build( $event, $booking, $ctx );
		$to       = 'admin' === $content['audience'] ? $settings['adminTo'] : (string) $booking['email'];

		if ( ! is_email( $to ) ) {
			return false;
		}

		$headers = self::headers( $settings );
		if ( 'admin' === $content['audience'] && is_email( $booking['email'] ) ) {
			$headers[] = 'Reply-To: ' . self::address( (string) $booking['name'], (string) $booking['email'] );
		}

		$body = MailTemplate::render( $content, $settings['type'], $settings, (string) ( $booking['accent'] ?? '' ), $to );

		return (bool) wp_mail( $to, $content['subject'], $body, $headers );
	}

	/**
	 * HTML for the modal's read-only preview, with sample data and inert links.
	 *
	 * @param string $kind     admin|customer.
	 * @param array  $settings Unsaved settings from the modal.
	 * @param string $accent   Brand colour.
	 * @return string
	 */
	public static function preview( string $kind, array $settings, string $accent ): string {
		$event   = 'admin' === $kind ? 'admin_request' : 'customer_confirmed';
		$booking = self::sample_booking( $accent );
		$content = MailDefaults::build( $event, $booking, [ 'sample' => true ] );
		$to      = 'admin' === $kind ? $settings['adminTo'] : $booking['email'];

		return MailTemplate::render( $content, 'html', $settings, $accent, $to );
	}

	/**
	 * Send the admin "new booking request" email with sample data to the admin address.
	 *
	 * @param string $accent Brand colour.
	 */
	public static function send_test( string $accent ): bool {
		$settings = Helper::get_settings();
		$booking  = self::sample_booking( $accent );
		$content  = MailDefaults::build( 'admin_request', $booking, [ 'sample' => true ] );

		if ( ! is_email( $settings['adminTo'] ) ) {
			return false;
		}

		$body = MailTemplate::render( $content, $settings['type'], $settings, $accent, $settings['adminTo'] );

		return (bool) wp_mail(
			$settings['adminTo'],
			/* translators: %s: email subject */
			sprintf( __( '[Test] %s', 'gembooking-simple-booking-form' ), $content['subject'] ),
			$body,
			self::headers( $settings )
		);
	}

	private static function sample_booking( string $accent ): array {
		$start = strtotime( 'next wednesday 10:00' );
		$tz    = wp_timezone_string();

		return [
			'id'          => 0,
			'ref'         => 'GB-7K3M9Q',
			'event_title' => __( '30-minute consultation', 'gembooking-simple-booking-form' ),
			'name'        => 'Rahim Uddin',
			'email'       => 'rahim@example.com',
			'phone'       => '+880 1712 345678',
			'start_utc'   => Helper::utc( $start ),
			'end_utc'     => Helper::utc( $start + 30 * MINUTE_IN_SECONDS ),
			'timezone'    => in_array( $tz, \DateTimeZone::listIdentifiers(), true ) ? $tz : 'UTC',
			'accent'      => $accent,
			'page_url'    => home_url( '/' ),
		];
	}

	private static function headers( array $settings ): array {
		return [
			'Content-Type: ' . ( 'plain' === $settings['type'] ? 'text/plain' : 'text/html' ) . '; charset=UTF-8',
			'From: ' . self::address( (string) $settings['fromName'], (string) $settings['fromEmail'] ),
		];
	}

	/**
	 * "Name" <email> with anything that could break a header removed.
	 *
	 * @param string $name  Display name.
	 * @param string $email Email address.
	 */
	private static function address( string $name, string $email ): string {
		$name = trim( str_replace( [ '"', "\r", "\n", '<', '>' ], '', $name ) );

		return '' === $name ? $email : '"' . $name . '" <' . $email . '>';
	}
}

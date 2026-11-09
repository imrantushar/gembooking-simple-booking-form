<?php

namespace GembookingSimpleBookingForm\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the structure from MailDefaults::build() into an HTML or plain-text
 * email body using templates/email/layout.php and templates/email/plain.php.
 */
class MailTemplate {

	/** WordPress's default (system) font stack; no webfont is ever loaded. */
	const FONT_STACK = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';

	/** Banner colours (background, text) per tone. */
	const TONES = [
		'info'    => [ '#F6F7F8', '#434A51' ],
		'success' => [ '#DCFCE7', '#166534' ],
		'warning' => [ '#FEF3C7', '#92400E' ],
		'danger'  => [ '#FEE2E2', '#991B1B' ],
	];

	/**
	 * @param array  $content  MailDefaults::build() result.
	 * @param string $type     html|plain.
	 * @param array  $settings Site settings (Helper::get_settings()).
	 * @param string $accent   Brand colour (the booked form's accent).
	 * @param string $to       Recipient address, shown on the "to" line.
	 * @return string
	 */
	public static function render( array $content, string $type, array $settings, string $accent, string $to ): string {
		$accent = \GembookingSimpleBookingForm\Helper::sanitize_accent( $accent );
		$file   = 'plain' === $type ? 'plain.php' : 'layout.php';

		$vars = [
			'c'        => $content,
			'settings' => $settings,
			'accent'   => $accent,
			'to'       => $to,
			'font'     => self::FONT_STACK,
			'tone'     => self::TONES[ $content['tone'] ] ?? self::TONES['info'],
			'logo_url' => $settings['logo'] ? (string) wp_get_attachment_image_url( (int) $settings['logo'], 'medium' ) : '',
			'initials' => self::initials( (string) $settings['fromName'] ),
		];

		return self::include_template( GSBF_TEMPLATES_DIR_PATH . 'email/' . $file, $vars );
	}

	private static function initials( string $name ): string {
		$letters = '';
		$words   = preg_split( '/\s+/', trim( $name ) );
		foreach ( array_slice( false === $words ? [] : $words, 0, 2 ) as $word ) {
			$letters .= function_exists( 'mb_substr' ) ? mb_substr( $word, 0, 1 ) : substr( $word, 0, 1 );
		}
		return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $letters ) : strtoupper( $letters );
	}

	private static function include_template( string $file, array $vars ): string {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- scoped, explicit template variables.
		extract( $vars, EXTR_SKIP );
		ob_start();
		include $file;
		return (string) ob_get_clean();
	}
}

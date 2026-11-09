<?php

namespace GembookingSimpleBookingForm\Render;

use GembookingSimpleBookingForm\Classes\Config;
use GembookingSimpleBookingForm\Classes\ConfigSigner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the mount point the front-end widget hydrates.
 *
 * Prints the sanitized, signed block config; the browser sends it back with
 * every REST call (see ConfigSigner).
 */
class Form {

	/**
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function html( array $attributes ): string {
		$config   = Config::sanitize( $attributes );
		$page_url = self::page_url();

		// Safety net: the editor always sets a formId; a block without one still works.
		if ( '' === $config['formId'] ) {
			$config['formId'] = 'gsbf-' . substr( md5( (string) wp_json_encode( $config ) . $page_url ), 0, 12 );
		}

		$signed  = ConfigSigner::sign( $config, $page_url );
		$wrapper = get_block_wrapper_attributes(
			[
				'class' => 'gsbf-form',
				'style' => '--gsbf-accent:' . $config['accent'] . ';',
			]
		);

		ob_start();
		include GSBF_TEMPLATES_DIR_PATH . 'form.php';

		return (string) ob_get_clean();
	}

	/**
	 * Permalink of the page being rendered (used to build links back to it).
	 */
	private static function page_url(): string {
		$url = '';

		if ( is_singular() ) {
			$url = (string) get_permalink( (int) get_queried_object_id() );
		}

		if ( '' === $url && isset( $_SERVER['REQUEST_URI'] ) ) {
			$path = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
			$url  = is_string( $path ) ? home_url( $path ) : home_url( '/' );
		}

		return esc_url_raw( '' !== $url ? $url : home_url( '/' ) );
	}
}

<?php

namespace GembookingSimpleBookingForm\Classes;

use WP_Error;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and verifies the signed block config (`config` + `sig`) a public REST
 * request carries.
 */
class RequestConfig {

	/**
	 * @param WP_REST_Request $request Request with `config` and `sig` params.
	 * @return array{config:array,page_url:string}|WP_Error
	 */
	public static function verify( WP_REST_Request $request ) {
		$verified = ConfigSigner::verify(
			(string) $request->get_param( 'config' ),
			(string) $request->get_param( 'sig' )
		);

		if ( null === $verified ) {
			return new WP_Error(
				'gsbf_bad_signature',
				__( 'This form is out of date. Please reload the page and try again.', 'gembooking-simple-booking-form' ),
				[ 'status' => 403 ]
			);
		}

		return $verified;
	}

	/**
	 * Request args shared by every route that needs the signed config.
	 *
	 * @return array
	 */
	public static function args(): array {
		return [
			'config' => [
				'required' => true,
				'type'     => 'string',
			],
			'sig'    => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}
}

<?php

namespace GembookingSimpleBookingForm\API;

use GembookingSimpleBookingForm\Classes\BookingRepository;
use GembookingSimpleBookingForm\Classes\BookingService;
use GembookingSimpleBookingForm\Classes\BookingToken;
use GembookingSimpleBookingForm\Classes\RequestConfig;
use GembookingSimpleBookingForm\Classes\SpamGuard;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public booking endpoints.
 *
 *   POST /bookings                    create a booking (signed config + anti-spam)
 *   GET  /bookings/{id}               read one booking (signed `view`/`reschedule` token)
 *   POST /bookings/{id}/reschedule    move a booking (signed `reschedule` token + signed config)
 *
 * There is deliberately no list endpoint.
 */
class BookingController {

	public function register_routes(): void {
		register_rest_route(
			GSBF_REST_NAMESPACE,
			'/bookings',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create' ],
				'permission_callback' => '__return_true', // Public; signed config + SpamGuard are the gate.
				'args'                => RequestConfig::args() + [
					'start' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'name'  => [
						'required' => true,
						'type'     => 'string',
					],
					'email' => [
						'required' => true,
						'type'     => 'string',
					],
					'phone' => [
						'type'    => 'string',
						'default' => '',
					],
					'hp'    => [
						'type'    => 'string',
						'default' => '',
					],
					't'     => [
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			GSBF_REST_NAMESPACE,
			'/bookings/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_one' ],
				'permission_callback' => '__return_true', // Public; the signed token is the gate.
				'args'                => [
					'id'     => [
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'token'  => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'action' => [
						'required' => true,
						'type'     => 'string',
						'enum'     => [ 'view', 'reschedule' ],
					],
				],
			]
		);

		register_rest_route(
			GSBF_REST_NAMESPACE,
			'/bookings/(?P<id>\d+)/reschedule',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'reschedule' ],
				'permission_callback' => '__return_true', // Public; the signed token is the gate.
				'args'                => RequestConfig::args() + [
					'id'    => [
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'token' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'start' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create( WP_REST_Request $request ) {
		$verified = RequestConfig::verify( $request );
		if ( is_wp_error( $verified ) ) {
			return $verified;
		}

		$email = sanitize_email( (string) $request->get_param( 'email' ) );

		$guard = SpamGuard::check( (string) $request->get_param( 'hp' ), (int) $request->get_param( 't' ), $email );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$booking = BookingService::create(
			$verified,
			[
				'start' => (int) $request->get_param( 'start' ),
				'name'  => (string) $request->get_param( 'name' ),
				'email' => $email,
				'phone' => (string) $request->get_param( 'phone' ),
			]
		);
		if ( is_wp_error( $booking ) ) {
			return $booking;
		}

		return $this->respond( $booking, 201 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_one( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );

		if ( ! BookingToken::verify( $id, (string) $request->get_param( 'token' ), (string) $request->get_param( 'action' ) ) ) {
			return $this->invalid_link();
		}

		$booking = BookingRepository::find( $id );
		if ( ! $booking ) {
			return new WP_Error( 'gsbf_not_found', __( 'This booking no longer exists.', 'gembooking-simple-booking-form' ), [ 'status' => 404 ] );
		}

		return $this->respond( $booking, 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function reschedule( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );

		if ( ! BookingToken::verify( $id, (string) $request->get_param( 'token' ), 'reschedule' ) ) {
			return $this->invalid_link();
		}

		$verified = RequestConfig::verify( $request );
		if ( is_wp_error( $verified ) ) {
			return $verified;
		}

		$booking = BookingService::reschedule( $id, $verified, (int) $request->get_param( 'start' ) );
		if ( is_wp_error( $booking ) ) {
			return $booking;
		}

		return $this->respond( $booking, 200 );
	}

	private function respond( array $booking, int $status ): WP_REST_Response {
		$response = new WP_REST_Response( BookingService::to_public( $booking ), $status );
		$response->set_headers( [ 'Cache-Control' => 'no-store' ] );

		return $response;
	}

	private function invalid_link(): WP_Error {
		return new WP_Error( 'gsbf_invalid_link', __( 'This link is invalid or has expired.', 'gembooking-simple-booking-form' ), [ 'status' => 403 ] );
	}
}

<?php

namespace GembookingSimpleBookingForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API registration.
 */
class Api {

	public static function init(): void {
		$self = new self();
		add_action( 'rest_api_init', [ $self, 'register_routes' ] );
	}

	public function register_routes(): void {
		( new API\AvailabilityController() )->register_routes();
		( new API\BookingController() )->register_routes();
		( new API\EmailSettingsController() )->register_routes();
	}
}

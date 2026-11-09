<?php

namespace GembookingSimpleBookingForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the personal-data exporter and eraser (name, email and phone are
 * stored in the bookings table).
 */
class Privacy {

	public static function init(): void {
		$self = new self();
		add_filter( 'wp_privacy_personal_data_exporters', [ $self, 'register_exporter' ] );
		add_filter( 'wp_privacy_personal_data_erasers', [ $self, 'register_eraser' ] );
	}

	public function register_exporter( $exporters ) {
		$exporters['gembooking-simple-booking-form'] = [
			'exporter_friendly_name' => __( 'GemBooking Simple Booking Form bookings', 'gembooking-simple-booking-form' ),
			'callback'               => [ Privacy\Exporter::class, 'export' ],
		];

		return $exporters;
	}

	public function register_eraser( $erasers ) {
		$erasers['gembooking-simple-booking-form'] = [
			'eraser_friendly_name' => __( 'GemBooking Simple Booking Form bookings', 'gembooking-simple-booking-form' ),
			'callback'             => [ Privacy\Eraser::class, 'erase' ],
		];

		return $erasers;
	}
}

<?php

namespace GembookingSimpleBookingForm\Privacy;

use GembookingSimpleBookingForm\Classes\BookingRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eraser {

	/**
	 * Anonymise the person's bookings. The slot rows are kept so that the
	 * double-booking protection of future slots is not broken.
	 *
	 * @param string $email_address Email of the person.
	 * @param int    $page          Page number (unused: one pass erases everything).
	 * @return array
	 */
	public static function erase( $email_address, $page = 1 ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- fixed signature required by the privacy eraser API.
		$count = BookingRepository::anonymize_by_email( sanitize_email( (string) $email_address ) );

		return [
			'items_removed'  => $count > 0,
			'items_retained' => false,
			'messages'       => $count > 0
				? [ __( 'Booking names, emails and phone numbers were anonymised. The booked time slots were kept so that later bookings cannot double up.', 'gembooking-simple-booking-form' ) ]
				: [],
			'done'           => true,
		];
	}
}

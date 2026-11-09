<?php

namespace GembookingSimpleBookingForm\Classes;

use GembookingSimpleBookingForm\Helper;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Booking rules: create, confirm, decline, cancel, reschedule, expire.
 *
 * Status values: `pending` (waiting for the admin) and `confirmed`. A declined,
 * cancelled or expired booking is deleted, which frees its slot immediately.
 */
class BookingService {

	/**
	 * Create a booking from a verified request.
	 *
	 * @param array $verified ConfigSigner::verify() result.
	 * @param array $input    start (unix), name, email, phone.
	 * @return array|WP_Error The new booking row.
	 */
	public static function create( array $verified, array $input ) {
		$config = $verified['config'];

		$customer = self::validate_customer( $config, $input );
		if ( is_wp_error( $customer ) ) {
			return $customer;
		}

		$slot = self::open_slot( $config, (int) ( $input['start'] ?? 0 ), 0 );
		if ( is_wp_error( $slot ) ) {
			return $slot;
		}

		$settings = Helper::get_settings();
		$status   = 'auto' === $settings['approval'] ? 'confirmed' : 'pending';

		$id = BookingRepository::insert(
			[
				'form_id'     => $config['formId'],
				'page_url'    => $verified['page_url'],
				'event_title' => $config['title'],
				'accent'      => $config['accent'],
				'start_utc'   => Helper::utc( $slot['start'] ),
				'end_utc'     => Helper::utc( $slot['end'] ),
				'timezone'    => $config['timezone'],
				'duration'    => $config['duration'],
				'name'        => $customer['name'],
				'email'       => $customer['email'],
				'phone'       => '' === $customer['phone'] ? null : $customer['phone'],
				'status'      => $status,
				'ip_hash'     => SpamGuard::ip_hash(),
			]
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$booking = BookingRepository::find( $id );

		if ( 'confirmed' === $status ) {
			Mailer::send( 'customer_confirmed', $booking );
			Mailer::send( 'admin_new_confirmed', $booking );
		} else {
			Mailer::send( 'admin_request', $booking );
		}

		return $booking;
	}

	/**
	 * Admin confirms a pending booking (or a pending reschedule request).
	 *
	 * @param int $id Booking id.
	 * @return array|WP_Error The confirmed booking row.
	 */
	public static function confirm( int $id ) {
		$booking = BookingRepository::find( $id );
		if ( ! $booking || 'pending' !== $booking['status'] ) {
			return self::not_pending();
		}

		$was_reschedule = ! empty( $booking['rescheduled_at'] );

		BookingRepository::update(
			$id,
			[
				'status'         => 'confirmed',
				'rescheduled_at' => null,
			]
		);

		$booking = BookingRepository::find( $id );
		Mailer::send( $was_reschedule ? 'customer_rescheduled' : 'customer_confirmed', $booking );

		return $booking;
	}

	/**
	 * Admin declines a pending booking: the customer is told, the row is deleted.
	 *
	 * @param int $id Booking id.
	 * @return array|WP_Error The deleted booking row.
	 */
	public static function decline( int $id ) {
		$booking = BookingRepository::find( $id );
		if ( ! $booking || 'pending' !== $booking['status'] ) {
			return self::not_pending();
		}

		Mailer::send( ! empty( $booking['rescheduled_at'] ) ? 'customer_reschedule_declined' : 'customer_declined', $booking );
		BookingRepository::delete( $id );

		return $booking;
	}

	/**
	 * Customer cancels: both sides are told, the row is deleted.
	 *
	 * @param int $id Booking id.
	 * @return array|WP_Error The deleted booking row.
	 */
	public static function cancel( int $id ) {
		$booking = BookingRepository::find( $id );
		if ( ! $booking ) {
			return new WP_Error( 'gsbf_not_found', __( 'This booking no longer exists.', 'gembooking-simple-booking-form' ), [ 'status' => 404 ] );
		}

		Mailer::send( 'customer_cancelled', $booking );
		Mailer::send( 'admin_cancelled', $booking );
		BookingRepository::delete( $id );

		return $booking;
	}

	/**
	 * Customer moves a booking to another slot. The old slot is released at
	 * once. In manual mode the booking goes (back) to `pending`.
	 *
	 * @param int   $id       Booking id.
	 * @param array $verified ConfigSigner::verify() result of the page's form.
	 * @param int   $start    New slot start (unix).
	 * @return array|WP_Error The updated booking row.
	 */
	public static function reschedule( int $id, array $verified, int $start ) {
		$booking = BookingRepository::find( $id );
		if ( ! $booking ) {
			return new WP_Error( 'gsbf_not_found', __( 'This booking no longer exists.', 'gembooking-simple-booking-form' ), [ 'status' => 404 ] );
		}

		$config = $verified['config'];
		if ( $config['formId'] !== $booking['form_id'] ) {
			return new WP_Error( 'gsbf_wrong_form', __( 'This booking belongs to a different form.', 'gembooking-simple-booking-form' ), [ 'status' => 403 ] );
		}
		if ( Helper::ts( (string) $booking['start_utc'] ) <= time() ) {
			return new WP_Error( 'gsbf_past', __( 'This booking has already started and can no longer be changed.', 'gembooking-simple-booking-form' ), [ 'status' => 400 ] );
		}

		$slot = self::open_slot( $config, $start, $id );
		if ( is_wp_error( $slot ) ) {
			return $slot;
		}

		$previous = [
			'start' => Helper::ts( (string) $booking['start_utc'] ),
			'end'   => Helper::ts( (string) $booking['end_utc'] ),
		];
		$manual   = 'manual' === Helper::get_settings()['approval'];

		$fields = [
			'start_utc'   => Helper::utc( $slot['start'] ),
			'end_utc'     => Helper::utc( $slot['end'] ),
			'timezone'    => $config['timezone'],
			'duration'    => $config['duration'],
			'event_title' => $config['title'],
			'accent'      => $config['accent'],
		];
		if ( $manual ) {
			$fields['status']       = 'pending';
			$fields['requested_at'] = Helper::utc();
			// Only a previously confirmed booking becomes an "unapproved
			// reschedule"; a still-pending new request stays a new request.
			if ( 'confirmed' === $booking['status'] ) {
				$fields['rescheduled_at'] = Helper::utc();
			}
		}

		$result = BookingRepository::update_slot( $id, $fields );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$booking = BookingRepository::find( $id );
		$ctx     = [ 'previous' => $previous ];

		if ( $manual ) {
			Mailer::send( 'admin_reschedule_request', $booking, $ctx );
			Mailer::send( 'customer_reschedule_received', $booking, $ctx );
		} else {
			Mailer::send( 'admin_rescheduled', $booking, $ctx );
			Mailer::send( 'customer_rescheduled', $booking, $ctx );
		}

		return $booking;
	}

	/**
	 * Cron: tell the customer a pending request was not decided in time, then
	 * delete it. A failed email never blocks the delete (the slot must be freed).
	 *
	 * @param array $booking Booking row.
	 */
	public static function expire( array $booking ): void {
		Mailer::send( ! empty( $booking['rescheduled_at'] ) ? 'customer_reschedule_expired' : 'customer_expired', $booking );
		BookingRepository::delete( (int) $booking['id'] );
	}

	/**
	 * Booking fields that are safe to return to the person holding a valid token.
	 *
	 * @param array $booking Booking row.
	 * @return array
	 */
	public static function to_public( array $booking ): array {
		$manual = 'manual' === Helper::get_settings()['approval'];

		return [
			'ref'             => (string) $booking['ref'],
			'status'          => (string) $booking['status'],
			'formId'          => (string) $booking['form_id'],
			'title'           => (string) $booking['event_title'],
			'start'           => Helper::ts( (string) $booking['start_utc'] ),
			'end'             => Helper::ts( (string) $booking['end_utc'] ),
			'timezone'        => (string) $booking['timezone'],
			'duration'        => (int) $booking['duration'],
			'name'            => (string) $booking['name'],
			'email'           => (string) $booking['email'],
			'phone'           => (string) ( $booking['phone'] ?? '' ),
			// In manual mode, moving a confirmed booking puts it back in the approval queue.
			'rescheduleNeedsApproval' => $manual && 'confirmed' === $booking['status'],
		];
	}

	/**
	 * A requested start must be a real slot of the config and must not overlap
	 * a held booking (own row excluded).
	 *
	 * @param array $config     Verified config.
	 * @param int   $start      Requested slot start (unix).
	 * @param int   $exclude_id Booking to ignore (the one being moved), or 0.
	 * @return array{start:int,end:int,minutes:int}|WP_Error
	 */
	private static function open_slot( array $config, int $start, int $exclude_id ) {
		if ( '' === $config['formId'] ) {
			return new WP_Error( 'gsbf_invalid_form', __( 'This booking form is not set up correctly.', 'gembooking-simple-booking-form' ), [ 'status' => 400 ] );
		}

		$slot = SlotCalendar::find_slot( $config, $start, new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) ) );
		if ( ! $slot ) {
			return new WP_Error( 'gsbf_invalid_slot', __( 'That time is not available. Please choose another time.', 'gembooking-simple-booking-form' ), [ 'status' => 400 ] );
		}

		$held = array_filter(
			BookingRepository::held_ranges( $config['formId'], $slot['start'], $slot['end'] ),
			static function ( array $range ) use ( $exclude_id ): bool {
				return $range['id'] !== $exclude_id;
			}
		);
		if ( SlotCalendar::overlaps( $slot['start'], $slot['end'], $held ) ) {
			return new WP_Error( 'gsbf_slot_taken', __( 'Sorry, that time was just taken. Please choose another time.', 'gembooking-simple-booking-form' ), [ 'status' => 409 ] );
		}

		return $slot;
	}

	/**
	 * @param array $config Verified config.
	 * @param array $input  Raw name/email/phone.
	 * @return array{name:string,email:string,phone:string}|WP_Error
	 */
	private static function validate_customer( array $config, array $input ) {
		$name  = trim( sanitize_text_field( (string) ( $input['name'] ?? '' ) ) );
		$email = sanitize_email( (string) ( $input['email'] ?? '' ) );
		$phone = trim( preg_replace( '/[^0-9+\-\s().]/', '', (string) ( $input['phone'] ?? '' ) ) );

		if ( '' === $name ) {
			return new WP_Error( 'gsbf_invalid_name', __( 'Enter your name.', 'gembooking-simple-booking-form' ), [ 'status' => 400 ] );
		}
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'gsbf_invalid_email', __( 'Enter a valid email address.', 'gembooking-simple-booking-form' ), [ 'status' => 400 ] );
		}
		if ( $config['phoneRequired'] && '' === $phone ) {
			return new WP_Error( 'gsbf_invalid_phone', __( 'Enter your phone number.', 'gembooking-simple-booking-form' ), [ 'status' => 400 ] );
		}

		return [
			'name'  => function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 190 ) : substr( $name, 0, 190 ),
			'email' => substr( $email, 0, 190 ),
			'phone' => substr( $phone, 0, 64 ),
		];
	}

	private static function not_pending(): WP_Error {
		return new WP_Error(
			'gsbf_not_pending',
			__( 'This request was already handled, cancelled or has expired.', 'gembooking-simple-booking-form' ),
			[ 'status' => 409 ]
		);
	}
}

<?php

namespace GembookingSimpleBookingForm\Privacy;

use GembookingSimpleBookingForm\Classes\BookingRepository;
use GembookingSimpleBookingForm\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Exporter {

	const PER_PAGE = 50;

	/**
	 * @param string $email_address Email of the person.
	 * @param int    $page          1-based page.
	 * @return array
	 */
	public static function export( $email_address, $page = 1 ): array {
		$page = max( 1, (int) $page );
		$rows = BookingRepository::find_by_email( sanitize_email( (string) $email_address ), $page, self::PER_PAGE );
		$data = [];

		foreach ( $rows as $row ) {
			$tz     = new \DateTimeZone( $row['timezone'] );
			$fields = [
				[
					'name'  => __( 'Booking ref', 'gembooking-simple-booking-form' ),
					'value' => $row['ref'],
				],
				[
					'name'  => __( 'Event', 'gembooking-simple-booking-form' ),
					'value' => $row['event_title'],
				],
				[
					'name'  => __( 'Start', 'gembooking-simple-booking-form' ),
					'value' => wp_date( 'Y-m-d H:i', Helper::ts( $row['start_utc'] ), $tz ) . ' ' . $row['timezone'],
				],
				[
					'name'  => __( 'Status', 'gembooking-simple-booking-form' ),
					'value' => $row['status'],
				],
				[
					'name'  => __( 'Name', 'gembooking-simple-booking-form' ),
					'value' => $row['name'],
				],
				[
					'name'  => __( 'Email', 'gembooking-simple-booking-form' ),
					'value' => $row['email'],
				],
			];
			if ( ! empty( $row['phone'] ) ) {
				$fields[] = [
					'name'  => __( 'Phone', 'gembooking-simple-booking-form' ),
					'value' => $row['phone'],
				];
			}

			$data[] = [
				'group_id'    => 'gsbf-bookings',
				'group_label' => __( 'Bookings', 'gembooking-simple-booking-form' ),
				'item_id'     => 'gsbf-booking-' . (int) $row['id'],
				'data'        => $fields,
			];
		}

		return [
			'data' => $data,
			'done' => count( $rows ) < self::PER_PAGE,
		];
	}
}

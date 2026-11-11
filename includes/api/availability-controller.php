<?php

namespace GembookingSimpleBookingForm\API;

use GembookingSimpleBookingForm\Classes\BookingRepository;
use GembookingSimpleBookingForm\Classes\RequestConfig;
use GembookingSimpleBookingForm\Classes\SlotCalendar;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public availability for a signed block config.
 *
 *   GET /availability?month=YYYY-MM  => which dates still have a free slot
 *   GET /availability?date=YYYY-MM-DD => that day's slots, flagged free/taken
 */
class AvailabilityController {

	public function register_routes(): void {
		register_rest_route(
			GSBF_REST_NAMESPACE,
			'/availability',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_availability' ],
				'permission_callback' => '__return_true', // Public; the signed config is the gate.
				'args'                => RequestConfig::args() + [
					'month' => [
						'type'    => 'string',
						'pattern' => '^\d{4}-\d{2}$',
					],
					'date'  => [
						'type'    => 'string',
						'pattern' => '^\d{4}-\d{2}-\d{2}$',
					],
				],
			]
		);
	}

	public function get_availability( WP_REST_Request $request ) {
		$verified = RequestConfig::verify( $request );
		if ( is_wp_error( $verified ) ) {
			return $verified;
		}

		$config = $verified['config'];
		$now    = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
		$tz     = new \DateTimeZone( $config['timezone'] );

		if ( $request->get_param( 'date' ) ) {
			$date  = (string) $request->get_param( 'date' );
			$slots = SlotCalendar::slots_for_date( $config, $date, $now );
			$data  = [
				'date'     => $date,
				'timezone' => $config['timezone'],
				'slots'    => $this->flag_slots( $config, $slots ),
			];
		} elseif ( $request->get_param( 'month' ) ) {
			$month = (string) $request->get_param( 'month' );
			$data  = [
				'month'    => $month,
				'timezone' => $config['timezone'],
				'days'     => $this->month_days( $config, $month, $now, $tz ),
			];
		} else {
			return new WP_Error( 'gsbf_missing_range', __( 'Provide a month or a date.', 'gembooking-simple-booking-form' ), [ 'status' => 400 ] );
		}

		$response = new WP_REST_Response( $data );
		$response->set_headers( [ 'Cache-Control' => 'no-store' ] );

		return $response;
	}

	/**
	 * Mark each slot free or taken against the held bookings of the form.
	 *
	 * @param array $config Config.
	 * @param array $slots  SlotCalendar slots.
	 * @return array
	 */
	private function flag_slots( array $config, array $slots ): array {
		if ( empty( $slots ) ) {
			return [];
		}

		$held = BookingRepository::held_ranges( $config['formId'], $slots[0]['start'], $slots[ count( $slots ) - 1 ]['end'] );
		$out  = [];

		foreach ( $slots as $slot ) {
			$out[] = [
				'start'     => $slot['start'],
				'minutes'   => $slot['minutes'],
				'available' => ! SlotCalendar::overlaps( $slot['start'], $slot['end'], $held ),
			];
		}

		return $out;
	}

	/**
	 * Open dates of a month with whether at least one slot is still free.
	 *
	 * @param array              $config Config.
	 * @param string             $month  Y-m.
	 * @param \DateTimeImmutable $now    Current moment.
	 * @param \DateTimeZone      $tz     Form timezone.
	 * @return array<int, array{date:string,available:bool}>
	 */
	private function month_days( array $config, string $month, \DateTimeImmutable $now, \DateTimeZone $tz ): array {
		$open = SlotCalendar::open_dates( $config, $month, $now );
		if ( empty( $open ) ) {
			return [];
		}

		$from = ( new \DateTimeImmutable( $open[0] . ' 00:00:00', $tz ) )->getTimestamp();
		$to   = ( new \DateTimeImmutable( $open[ count( $open ) - 1 ] . ' 23:59:59', $tz ) )->getTimestamp();
		$held = BookingRepository::held_ranges( $config['formId'], $from, $to );
		$days = [];

		foreach ( $open as $date ) {
			$free = false;
			foreach ( SlotCalendar::slots_for_date( $config, $date, $now ) as $slot ) {
				if ( ! SlotCalendar::overlaps( $slot['start'], $slot['end'], $held ) ) {
					$free = true;
					break;
				}
			}
			$days[] = [
				'date'      => $date,
				'available' => $free,
			];
		}

		return $days;
	}
}

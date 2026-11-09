<?php

namespace GembookingSimpleBookingForm\Classes;

use GembookingSimpleBookingForm\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hourly wp-cron job: a `pending` request the admin has not decided within
 * 48 hours is released. The customer gets a short email, then the row is
 * deleted so the slot can be booked again. `confirmed` rows are never touched.
 *
 * The age counts from `requested_at` (the start of the current request, reset
 * by a reschedule), not from when the booking was first created.
 */
class CleanupExpiredBookings {

	/** Cron action name. */
	const HOOK = 'gsbf/cleanup/expired_pending';

	/** Max rows handled per tick, to stay clear of PHP time limits. */
	const BATCH_LIMIT = 500;

	public static function init(): void {
		$self = new self();

		add_action( 'init', [ $self, 'ensure_scheduled' ] );
		add_action( self::HOOK, [ $self, 'run' ] );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::HOOK );
		}
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::HOOK );
	}

	public function ensure_scheduled(): void {
		self::schedule();
	}

	public function run(): void {
		$cutoff = time() - Helper::PENDING_EXPIRY_HOURS * HOUR_IN_SECONDS;

		foreach ( BookingRepository::expired_pending( $cutoff, self::BATCH_LIMIT ) as $booking ) {
			BookingService::expire( $booking );
		}
	}
}

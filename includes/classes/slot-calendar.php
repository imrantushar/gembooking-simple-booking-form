<?php

namespace GembookingSimpleBookingForm\Classes;

/**
 * Pure availability rules: block config + date => bookable slots.
 *
 * Deliberately free of WordPress calls so it can be unit-tested in isolation.
 * Mirrors the rules of the design's `avail()` / `slotList()` and of the JS
 * port in dev_gsbf/utils/slots.js (keep the two in step).
 *
 * Every "local" value is in the form's own IANA timezone; every returned
 * timestamp is a Unix timestamp (UTC based).
 */
class SlotCalendar {

	/**
	 * Is this local calendar date open for booking at all (ignoring existing bookings)?
	 *
	 * @param array              $config Sanitized block config.
	 * @param string             $date   Local date, Y-m-d.
	 * @param \DateTimeImmutable $now    Current moment.
	 * @return bool
	 */
	public static function is_date_open( array $config, string $date, \DateTimeImmutable $now ): bool {
		$tz    = new \DateTimeZone( $config['timezone'] );
		$day   = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $tz );
		$today = $now->setTimezone( $tz )->setTime( 0, 0, 0 );

		if ( false === $day || $day->format( 'Y-m-d' ) !== $date ) {
			return false;
		}

		// Whole calendar days between today and the date, in the form timezone.
		$diff = (int) $today->diff( $day )->format( '%r%a' );

		return $diff >= (int) $config['notice']
			&& $diff <= (int) $config['maxAdvance']
			&& in_array( (int) $day->format( 'w' ), array_map( 'intval', $config['days'] ), true );
	}

	/**
	 * Every slot of a local date, in order. Empty when the date is not open.
	 *
	 * Each slot: [ 'start' => int unix, 'end' => int unix, 'minutes' => int minutes after local midnight ].
	 * Slots that already started are dropped.
	 *
	 * @param array              $config Sanitized block config.
	 * @param string             $date   Local date, Y-m-d.
	 * @param \DateTimeImmutable $now    Current moment.
	 * @return array<int, array{start:int,end:int,minutes:int}>
	 */
	public static function slots_for_date( array $config, string $date, \DateTimeImmutable $now ): array {
		if ( ! self::is_date_open( $config, $date, $now ) ) {
			return [];
		}

		$tz       = new \DateTimeZone( $config['timezone'] );
		$duration = (int) $config['duration'];
		$step     = $duration + (int) $config['buffer'];
		$last     = (int) $config['endHour'] * 60;
		$slots    = [];

		for ( $m = (int) $config['startHour'] * 60; $m + $duration <= $last; $m += $step ) {
			// Build from wall-clock parts (not "midnight + N minutes") so a DST
			// change inside the day cannot shift the local start time.
			$start = new \DateTimeImmutable(
				sprintf( '%s %02d:%02d:00', $date, intdiv( $m, 60 ), $m % 60 ),
				$tz
			);
			$end   = $start->modify( '+' . $duration . ' minutes' );

			if ( $start->getTimestamp() <= $now->getTimestamp() ) {
				continue;
			}

			$slots[] = [
				'start'   => $start->getTimestamp(),
				'end'     => $end->getTimestamp(),
				'minutes' => $m,
			];
		}

		return $slots;
	}

	/**
	 * The open local dates (Y-m-d) of a month.
	 *
	 * @param array              $config Sanitized block config.
	 * @param string             $month  Y-m.
	 * @param \DateTimeImmutable $now    Current moment.
	 * @return string[]
	 */
	public static function open_dates( array $config, string $month, \DateTimeImmutable $now ): array {
		$tz    = new \DateTimeZone( $config['timezone'] );
		$first = \DateTimeImmutable::createFromFormat( '!Y-m-d', $month . '-01', $tz );
		if ( false === $first || $first->format( 'Y-m' ) !== $month ) {
			return [];
		}

		$dates = [];
		$days  = (int) $first->format( 't' );
		for ( $d = 1; $d <= $days; $d++ ) {
			$date = sprintf( '%s-%02d', $month, $d );
			if ( self::is_date_open( $config, $date, $now ) ) {
				$dates[] = $date;
			}
		}

		return $dates;
	}

	/**
	 * Find the slot that starts exactly at the given Unix timestamp.
	 *
	 * A booking request is only valid for a timestamp that is a real slot of
	 * the config, never an arbitrary moment.
	 *
	 * @param array              $config Sanitized block config.
	 * @param int                $start  Unix timestamp.
	 * @param \DateTimeImmutable $now    Current moment.
	 * @return array{start:int,end:int,minutes:int}|null
	 */
	public static function find_slot( array $config, int $start, \DateTimeImmutable $now ): ?array {
		$tz   = new \DateTimeZone( $config['timezone'] );
		$date = ( new \DateTimeImmutable( '@' . $start ) )->setTimezone( $tz )->format( 'Y-m-d' );

		foreach ( self::slots_for_date( $config, $date, $now ) as $slot ) {
			if ( $slot['start'] === $start ) {
				return $slot;
			}
		}

		return null;
	}

	/**
	 * Does [start, end) overlap any of the given held ranges?
	 *
	 * @param int                                  $start  Unix start.
	 * @param int                                  $end    Unix end.
	 * @param array<int, array{start:int,end:int}> $ranges Held ranges.
	 * @return bool
	 */
	public static function overlaps( int $start, int $end, array $ranges ): bool {
		foreach ( $ranges as $range ) {
			if ( $start < $range['end'] && $end > $range['start'] ) {
				return true;
			}
		}

		return false;
	}
}

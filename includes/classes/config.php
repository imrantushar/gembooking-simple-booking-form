<?php

namespace GembookingSimpleBookingForm\Classes;

/**
 * The block's configuration (its attributes), sanitized and clamped.
 *
 * The same limits are mirrored in the block editor (dev_gsbf/utils/config.js).
 * A config only reaches the booking logic after passing through sanitize()
 * (either at render time or after ConfigSigner::verify()).
 */
class Config {

	const LAYOUTS = [ 'step', 'single' ];

	/**
	 * Default attribute values (from the design).
	 *
	 * @return array
	 */
	public static function defaults(): array {
		return [
			'formId'        => '',
			'title'         => '30-minute consultation',
			'layout'        => 'step',
			'duration'      => 30,
			'days'          => [ 1, 2, 3, 4, 5 ],
			'startHour'     => 9,
			'endHour'       => 17,
			'notice'        => 1,
			'buffer'        => 0,
			'maxAdvance'    => 60,
			'timezone'      => 'UTC',
			'accent'        => '#141A24',
			'phoneRequired' => false,
		];
	}

	/**
	 * Sanitize and clamp raw attributes.
	 *
	 * @param array $raw Raw attributes (block attributes or decoded JSON).
	 * @return array
	 */
	public static function sanitize( array $raw ): array {
		$d = self::defaults();

		$form_id = preg_replace( '/[^A-Za-z0-9\-]/', '', (string) ( $raw['formId'] ?? '' ) );
		$form_id = substr( (string) $form_id, 0, 64 );

		$layout = (string) ( $raw['layout'] ?? $d['layout'] );

		// Every numeric option is a whole number; anything else (30.5, "abc") falls back
		// to the default instead of being truncated or rounded into a different value.
		$duration = (int) round( self::whole( $raw['duration'] ?? null, $d['duration'] ) / 5 ) * 5;
		$duration = min( 480, max( 5, $duration ) );

		$days = [];
		foreach ( (array) ( $raw['days'] ?? $d['days'] ) as $day ) {
			$day = self::whole( $day, -1 );
			if ( $day >= 0 && $day <= 6 ) {
				$days[ $day ] = $day;
			}
		}
		$days = array_values( $days );
		sort( $days );

		$start = min( 23, max( 0, self::whole( $raw['startHour'] ?? null, $d['startHour'] ) ) );
		$end   = min( 24, max( $start + 1, self::whole( $raw['endHour'] ?? null, $d['endHour'] ) ) );

		// Buffer is any whole number of minutes (the "Custom" option), not only multiples of 5.
		$buffer = min( 240, max( 0, self::whole( $raw['buffer'] ?? null, $d['buffer'] ) ) );

		$timezone = (string) ( $raw['timezone'] ?? '' );
		if ( ! in_array( $timezone, \DateTimeZone::listIdentifiers(), true ) ) {
			$timezone = 'UTC';
		}

		$accent = sanitize_hex_color( (string) ( $raw['accent'] ?? '' ) );

		return [
			'formId'        => $form_id,
			'title'         => self::truncate( sanitize_text_field( (string) ( $raw['title'] ?? $d['title'] ) ), 120 ),
			'layout'        => in_array( $layout, self::LAYOUTS, true ) ? $layout : $d['layout'],
			'duration'      => $duration,
			'days'          => $days,
			'startHour'     => $start,
			'endHour'       => $end,
			'notice'        => min( 30, max( 0, self::whole( $raw['notice'] ?? null, $d['notice'] ) ) ),
			'buffer'        => $buffer,
			'maxAdvance'    => min( 365, max( 1, self::whole( $raw['maxAdvance'] ?? null, $d['maxAdvance'] ) ) ),
			'timezone'      => $timezone,
			'accent'        => $accent ? strtoupper( $accent ) : $d['accent'],
			'phoneRequired' => ! empty( $raw['phoneRequired'] ) && 'false' !== $raw['phoneRequired'],
		];
	}

	/**
	 * A whole number, or the default.
	 *
	 * Accepts ints, floats that are exactly whole (30.0) and integer-looking strings
	 * ("30"). Floats with a fraction (30.5), empty values and text are not whole
	 * numbers and give the default.
	 *
	 * @param mixed $value   Raw value.
	 * @param int   $default Value to use when $value is not a whole number.
	 * @return int
	 */
	private static function whole( $value, int $default ): int {
		if ( is_int( $value ) ) {
			return $value;
		}
		if ( is_float( $value ) && is_finite( $value ) && floor( $value ) === $value ) {
			return (int) $value;
		}
		if ( is_string( $value ) && preg_match( '/^-?\d+$/', trim( $value ) ) ) {
			return (int) trim( $value );
		}

		return $default;
	}

	private static function truncate( string $value, int $length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
	}
}

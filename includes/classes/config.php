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
			'message'       => 'A confirmation email is on its way. We look forward to speaking with you.',
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

		$duration = (int) round( (int) ( $raw['duration'] ?? $d['duration'] ) / 5 ) * 5;
		$duration = min( 480, max( 5, $duration ) );

		$days = [];
		foreach ( (array) ( $raw['days'] ?? $d['days'] ) as $day ) {
			$day = (int) $day;
			if ( $day >= 0 && $day <= 6 ) {
				$days[ $day ] = $day;
			}
		}
		$days = array_values( $days );
		sort( $days );

		$start = min( 23, max( 0, (int) ( $raw['startHour'] ?? $d['startHour'] ) ) );
		$end   = min( 24, max( $start + 1, (int) ( $raw['endHour'] ?? $d['endHour'] ) ) );

		$buffer = (int) round( (int) ( $raw['buffer'] ?? $d['buffer'] ) / 5 ) * 5;

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
			'notice'        => min( 30, max( 0, (int) ( $raw['notice'] ?? $d['notice'] ) ) ),
			'buffer'        => min( 120, max( 0, $buffer ) ),
			'maxAdvance'    => min( 365, max( 1, (int) ( $raw['maxAdvance'] ?? $d['maxAdvance'] ) ) ),
			'timezone'      => $timezone,
			'accent'        => $accent ? strtoupper( $accent ) : $d['accent'],
			'phoneRequired' => ! empty( $raw['phoneRequired'] ) && 'false' !== $raw['phoneRequired'],
			'message'       => self::truncate( sanitize_textarea_field( (string) ( $raw['message'] ?? $d['message'] ) ), 400 ),
		];
	}

	private static function truncate( string $value, int $length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
	}
}

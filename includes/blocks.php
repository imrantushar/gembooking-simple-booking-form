<?php
/**
 * Block registration.
 *
 * @package GembookingSimpleBookingForm
 */

namespace GembookingSimpleBookingForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blocks {

	/** Same category slug GemBooking uses; added only when it is not there yet. */
	const CATEGORY = 'gembooking';

	public static function init(): void {
		$self = new self();
		add_action( 'init', [ $self, 'register' ] );
		add_filter( 'block_categories_all', [ $self, 'category' ], 10, 1 );
	}

	/**
	 * A "GemBooking" inserter category, unless another plugin already added it.
	 *
	 * @param array $categories Block categories.
	 * @return array
	 */
	public function category( $categories ) {
		if ( ! is_array( $categories ) ) {
			return $categories;
		}
		foreach ( $categories as $existing ) {
			if ( isset( $existing['slug'] ) && self::CATEGORY === $existing['slug'] ) {
				return $categories;
			}
		}
		array_unshift(
			$categories,
			[
				'slug'  => self::CATEGORY,
				'title' => __( 'GemBooking', 'gembooking-simple-booking-form' ),
			]
		);

		return $categories;
	}

	public function register(): void {
		register_block_type(
			GSBF_ROOT_DIR_PATH . 'blocks/booking-form',
			[ 'render_callback' => [ $this, 'render_booking_form' ] ]
		);
	}

	/**
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_booking_form( $attributes ): string {
		return Render\Form::html( is_array( $attributes ) ? $attributes : [] );
	}
}

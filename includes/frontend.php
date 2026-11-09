<?php

namespace GembookingSimpleBookingForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end hooks.
 */
class Frontend {

	public static function init(): void {
		Frontend\ActionPage::init();
	}
}

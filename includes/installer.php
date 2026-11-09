<?php

namespace GembookingSimpleBookingForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs on plugin activation.
 */
class Installer {

	public static function init(): void {
		Database::create_initial_custom_table();

		// Seed the settings only when nothing is saved yet; never overwrite.
		if ( false === get_option( GSBF_SETTINGS_NAME, false ) ) {
			add_option( GSBF_SETTINGS_NAME, Helper::default_settings(), '', false );
		}

		Classes\CleanupExpiredBookings::schedule();
	}
}

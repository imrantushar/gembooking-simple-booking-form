<?php

namespace GembookingSimpleBookingForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database schema management.
 */
class Database {

	/** Bump when the table definition changes. */
	const DB_VERSION        = '1';
	const DB_VERSION_OPTION = 'gsbf_db_version';

	public static function init(): void {
		$self = new self();
		// An update replaces files without firing the activation hook, so
		// compare the stored schema version on every load (one option read).
		add_action( 'init', [ $self, 'maybe_upgrade' ], 1 );
	}

	/**
	 * Create/upgrade the tables when the stored schema version is behind.
	 */
	public function maybe_upgrade(): void {
		if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
			self::create_initial_custom_table();
		}
	}

	/**
	 * Create (or update) every custom table. Safe to call repeatedly.
	 */
	public static function create_initial_custom_table(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		Database\CreateBookingsTable::up( $wpdb->prefix, $charset_collate );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
	}
}

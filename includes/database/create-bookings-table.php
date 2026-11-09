<?php

namespace GembookingSimpleBookingForm\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bookings table.
 *
 * Double booking is prevented by the UNIQUE `slot_key` (sha256 of form id +
 * slot start): the INSERT itself is the lock, so no advisory lock is needed.
 * A declined, cancelled or expired booking row is deleted, which frees the key.
 *
 * Note: NO SQL `-- comment` lines inside the CREATE TABLE statement -
 * dbDelta's parser fails on them silently.
 */
class CreateBookingsTable {

	public static function table( string $prefix ): string {
		return $prefix . GSBF_DB_PREFIX . '_bookings';
	}

	public static function up( string $prefix, string $charset_collate ): void {
		$table = self::table( $prefix );

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			ref VARCHAR(16) NOT NULL,
			form_id VARCHAR(64) NOT NULL,
			page_url TEXT NULL,
			event_title VARCHAR(255) NOT NULL DEFAULT '',
			accent VARCHAR(7) NOT NULL DEFAULT '',
			start_utc DATETIME NOT NULL,
			end_utc DATETIME NOT NULL,
			timezone VARCHAR(64) NOT NULL,
			duration SMALLINT UNSIGNED NOT NULL,
			name VARCHAR(190) NOT NULL,
			email VARCHAR(190) NOT NULL,
			phone VARCHAR(64) NULL,
			status VARCHAR(16) NOT NULL DEFAULT 'pending',
			slot_key CHAR(64) NOT NULL,
			ip_hash CHAR(64) NULL,
			requested_at DATETIME NOT NULL,
			rescheduled_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_ref (ref),
			UNIQUE KEY uq_slot (slot_key),
			KEY idx_form_time (form_id, start_utc),
			KEY idx_email (email),
			KEY idx_status_requested (status, requested_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}

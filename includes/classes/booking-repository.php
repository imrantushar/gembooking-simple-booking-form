<?php

namespace GembookingSimpleBookingForm\Classes;

use GembookingSimpleBookingForm\Database\CreateBookingsTable;
use GembookingSimpleBookingForm\Helper;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// All queries here run against this plugin's own table, whose name comes from
// the table() helper (never from user input); every value is passed through
// $wpdb->prepare() or $wpdb->insert()/update().
// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

/**
 * The only place that talks to the bookings table.
 */
class BookingRepository {

	/** Alphabet for public refs: no 0/O/1/I so a code can be read aloud. */
	const REF_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

	public static function table(): string {
		global $wpdb;
		return CreateBookingsTable::table( $wpdb->prefix );
	}

	public static function slot_key( string $form_id, string $start_utc ): string {
		return hash( 'sha256', $form_id . '|' . $start_utc );
	}

	public static function generate_ref(): string {
		$ref = '';
		$max = strlen( self::REF_ALPHABET ) - 1;
		for ( $i = 0; $i < 6; $i++ ) {
			$ref .= self::REF_ALPHABET[ random_int( 0, $max ) ];
		}
		return 'GB-' . $ref;
	}

	/**
	 * Insert a booking. The UNIQUE slot_key makes this the double-booking lock.
	 *
	 * @param array $data Column => value (ref and slot_key are generated here).
	 * @return int|WP_Error New id, or WP_Error `gsbf_slot_taken` (409) when the slot is held.
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$now              = Helper::utc();
		$data['slot_key'] = self::slot_key( (string) $data['form_id'], (string) $data['start_utc'] );
		$data            += [
			'created_at'   => $now,
			'updated_at'   => $now,
			'requested_at' => $now,
		];

		$previous = $wpdb->suppress_errors( true );

		for ( $attempt = 0; $attempt < 5; $attempt++ ) {
			$data['ref'] = self::generate_ref();
			$inserted    = $wpdb->insert( self::table(), $data );

			if ( false !== $inserted ) {
				$wpdb->suppress_errors( $previous );
				return (int) $wpdb->insert_id;
			}

			$error = (string) $wpdb->last_error;
			if ( false !== stripos( $error, 'uq_slot' ) ) {
				$wpdb->suppress_errors( $previous );
				return self::slot_taken();
			}
			if ( false === stripos( $error, 'uq_ref' ) ) {
				break; // Not a ref collision: do not retry.
			}
		}

		$wpdb->suppress_errors( $previous );

		return new WP_Error( 'gsbf_db_error', __( 'Could not save the booking. Please try again.', 'gembooking-simple-booking-form' ), [ 'status' => 500 ] );
	}

	/**
	 * Move a booking to another slot (reschedule). The old slot is freed by the
	 * same UPDATE that claims the new one.
	 *
	 * @param int   $id     Booking id.
	 * @param array $fields Columns to change; start_utc and end_utc are required.
	 * @return true|WP_Error
	 */
	public static function update_slot( int $id, array $fields ) {
		global $wpdb;

		$booking = self::find( $id );
		if ( ! $booking ) {
			return new WP_Error( 'gsbf_not_found', __( 'Booking not found.', 'gembooking-simple-booking-form' ), [ 'status' => 404 ] );
		}

		$fields['slot_key']   = self::slot_key( (string) $booking['form_id'], (string) $fields['start_utc'] );
		$fields['updated_at'] = Helper::utc();

		$previous = $wpdb->suppress_errors( true );
		$updated  = $wpdb->update( self::table(), $fields, [ 'id' => $id ] );
		$error    = (string) $wpdb->last_error;
		$wpdb->suppress_errors( $previous );

		if ( false === $updated ) {
			return false !== stripos( $error, 'uq_slot' )
				? self::slot_taken()
				: new WP_Error( 'gsbf_db_error', __( 'Could not save the booking. Please try again.', 'gembooking-simple-booking-form' ), [ 'status' => 500 ] );
		}

		return true;
	}

	public static function update( int $id, array $fields ): bool {
		global $wpdb;

		$fields['updated_at'] = Helper::utc();

		return false !== $wpdb->update( self::table(), $fields, [ 'id' => $id ] );
	}

	public static function find( int $id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	public static function delete( int $id ): bool {
		global $wpdb;

		return false !== $wpdb->delete( self::table(), [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Held ranges of one form that touch [from, to).
	 *
	 * @param string $form_id Form id.
	 * @param int    $from    Unix start of the window.
	 * @param int    $to      Unix end of the window.
	 * @return array<int, array{id:int,start:int,end:int}>
	 */
	public static function held_ranges( string $form_id, int $from, int $to ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, start_utc, end_utc FROM ' . self::table() . ' WHERE form_id = %s AND start_utc < %s AND end_utc > %s',
				$form_id,
				Helper::utc( $to ),
				Helper::utc( $from )
			),
			ARRAY_A
		);

		$ranges = [];
		foreach ( (array) $rows as $row ) {
			$ranges[] = [
				'id'    => (int) $row['id'],
				'start' => Helper::ts( $row['start_utc'] ),
				'end'   => Helper::ts( $row['end_utc'] ),
			];
		}

		return $ranges;
	}

	/**
	 * Pending requests whose current request is older than the cutoff.
	 *
	 * @param int $older_than Unix cutoff.
	 * @param int $limit      Batch size.
	 * @return array[]
	 */
	public static function expired_pending( int $older_than, int $limit = 500 ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . " WHERE status = 'pending' AND requested_at < %s ORDER BY requested_at ASC LIMIT %d",
				Helper::utc( $older_than ),
				$limit
			),
			ARRAY_A
		);

		return (array) $rows;
	}

	/**
	 * Bookings of one email address (privacy export), paged.
	 *
	 * @param string $email    Email address.
	 * @param int    $page     1-based page.
	 * @param int    $per_page Page size.
	 * @return array[]
	 */
	public static function find_by_email( string $email, int $page = 1, int $per_page = 50 ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . ' WHERE email = %s ORDER BY id ASC LIMIT %d OFFSET %d',
				$email,
				$per_page,
				max( 0, ( $page - 1 ) * $per_page )
			),
			ARRAY_A
		);

		return (array) $rows;
	}

	/**
	 * Anonymise personal data of an email address but keep the slot row, so
	 * double-booking protection of future slots is not broken.
	 *
	 * @param string $email Email address.
	 * @return int Rows changed.
	 */
	public static function anonymize_by_email( string $email ): int {
		global $wpdb;

		$changed = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::table() . " SET name = %s, email = CONCAT('erased-', id, '@invalid.invalid'), phone = NULL, ip_hash = NULL, updated_at = %s WHERE email = %s",
				__( 'Erased', 'gembooking-simple-booking-form' ),
				Helper::utc(),
				$email
			)
		);

		return (int) $changed;
	}

	private static function slot_taken(): WP_Error {
		return new WP_Error(
			'gsbf_slot_taken',
			__( 'Sorry, that time was just taken. Please choose another time.', 'gembooking-simple-booking-form' ),
			[ 'status' => 409 ]
		);
	}
}

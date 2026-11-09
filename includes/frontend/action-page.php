<?php

namespace GembookingSimpleBookingForm\Frontend;

use GembookingSimpleBookingForm\Classes\BookingActionUrl;
use GembookingSimpleBookingForm\Classes\BookingRepository;
use GembookingSimpleBookingForm\Classes\BookingService;
use GembookingSimpleBookingForm\Classes\BookingToken;
use GembookingSimpleBookingForm\Classes\MailTemplate;
use GembookingSimpleBookingForm\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Landing page for the email links that change something: confirm, decline
 * (admin) and cancel (customer).
 *
 * A GET only shows a summary and a button; the change happens on the POST of
 * that button. Mail scanners that pre-fetch links therefore cannot approve,
 * decline or cancel a booking by accident.
 *
 * The page lives on the booking page's own URL (`page_url`) so it needs no
 * special route; it is intercepted early on `template_redirect`.
 */
class ActionPage {

	const ACTIONS = [ 'confirm', 'decline', 'cancel' ];

	public static function init(): void {
		$self = new self();
		add_action( 'template_redirect', [ $self, 'maybe_render' ], 1 );
	}

	public function maybe_render(): void {
		// The signed token is the authorisation, so there is no nonce here.
		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! isset( $_GET[ BookingActionUrl::QUERY_ACTION ] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_GET[ BookingActionUrl::QUERY_ACTION ] ) );
		if ( ! in_array( $action, self::ACTIONS, true ) ) {
			return; // view / reschedule are handled by the booking form itself.
		}

		$id    = isset( $_GET[ BookingActionUrl::QUERY_BOOKING ] ) ? absint( $_GET[ BookingActionUrl::QUERY_BOOKING ] ) : 0;
		$token = isset( $_GET[ BookingActionUrl::QUERY_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_GET[ BookingActionUrl::QUERY_TOKEN ] ) ) : '';
		$post  = isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['gsbf_do'] );
		// phpcs:enable WordPress.Security.NonceVerification

		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow' );

		if ( ! BookingToken::verify( $id, $token, $action ) ) {
			$this->render( 'error', $action, null, __( 'This link is invalid or has expired.', 'gembooking-simple-booking-form' ), 403 );
		}

		$booking = BookingRepository::find( $id );

		if ( ! $booking || ( 'cancel' !== $action && 'pending' !== $booking['status'] ) ) {
			$message = 'cancel' === $action
				? __( 'This booking no longer exists. It may already be cancelled.', 'gembooking-simple-booking-form' )
				: __( 'This request was already handled, cancelled or has expired. Nothing more to do.', 'gembooking-simple-booking-form' );
			$this->render( 'info', $action, $booking, $message, 200 );
		}

		if ( $post ) {
			$result = $this->execute( $action, $id );
			if ( is_wp_error( $result ) ) {
				$this->render( 'info', $action, $booking, $result->get_error_message(), 200 );
			}
			$this->render( 'done', $action, $booking, $this->done_message( $action ), 200 );
		}

		$this->render( 'confirm', $action, $booking, '', 200 );
	}

	/**
	 * @param string $action confirm|decline|cancel.
	 * @param int    $id     Booking id.
	 * @return array|\WP_Error
	 */
	private function execute( string $action, int $id ) {
		switch ( $action ) {
			case 'confirm':
				return BookingService::confirm( $id );
			case 'decline':
				return BookingService::decline( $id );
			default:
				return BookingService::cancel( $id );
		}
	}

	private function done_message( string $action ): string {
		switch ( $action ) {
			case 'confirm':
				return __( 'Booking confirmed. The customer has been emailed.', 'gembooking-simple-booking-form' );
			case 'decline':
				return __( 'Booking declined. The customer has been emailed and the time is free again.', 'gembooking-simple-booking-form' );
			default:
				return __( 'Your booking has been cancelled. We emailed you a confirmation.', 'gembooking-simple-booking-form' );
		}
	}

	/**
	 * Output the standalone page and stop.
	 *
	 * @param string     $state   confirm|done|info|error.
	 * @param string     $action  confirm|decline|cancel.
	 * @param array|null $row     Booking row, when known.
	 * @param string     $message Message to show (done/info/error).
	 * @param int        $status  HTTP status.
	 */
	private function render( string $state, string $action, ?array $row, string $message, int $status ): void {
		status_header( $status );

		// The template receives the *public* fields as $booking. The parameter above
		// is deliberately not called $booking: extract() would skip a name that is
		// already defined and the template would see the raw database row.
		$accent = $row && ! empty( $row['accent'] ) ? (string) $row['accent'] : '#141A24';
		$vars   = [
			'state'     => $state,
			'action'    => $action,
			'booking'   => $row ? BookingService::to_public( $row ) : null,
			'message'   => $message,
			'accent'    => Helper::sanitize_accent( $accent ),
			'font'      => MailTemplate::FONT_STACK,
			'site_name' => wp_specialchars_decode( (string) get_option( 'blogname', '' ), ENT_QUOTES ),
			'home_url'  => $row ? BookingActionUrl::page_url( $row ) : home_url( '/' ),
		];

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- scoped, explicit template variables.
		extract( $vars, EXTR_SKIP );
		include GSBF_TEMPLATES_DIR_PATH . 'action-page.php';
		exit;
	}
}

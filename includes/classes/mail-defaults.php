<?php

namespace GembookingSimpleBookingForm\Classes;

use GembookingSimpleBookingForm\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The fixed text of every email (the design removed per-field editing, so the
 * wording is not stored or editable in v1).
 *
 * The build() method returns a structure the MailTemplate renders:
 *   audience, subject, banner, tone, heading, body, rows[], buttons[], date{}
 */
class MailDefaults {

	/** Events and who receives them. */
	const AUDIENCE = [
		'admin_request'                => 'admin',
		'admin_new_confirmed'          => 'admin',
		'admin_reschedule_request'     => 'admin',
		'admin_rescheduled'            => 'admin',
		'admin_cancelled'              => 'admin',
		'customer_confirmed'           => 'customer',
		'customer_rescheduled'         => 'customer',
		'customer_reschedule_received' => 'customer',
		'customer_declined'            => 'customer',
		'customer_reschedule_declined' => 'customer',
		'customer_cancelled'           => 'customer',
		'customer_expired'             => 'customer',
		'customer_reschedule_expired'  => 'customer',
	];

	/**
	 * @param string $event   Key of self::AUDIENCE.
	 * @param array  $booking Booking row (or a sample).
	 * @param array  $ctx     Optional: `previous` => [start,end] unix, `sample` => true (links become "#").
	 * @return array
	 */
	public static function build( string $event, array $booking, array $ctx = [] ): array {
		$audience = self::AUDIENCE[ $event ] ?? 'customer';
		$tz       = new \DateTimeZone( (string) $booking['timezone'] );
		$start    = Helper::ts( (string) $booking['start_utc'] );
		$end      = Helper::ts( (string) $booking['end_utc'] );
		$sample   = ! empty( $ctx['sample'] );

		$vars = [
			'{event}' => (string) $booking['event_title'],
			'{name}'  => (string) $booking['name'],
			'{short}' => wp_date( 'M j, g:i A', $start, $tz ),
		];

		$text = [
			'subject' => '',
			'banner'  => '',
			'tone'    => 'info',
			'heading' => '{event}',
			'body'    => '',
		];

		switch ( $event ) {
			case 'admin_request':
				$text['subject'] = __( 'New booking request: {event} @ {short}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'You have a new booking request.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'warning';
				$text['heading'] = __( '{event} with {name}', 'gembooking-simple-booking-form' );
				$text['body']    = __( '{name} wants to book a time with you. Confirm or decline from this email. No WordPress login needed.', 'gembooking-simple-booking-form' );
				break;
			case 'admin_new_confirmed':
				$text['subject'] = __( 'New booking: {event} @ {short}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'A new booking was confirmed automatically.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'success';
				$text['heading'] = __( '{event} with {name}', 'gembooking-simple-booking-form' );
				$text['body']    = __( '{name} booked a time with you. It is already confirmed, nothing else to do.', 'gembooking-simple-booking-form' );
				break;
			case 'admin_reschedule_request':
				$text['subject'] = __( 'Reschedule request: {event} @ {short}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'A customer asked to move their booking.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'warning';
				$text['heading'] = __( '{event} with {name}', 'gembooking-simple-booking-form' );
				$text['body']    = __( '{name} asked to move this booking to the time below. Their previous time is already released. Confirm the new time, or decline to cancel the booking.', 'gembooking-simple-booking-form' );
				break;
			case 'admin_rescheduled':
				$text['subject'] = __( 'Booking rescheduled: {event} @ {short}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'A booking was rescheduled.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'success';
				$text['heading'] = __( '{event} with {name}', 'gembooking-simple-booking-form' );
				$text['body']    = __( '{name} moved their booking to a new time. It stays confirmed.', 'gembooking-simple-booking-form' );
				break;
			case 'admin_cancelled':
				$text['subject'] = __( 'Booking cancelled: {event} @ {short}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'A booking was cancelled.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'danger';
				$text['heading'] = __( '{event} with {name}', 'gembooking-simple-booking-form' );
				$text['body']    = __( '{name} cancelled this booking. The time is available again.', 'gembooking-simple-booking-form' );
				break;
			case 'customer_confirmed':
				$text['subject'] = __( 'Your booking is confirmed: {event}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'Your booking is confirmed.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'success';
				$text['body']    = __( 'Hi {name}, your booking is confirmed. We look forward to speaking with you. Reply to this email if you need to change anything.', 'gembooking-simple-booking-form' );
				break;
			case 'customer_rescheduled':
				$text['subject'] = __( 'Your booking has a new time: {event}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'Your booking was moved to the new time.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'success';
				$text['body']    = __( 'Hi {name}, your booking is now at the time below and it is confirmed.', 'gembooking-simple-booking-form' );
				break;
			case 'customer_reschedule_received':
				$text['subject'] = __( 'Reschedule request received: {event}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'Waiting for the host to approve the new time.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'warning';
				$text['body']    = __( 'Hi {name}, we received your request to move your booking. Your previous time has been released. If the host does not approve the new time within 48 hours, your booking will be cancelled.', 'gembooking-simple-booking-form' );
				break;
			case 'customer_declined':
				$text['subject'] = __( 'Your booking request was declined: {event}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'Your booking request was declined.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'danger';
				$text['body']    = __( 'Hi {name}, the host could not accept this time. The time slot is free again and you are welcome to choose another time.', 'gembooking-simple-booking-form' );
				break;
			case 'customer_reschedule_declined':
				$text['subject'] = __( 'Your reschedule request was not approved: {event}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'Your reschedule request was not approved.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'danger';
				$text['body']    = __( 'Hi {name}, the host did not approve the new time, so your booking has been cancelled and both times are released. You can book again at any time.', 'gembooking-simple-booking-form' );
				break;
			case 'customer_cancelled':
				$text['subject'] = __( 'Your booking was cancelled: {event}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'Your booking was cancelled.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'danger';
				$text['body']    = __( 'Hi {name}, your booking has been cancelled and the time is available again.', 'gembooking-simple-booking-form' );
				break;
			case 'customer_expired':
				$text['subject'] = __( 'Your booking request was not confirmed in time: {event}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'Your booking request was not confirmed in time.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'danger';
				$text['body']    = __( 'Hi {name}, the host did not confirm this request within 48 hours, so the time slot was released. You can choose another time.', 'gembooking-simple-booking-form' );
				break;
			case 'customer_reschedule_expired':
				$text['subject'] = __( 'Your reschedule request was not approved in time: {event}', 'gembooking-simple-booking-form' );
				$text['banner']  = __( 'Your reschedule request was not approved in time.', 'gembooking-simple-booking-form' );
				$text['tone']    = 'danger';
				$text['body']    = __( 'Hi {name}, the host did not approve the new time within 48 hours, so your booking has been cancelled and both times are released. You can book again at any time.', 'gembooking-simple-booking-form' );
				break;
		}

		$when = wp_date( 'D M j, Y', $start, $tz ) . ' ' . wp_date( 'g:i A', $start, $tz ) . ' – ' . wp_date( 'g:i A', $end, $tz ) . ' (' . $tz->getName() . ')';

		$who = [ (string) $booking['name'] ];
		if ( ! empty( $booking['phone'] ) ) {
			$who[] = (string) $booking['phone'];
		}
		$who[] = (string) $booking['email'];

		$rows = [ [ __( 'When', 'gembooking-simple-booking-form' ), $when ] ];
		if ( ! empty( $ctx['previous'] ) ) {
			$rows[] = [
				__( 'Previous', 'gembooking-simple-booking-form' ),
				wp_date( 'D M j, Y', (int) $ctx['previous']['start'], $tz ) . ' ' . wp_date( 'g:i A', (int) $ctx['previous']['start'], $tz ) . ' – ' . wp_date( 'g:i A', (int) $ctx['previous']['end'], $tz ),
			];
		}
		$rows[] = [ __( 'Who', 'gembooking-simple-booking-form' ), implode( ' · ', $who ) ];
		if ( ! empty( $booking['ref'] ) ) {
			$rows[] = [ __( 'Ref', 'gembooking-simple-booking-form' ), (string) $booking['ref'] ];
		}

		$link = static function ( string $action ) use ( $booking, $sample ): string {
			return $sample ? '#' : BookingActionUrl::url( $booking, $action );
		};
		$page = $sample ? '#' : BookingActionUrl::page_url( $booking );

		$buttons = [];
		switch ( $event ) {
			case 'admin_request':
			case 'admin_reschedule_request':
				$buttons = [
					[ __( 'Confirm booking', 'gembooking-simple-booking-form' ), $link( 'confirm' ), 'primary' ],
					[ __( 'Decline', 'gembooking-simple-booking-form' ), $link( 'decline' ), 'secondary' ],
				];
				break;
			case 'customer_confirmed':
			case 'customer_rescheduled':
			case 'customer_reschedule_received':
				$buttons = [
					[ __( 'View booking', 'gembooking-simple-booking-form' ), $link( 'view' ), 'primary' ],
					[ __( 'Reschedule', 'gembooking-simple-booking-form' ), $link( 'reschedule' ), 'secondary' ],
					[ __( 'Cancel', 'gembooking-simple-booking-form' ), $link( 'cancel' ), 'link' ],
				];
				break;
			case 'customer_declined':
			case 'customer_reschedule_declined':
			case 'customer_cancelled':
			case 'customer_expired':
			case 'customer_reschedule_expired':
				$buttons = [
					[ __( 'Book again', 'gembooking-simple-booking-form' ), $page, 'primary' ],
				];
				break;
		}

		return [
			'audience' => $audience,
			'subject'  => sanitize_text_field( strtr( $text['subject'], $vars ) ),
			'banner'   => strtr( $text['banner'], $vars ),
			'tone'     => $text['tone'],
			'heading'  => strtr( $text['heading'], $vars ),
			'body'     => strtr( $text['body'], $vars ),
			'rows'     => $rows,
			'buttons'  => array_map(
				static function ( array $b ): array {
					return [
						'label'   => $b[0],
						'url'     => $b[1],
						'variant' => $b[2],
					];
				},
				$buttons
			),
			'date'     => [
				'month'   => wp_date( 'M', $start, $tz ),
				'day'     => wp_date( 'j', $start, $tz ),
				'weekday' => wp_date( 'D', $start, $tz ),
			],
		];
	}
}

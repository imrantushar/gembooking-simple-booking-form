<?php
/**
 * End-to-end test against a real WordPress + database.
 *
 *   wp eval-file wp-content/plugins/gembooking-simple-booking-form/tests/php/integration.php
 *
 * Creates rows under the form id "itest-*" and removes them again. Emails are
 * captured (never sent). The approval setting is restored at the end.
 */

use GembookingSimpleBookingForm\Classes\BookingActionUrl;
use GembookingSimpleBookingForm\Classes\BookingRepository;
use GembookingSimpleBookingForm\Classes\BookingService;
use GembookingSimpleBookingForm\Classes\BookingToken;
use GembookingSimpleBookingForm\Classes\CleanupExpiredBookings;
use GembookingSimpleBookingForm\Classes\Config;
use GembookingSimpleBookingForm\Classes\ConfigSigner;
use GembookingSimpleBookingForm\Helper;

global $wpdb;
$table  = BookingRepository::table();
$GLOBALS['gsbf_pass']   = 0;
$GLOBALS['gsbf_failed'] = 0;

function it( string $name, $ok, string $detail = '' ): void {
	if ( $ok ) {
		$GLOBALS['gsbf_pass']++;
		echo "  ok   $name\n";
	} else {
		$GLOBALS['gsbf_failed']++;
		echo "  FAIL $name $detail\n";
	}
}

// Capture mail instead of sending it.
$mails = [];
add_filter(
	'pre_wp_mail',
	function ( $return, $atts ) use ( &$mails ) {
		$mails[] = $atts;
		return true;
	},
	10,
	2
);
$subjects = function () use ( &$mails ) {
	return array_map( fn( $m ) => $m['subject'], $mails );
};

$wpdb->query( "DELETE FROM {$table} WHERE form_id LIKE 'itest-%'" );
$original_settings = get_option( GSBF_SETTINGS_NAME );
update_option( GSBF_SETTINGS_NAME, array_merge( Helper::get_settings(), [ 'approval' => 'manual', 'adminTo' => 'admin@example.test' ] ), false );

$config = Config::sanitize(
	[
		'formId'   => 'itest-' . wp_generate_password( 6, false ),
		'timezone' => 'Asia/Dhaka',
		'days'     => [ 0, 1, 2, 3, 4, 5, 6 ],
		'notice'   => 1,
		'title'    => 'Test call',
		'accent'   => '#006BFF',
	]
);
$signed = ConfigSigner::sign( $config, home_url( '/book/' ) );

$call = function ( string $method, string $route, array $params ) {
	$request = new WP_REST_Request( $method, '/' . GSBF_REST_NAMESPACE . $route );
	if ( 'GET' === $method ) {
		$request->set_query_params( $params );
	} else {
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_body_params( $params );
	}
	return rest_do_request( $request );
};
$base = [ 'config' => $signed['payload'], 'sig' => $signed['sig'] ];

echo "Availability\n";
$month = gmdate( 'Y-m', strtotime( '+3 days' ) );
$res   = $call( 'GET', '/availability', $base + [ 'month' => $month ] );
it( 'month endpoint answers 200', 200 === $res->get_status() );
$days = $res->get_data()['days'] ?? [];
it( 'month lists open days', count( $days ) > 0 );
$bad = $call( 'GET', '/availability', [ 'config' => $signed['payload'], 'sig' => 'wrong', 'month' => $month ] );
it( 'tampered signature is rejected (403)', 403 === $bad->get_status() );

$date  = '';
foreach ( $days as $d ) {
	if ( $d['available'] ) {
		$date = $d['date'];
		break;
	}
}
$res   = $call( 'GET', '/availability', $base + [ 'date' => $date ] );
$slots = $res->get_data()['slots'] ?? [];
it( 'day endpoint returns 16 slots for 9-17 / 30 min', 16 === count( $slots ), 'got ' . count( $slots ) );
$slot_a = $slots[2]['start'] ?? 0;
$slot_b = $slots[5]['start'] ?? 0;
$slot_c = $slots[8]['start'] ?? 0;

$book = function ( int $start, string $email, array $extra = [] ) use ( $call, $base ) {
	// Every booking comes from a "new visitor" so the per-IP rate limit (12 / 10 min) is not hit.
	$_SERVER['REMOTE_ADDR'] = '10.' . wp_rand( 1, 250 ) . '.' . wp_rand( 1, 250 ) . '.' . wp_rand( 1, 250 );
	return $call(
		'POST',
		'/bookings',
		array_merge( $base, [ 'start' => $start, 'name' => 'Rahim Uddin', 'email' => $email, 'phone' => '+880 1712 345678', 'hp' => '', 't' => 5000 ], $extra )
	);
};

echo "Create (manual approval)\n";
$res = $book( $slot_a, 'a-' . wp_generate_password( 6, false ) . '@example.test' );
it( 'creates a booking (201)', 201 === $res->get_status(), wp_json_encode( $res->get_data() ) );
$booking_a = $res->get_data();
it( 'status is pending in manual mode', 'pending' === ( $booking_a['status'] ?? '' ) );
it( 'a ref is returned, the id is not', ! empty( $booking_a['ref'] ) && ! isset( $booking_a['id'] ) );
it( 'admin got the request email with Confirm/Decline', in_array( true, array_map( fn( $m ) => false !== strpos( $m['message'], 'gsbf_action=confirm' ) && false !== strpos( $m['message'], 'gsbf_action=decline' ), $mails ), true ) );
it( 'admin mail goes to the configured address', 'admin@example.test' === ( $mails[0]['to'] ?? '' ) );
it( 'customer gets a "received, still pending" email', 2 === count( $mails ) && false !== strpos( $mails[1]['subject'], 'We received your booking request' ) && false !== strpos( $mails[1]['message'], 'pending' ) );
it( 'that email has View / Reschedule / Cancel but no confirm link', false !== strpos( $mails[1]['message'], 'gsbf_action=view' ) && false !== strpos( $mails[1]['message'], 'gsbf_action=cancel' ) && false === strpos( $mails[1]['message'], 'gsbf_action=confirm' ) );
$row_a = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE ref = %s", $booking_a['ref'] ), ARRAY_A );
it( 'row stored with UTC times and accent', $row_a && '#006BFF' === $row_a['accent'] && 'Asia/Dhaka' === $row_a['timezone'] );

echo "Double booking guard\n";
$res = $book( $slot_a, 'b-' . wp_generate_password( 6, false ) . '@example.test' );
it( 'same slot again is refused (409)', 409 === $res->get_status(), wp_json_encode( $res->get_data() ) );
$res = $call( 'GET', '/availability', $base + [ 'date' => $date ] );
$flag = array_values( array_filter( $res->get_data()['slots'], fn( $s ) => $s['start'] === $slot_a ) )[0] ?? [];
it( 'availability now shows that slot as taken', false === ( $flag['available'] ?? true ) );
$res = $book( $slot_a + 60, 'c-' . wp_generate_password( 6, false ) . '@example.test' );
it( 'an off-grid time is refused (400)', 400 === $res->get_status() );

echo "Validation / spam\n";
it( 'honeypot filled is refused', 400 === $book( $slot_b, 'd-' . wp_generate_password( 6, false ) . '@example.test', [ 'hp' => 'x' ] )->get_status() );
it( 'too-fast submit is refused', 400 === $book( $slot_b, 'e-' . wp_generate_password( 6, false ) . '@example.test', [ 't' => 100 ] )->get_status() );
it( 'bad email is refused', 400 === $book( $slot_b, 'not-an-email' )->get_status() );

echo "View + tokens\n";
$id    = (int) $row_a['id'];
$view  = BookingToken::generate( $id, 'view', time() + 600 );
$res   = $call( 'GET', "/bookings/{$id}", [ 'token' => $view, 'action' => 'view' ] );
it( 'view token returns the booking', 200 === $res->get_status() && 'Rahim Uddin' === ( $res->get_data()['name'] ?? '' ) );
$res = $call( 'GET', "/bookings/{$id}", [ 'token' => $view, 'action' => 'reschedule' ] );
it( 'a view token cannot be used as reschedule token', 403 === $res->get_status() );
$res = $call( 'GET', '/bookings/999999', [ 'token' => $view, 'action' => 'view' ] );
it( 'a token for another booking is rejected', 403 === $res->get_status() );

echo "Confirm\n";
$mails = [];
$res   = BookingService::confirm( $id );
it( 'confirm makes it confirmed', ! is_wp_error( $res ) && 'confirmed' === $res['status'] );
it( 'customer got the confirmation with View/Reschedule/Cancel', 1 === count( $mails ) && $row_a['email'] === $mails[0]['to'] && false !== strpos( $mails[0]['message'], 'gsbf_action=reschedule' ) && false !== strpos( $mails[0]['message'], 'gsbf_action=cancel' ) );
it( 'a second confirm is refused', is_wp_error( BookingService::confirm( $id ) ) );

echo "Reschedule (manual => back to pending, old slot released at once)\n";
$mails = [];
$res   = $call( 'POST', "/bookings/{$id}/reschedule", $base + [ 'token' => BookingToken::generate( $id, 'reschedule', time() + 600 ), 'start' => $slot_b ] );
it( 'reschedule works (200)', 200 === $res->get_status(), wp_json_encode( $res->get_data() ) );
$row = BookingRepository::find( $id );
it( 'status is pending again with rescheduled_at set', 'pending' === $row['status'] && ! empty( $row['rescheduled_at'] ) );
it( 'row moved to the new slot', Helper::ts( $row['start_utc'] ) === $slot_b );
it( 'admin asked to approve, customer told it is waiting', 2 === count( $mails ) && false !== strpos( $subjects()[0], 'Reschedule request' ) );
$res = $book( $slot_a, 'f-' . wp_generate_password( 6, false ) . '@example.test' );
it( 'the old slot can be booked by someone else immediately', 201 === $res->get_status(), wp_json_encode( $res->get_data() ) );
$other = BookingRepository::find( (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE ref = %s", $res->get_data()['ref'] ) ) );
$res = $call( 'POST', "/bookings/{$id}/reschedule", $base + [ 'token' => BookingToken::generate( $id, 'reschedule', time() + 600 ), 'start' => $slot_a ] );
it( 'rescheduling onto a taken slot is refused (409)', 409 === $res->get_status() );

echo "Decline of an unapproved reschedule deletes the whole booking\n";
$mails = [];
$res   = BookingService::decline( $id );
it( 'decline succeeds', ! is_wp_error( $res ) );
it( 'row is deleted', null === BookingRepository::find( $id ) );
it( 'customer told the reschedule was not approved', 1 === count( $mails ) && false !== strpos( $mails[0]['subject'], 'reschedule request was not approved' ) );
it( 'slot is free again', 201 === $book( $slot_b, 'g-' . wp_generate_password( 6, false ) . '@example.test' )->get_status() );

echo "Decline of a new request / cancel\n";
$mails = [];
$res   = BookingService::decline( (int) $other['id'] );
it( 'declining a new request tells the customer it was declined', ! is_wp_error( $res ) && false !== strpos( $mails[0]['subject'], 'declined' ) );
$res = $book( $slot_c, 'h-' . wp_generate_password( 6, false ) . '@example.test' );
$cid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE ref = %s", $res->get_data()['ref'] ) );
BookingService::confirm( $cid );
$mails = [];
$res   = BookingService::cancel( $cid );
it( 'cancel deletes the row and mails customer + admin', ! is_wp_error( $res ) && null === BookingRepository::find( $cid ) && 2 === count( $mails ) );

echo "Landing page (real HTTP)\n";
$res = $book( $slots[14]['start'], 'l-' . wp_generate_password( 6, false ) . '@example.test' );
$lid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE ref = %s", $res->get_data()['ref'] ) );
$url = BookingActionUrl::url( BookingRepository::find( $lid ), 'confirm' );
$get = wp_remote_get( $url, [ 'timeout' => 20 ] );
$htm = is_wp_error( $get ) ? '' : wp_remote_retrieve_body( $get );
it( 'confirm page shows the real event, date and time', false !== strpos( $htm, 'Test call' ) && false !== strpos( $htm, wp_date( 'g:i A', $slots[14]['start'], new DateTimeZone( 'Asia/Dhaka' ) ) ), is_wp_error( $get ) ? $get->get_error_message() : '' );
$post = wp_remote_post( $url, [ 'timeout' => 20, 'body' => [ 'gsbf_do' => '1' ] ] );
it( 'confirm POST confirms the booking', 'confirmed' === ( BookingRepository::find( $lid )['status'] ?? '' ) );
$gone = wp_remote_retrieve_body( wp_remote_get( $url, [ 'timeout' => 20 ] ) );
it( 'a second visit says it was already handled', false !== strpos( $gone, 'already handled' ) );

echo "Expiry cron (pending older than 48 h)\n";
$res = $book( $slot_c, 'i-' . wp_generate_password( 6, false ) . '@example.test' );
$eid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE ref = %s", $res->get_data()['ref'] ) );
$wpdb->update( $table, [ 'requested_at' => Helper::utc( time() - 49 * HOUR_IN_SECONDS ) ], [ 'id' => $eid ] );
$res2 = $book( $slots[10]['start'], 'j-' . wp_generate_password( 6, false ) . '@example.test' );
$kid  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE ref = %s", $res2->get_data()['ref'] ) );
$mails = [];
( new CleanupExpiredBookings() )->run();
it( 'the 49 h old pending row is deleted', null === BookingRepository::find( $eid ) );
it( 'a fresh pending row is kept', null !== BookingRepository::find( $kid ) );
it( 'the customer got the expiry email', in_array( true, array_map( fn( $s ) => false !== strpos( $s, 'not confirmed in time' ), $subjects() ), true ) );
BookingService::confirm( $kid );
$wpdb->update( $table, [ 'requested_at' => Helper::utc( time() - 99 * HOUR_IN_SECONDS ) ], [ 'id' => $kid ] );
( new CleanupExpiredBookings() )->run();
it( 'a confirmed row is never expired', null !== BookingRepository::find( $kid ) );

echo "Auto approval\n";
update_option( GSBF_SETTINGS_NAME, array_merge( Helper::get_settings(), [ 'approval' => 'auto' ] ), false );
$mails = [];
$res   = $book( $slots[12]['start'], 'k-' . wp_generate_password( 6, false ) . '@example.test' );
it( 'booking is confirmed at once', 201 === $res->get_status() && 'confirmed' === $res->get_data()['status'] );
it( 'customer confirmation + admin FYI sent', 2 === count( $mails ) );
$aid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE ref = %s", $res->get_data()['ref'] ) );
$mails = [];
$res   = $call( 'POST', "/bookings/{$aid}/reschedule", $base + [ 'token' => BookingToken::generate( $aid, 'reschedule', time() + 600 ), 'start' => $slots[13]['start'] ] );
$row   = BookingRepository::find( $aid );
it( 'reschedule in auto mode stays confirmed', 200 === $res->get_status() && 'confirmed' === $row['status'] && empty( $row['rescheduled_at'] ) );

echo "Privacy\n";
$email = $row['email'];
$exp   = \GembookingSimpleBookingForm\Privacy\Exporter::export( $email );
it( 'exporter lists the booking', 1 === count( $exp['data'] ) );
$erase = \GembookingSimpleBookingForm\Privacy\Eraser::erase( $email );
$row   = BookingRepository::find( $aid );
it( 'eraser anonymises but keeps the slot', $erase['items_removed'] && 'Erased' === $row['name'] && null === $row['phone'] && false !== strpos( $row['email'], 'invalid.invalid' ) && $row['slot_key'] );

echo "Previews\n";
$html = \GembookingSimpleBookingForm\Classes\Mailer::preview( 'admin', Helper::get_settings(), '#006BFF' );
it( 'admin preview renders the branded layout', false !== strpos( $html, 'Confirm booking' ) && false !== strpos( $html, '#006BFF' ) );
$html = \GembookingSimpleBookingForm\Classes\Mailer::preview( 'customer', Helper::get_settings(), '#141A24' );
it( 'customer preview has View / Reschedule / Cancel', false !== strpos( $html, 'View booking' ) && false !== strpos( $html, 'Reschedule' ) && false !== strpos( $html, 'Cancel' ) );

// Cleanup.
$wpdb->query( "DELETE FROM {$table} WHERE form_id LIKE 'itest-%'" );
if ( false === $original_settings ) {
	delete_option( GSBF_SETTINGS_NAME );
} else {
	update_option( GSBF_SETTINGS_NAME, $original_settings, false );
}

echo "\n{$GLOBALS['gsbf_pass']} passed, {$GLOBALS['gsbf_failed']} failed\n";
exit( $GLOBALS['gsbf_failed'] > 0 ? 1 : 0 );

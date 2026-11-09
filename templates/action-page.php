<?php
/**
 * Standalone page for the confirm / decline / cancel email links.
 *
 * Variables: $state (confirm|done|info|error), $action, $booking (public fields or null),
 * $message, $accent, $font, $site_name, $home_url.
 *
 * @package GembookingSimpleBookingForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Template variables live in the including function's scope, not the global scope.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$titles = [
	'confirm' => __( 'Confirm this booking?', 'gembooking-simple-booking-form' ),
	'decline' => __( 'Decline this booking request?', 'gembooking-simple-booking-form' ),
	'cancel'  => __( 'Cancel your booking?', 'gembooking-simple-booking-form' ),
];
$labels = [
	'confirm' => __( 'Confirm booking', 'gembooking-simple-booking-form' ),
	'decline' => __( 'Decline booking', 'gembooking-simple-booking-form' ),
	'cancel'  => __( 'Yes, cancel my booking', 'gembooking-simple-booking-form' ),
];
$hints  = [
	'confirm' => __( 'The customer will be emailed that their booking is confirmed.', 'gembooking-simple-booking-form' ),
	'decline' => __( 'The customer will be emailed and the time becomes free for others.', 'gembooking-simple-booking-form' ),
	'cancel'  => __( 'The time becomes free for others. This cannot be undone.', 'gembooking-simple-booking-form' ),
];

$heading = 'confirm' === $state ? $titles[ $action ] : ( 'done' === $state ? __( 'Done', 'gembooking-simple-booking-form' ) : $site_name );

$summary = [];
if ( $booking ) {
	$tz        = new DateTimeZone( $booking['timezone'] );
	$summary[] = [ __( 'Event', 'gembooking-simple-booking-form' ), $booking['title'] ];
	$summary[] = [ __( 'Date', 'gembooking-simple-booking-form' ), wp_date( 'l, F j, Y', $booking['start'], $tz ) ];
	$summary[] = [ __( 'Time', 'gembooking-simple-booking-form' ), wp_date( 'g:i A', $booking['start'], $tz ) . ' – ' . wp_date( 'g:i A', $booking['end'], $tz ) ];
	$summary[] = [ __( 'Timezone', 'gembooking-simple-booking-form' ), $booking['timezone'] ];
	$summary[] = [ __( 'Name', 'gembooking-simple-booking-form' ), $booking['name'] ];
	$summary[] = [ __( 'Email', 'gembooking-simple-booking-form' ), $booking['email'] ];
	$summary[] = [ __( 'Booking ref', 'gembooking-simple-booking-form' ), $booking['ref'] ];
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?php echo esc_html( $heading ); ?></title>
<style>
	body{margin:0;background:#F6F7F8;color:#141A24;font-family:<?php echo esc_html( $font ); ?>;font-size:14px;line-height:1.5}
	.gsbf-ap{max-width:480px;margin:48px auto;padding:0 16px}
	.gsbf-ap-card{background:#fff;border:1px solid #E0E4E8;border-radius:12px;padding:28px 24px;box-shadow:0 0 1px rgba(20,26,36,.2),0 1px 2px rgba(20,26,36,.1)}
	.gsbf-ap h1{font-size:20px;font-weight:600;margin:0 0 6px}
	.gsbf-ap p{margin:0 0 16px;color:#434A51}
	.gsbf-ap dl{margin:0 0 20px;border:1px solid #E0E4E8;border-radius:8px}
	.gsbf-ap dl div{display:flex;justify-content:space-between;gap:16px;padding:10px 14px;border-bottom:1px solid #EEF0F2}
	.gsbf-ap dl div:last-child{border-bottom:0}
	.gsbf-ap dt{color:#646C73}.gsbf-ap dd{margin:0;text-align:right;overflow-wrap:anywhere}
	.gsbf-ap button,.gsbf-ap a.gsbf-ap-link{display:block;width:100%;box-sizing:border-box;height:46px;line-height:46px;padding:0 18px;border:0;border-radius:6px;background:<?php echo esc_html( $accent ); ?>;color:#fff;font:inherit;font-weight:500;text-align:center;text-decoration:none;cursor:pointer}
	.gsbf-ap a.gsbf-ap-link{margin-top:12px;background:#fff;color:#141A24;border:1px solid #E0E4E8;line-height:44px}
	.gsbf-ap button:focus-visible,.gsbf-ap a:focus-visible{outline:2px solid #141A24;outline-offset:2px}
	.gsbf-ap small{display:block;margin-top:10px;color:#646C73;text-align:center}
</style>
</head>
<body>
<main class="gsbf-ap">
	<div class="gsbf-ap-card">
		<h1><?php echo esc_html( $heading ); ?></h1>
		<?php if ( 'confirm' === $state ) : ?>
			<p><?php echo esc_html( $hints[ $action ] ); ?></p>
		<?php else : ?>
			<p><?php echo esc_html( $message ); ?></p>
		<?php endif; ?>

		<?php if ( $summary && in_array( $state, [ 'confirm', 'done' ], true ) ) : ?>
			<dl>
				<?php foreach ( $summary as $row ) : ?>
					<div><dt><?php echo esc_html( $row[0] ); ?></dt><dd><?php echo esc_html( $row[1] ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>

		<?php if ( 'confirm' === $state ) : ?>
			<form method="post" action="">
				<input type="hidden" name="gsbf_do" value="1">
				<button type="submit"><?php echo esc_html( $labels[ $action ] ); ?></button>
			</form>
		<?php elseif ( 'cancel' === $action && 'done' === $state ) : ?>
			<a class="gsbf-ap-link" href="<?php echo esc_url( $home_url ); ?>"><?php esc_html_e( 'Book another time', 'gembooking-simple-booking-form' ); ?></a>
		<?php endif; ?>
	</div>
</main>
</body>
</html>

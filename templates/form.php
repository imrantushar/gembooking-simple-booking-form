<?php
/**
 * Mount point for the booking form widget (hydrated by the front-end script).
 *
 * Variables: $wrapper (block wrapper attributes), $signed (payload + sig), $config.
 *
 * @package GembookingSimpleBookingForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped by get_block_wrapper_attributes(). ?>
	data-gsbf-config="<?php echo esc_attr( $signed['payload'] ); ?>"
	data-gsbf-sig="<?php echo esc_attr( $signed['sig'] ); ?>">
	<noscript><?php esc_html_e( 'Please enable JavaScript to book a time.', 'gembooking-simple-booking-form' ); ?></noscript>
</div>

<?php
/**
 * Plain-text email layout: the same content as the HTML layout, buttons as links.
 *
 * Variables: $c (content), $settings, $to.
 *
 * @package GembookingSimpleBookingForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Template variables live in the including function's scope, not the global scope.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// Plain-text email body: it is not HTML, so it is stripped of tags rather than HTML-escaped.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

echo wp_strip_all_tags( $c['banner'] ) . "\n\n";
echo wp_strip_all_tags( $c['heading'] ) . "\n";
echo wp_strip_all_tags( $c['body'] ) . "\n\n";

foreach ( $c['rows'] as $row ) {
	echo wp_strip_all_tags( $row[0] ) . ': ' . wp_strip_all_tags( $row[1] ) . "\n";
}

if ( ! empty( $c['buttons'] ) ) {
	echo "\n";
	foreach ( $c['buttons'] as $button ) {
		echo wp_strip_all_tags( $button['label'] ) . ': ' . esc_url_raw( $button['url'] ) . "\n";
	}
}

echo "\n--\n" . wp_strip_all_tags( (string) $settings['footerHtml'] ) . "\n";

<?php
/**
 * HTML email layout.
 *
 * Variables: $c (content), $settings, $accent, $to, $font, $tone, $logo_url, $initials.
 * Inline styles and tables only: email clients ignore stylesheets.
 *
 * @package GembookingSimpleBookingForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Template variables live in the including function's scope, not the global scope.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$variants = [
	'primary'   => 'background:' . $accent . ';border:1px solid ' . $accent . ';color:#ffffff;',
	'secondary' => 'background:#ffffff;border:1px solid #E0E4E8;color:#141A24;',
	'link'      => 'background:#ffffff;border:1px solid #E0E4E8;color:#434A51;',
];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $c['subject'] ); ?></title>
</head>
<body style="margin:0;padding:0;background:#F6F7F8;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F6F7F8;font-family:<?php echo esc_attr( $font ); ?>;color:#141A24;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border:1px solid #E0E4E8;border-radius:8px;">
	<tr><td style="padding:20px 24px 0;">
		<div style="font-size:20px;font-weight:500;line-height:28px;margin:0 0 14px;"><?php echo esc_html( $c['subject'] ); ?></div>
		<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 16px;">
		<tr>
			<td width="48" valign="middle">
				<?php if ( $logo_url ) : ?>
					<img src="<?php echo esc_url( $logo_url ); ?>" width="36" height="36" alt="" style="display:block;width:36px;height:36px;border-radius:999px;object-fit:cover;">
				<?php else : ?>
					<div style="width:36px;height:36px;border-radius:999px;background:<?php echo esc_attr( $accent ); ?>;color:#ffffff;font-size:13px;font-weight:600;line-height:36px;text-align:center;"><?php echo esc_html( $initials ); ?></div>
				<?php endif; ?>
			</td>
			<td valign="middle" style="font-size:14px;">
				<div style="font-weight:500;"><?php echo esc_html( $settings['fromName'] ); ?> <span style="color:#646C73;font-weight:400;font-size:12px;">&lt;<?php echo esc_html( $settings['fromEmail'] ); ?>&gt;</span></div>
				<div style="font-size:12px;color:#646C73;">
					<?php
					/* translators: %s: recipient email address */
					echo esc_html( sprintf( __( 'to %s', 'gembooking-simple-booking-form' ), $to ) );
					?>
				</div>
			</td>
		</tr>
		</table>
	</td></tr>
	<tr><td style="padding:0 24px;">
		<div style="background:<?php echo esc_attr( $tone[0] ); ?>;color:<?php echo esc_attr( $tone[1] ); ?>;padding:10px 12px;font-size:13px;font-weight:600;"><?php echo esc_html( $c['banner'] ); ?></div>
	</td></tr>
	<tr><td style="padding:16px 24px 8px;">
		<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #E0E4E8;">
		<tr>
			<td width="110" valign="top" align="center" style="background:#F6F7F8;padding:20px 0;">
				<table role="presentation" cellpadding="0" cellspacing="0" width="64" style="width:64px;background:#ffffff;border:1px solid #E0E4E8;border-radius:4px;text-align:center;">
					<tr><td style="background:<?php echo esc_attr( $accent ); ?>;color:#ffffff;font-size:11px;font-weight:600;padding:3px 0;"><?php echo esc_html( $c['date']['month'] ); ?></td></tr>
					<tr><td style="font-size:26px;font-weight:500;line-height:32px;padding:4px 0 0;"><?php echo esc_html( $c['date']['day'] ); ?></td></tr>
					<tr><td style="font-size:11px;font-weight:600;padding:0 0 6px;"><?php echo esc_html( $c['date']['weekday'] ); ?></td></tr>
				</table>
			</td>
			<td valign="top" style="padding:16px 20px;border-left:1px solid #E0E4E8;">
				<div style="font-size:20px;font-weight:500;line-height:26px;"><?php echo esc_html( $c['heading'] ); ?></div>
				<div style="font-size:14px;color:#434A51;line-height:21px;margin-top:8px;"><?php echo esc_html( $c['body'] ); ?></div>
				<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-top:12px;font-size:13px;">
				<?php foreach ( $c['rows'] as $row ) : ?>
					<tr>
						<td width="64" valign="top" style="color:#646C73;padding:4px 8px 4px 0;"><?php echo esc_html( $row[0] ); ?></td>
						<td valign="top" style="padding:4px 0;word-break:break-word;"><?php echo esc_html( $row[1] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</table>
				<?php if ( ! empty( $c['buttons'] ) ) : ?>
					<table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:14px;"><tr>
					<?php foreach ( $c['buttons'] as $button ) : ?>
						<td style="padding:0 8px 8px 0;">
							<a href="<?php echo esc_url( $button['url'] ); ?>" style="display:inline-block;height:40px;line-height:40px;padding:0 18px;border-radius:4px;font-size:14px;font-weight:500;text-decoration:none;<?php echo esc_attr( $variants[ $button['variant'] ] ?? $variants['secondary'] ); ?>"><?php echo esc_html( $button['label'] ); ?></a>
						</td>
					<?php endforeach; ?>
					</tr></table>
				<?php endif; ?>
			</td>
		</tr>
		</table>
	</td></tr>
	<tr><td style="padding:8px 24px 20px;font-size:11px;color:#646C73;line-height:16px;">
		<?php echo wp_kses( (string) $settings['footerHtml'], \GembookingSimpleBookingForm\Helper::footer_allowed_html() ); ?>
	</td></tr>
</table>
</td></tr>
</table>
</body>
</html>

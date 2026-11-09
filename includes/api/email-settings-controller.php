<?php

namespace GembookingSimpleBookingForm\API;

use GembookingSimpleBookingForm\Classes\Mailer;
use GembookingSimpleBookingForm\Helper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site-wide email settings (the "Configure email" modal in the block editor).
 *
 *   GET  /email-settings          read
 *   POST /email-settings          save
 *   POST /email-settings/preview  rendered HTML of the admin/customer email
 *   POST /email-settings/test     send the admin email with sample data to the admin address
 */
class EmailSettingsController {

	public function register_routes(): void {
		register_rest_route(
			GSBF_REST_NAMESPACE,
			'/email-settings',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'can_manage' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'save_settings' ],
					'permission_callback' => [ $this, 'can_manage' ],
					'args'                => [
						'settings' => [
							'required' => true,
							'type'     => 'object',
						],
					],
				],
			]
		);

		register_rest_route(
			GSBF_REST_NAMESPACE,
			'/email-settings/preview',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'preview' ],
				'permission_callback' => [ $this, 'can_manage' ],
				'args'                => [
					'kind'     => [
						'required' => true,
						'type'     => 'string',
						'enum'     => [ 'admin', 'customer' ],
					],
					'settings' => [
						'type' => 'object',
					],
					'accent'   => [
						'type' => 'string',
					],
				],
			]
		);

		register_rest_route(
			GSBF_REST_NAMESPACE,
			'/email-settings/test',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'send_test' ],
				'permission_callback' => [ $this, 'can_manage' ],
				'args'                => [
					'accent' => [
						'type' => 'string',
					],
				],
			]
		);
	}

	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	public function get_settings(): WP_REST_Response {
		return new WP_REST_Response( $this->present( Helper::get_settings() ) );
	}

	public function save_settings( WP_REST_Request $request ): WP_REST_Response {
		$settings = Helper::sanitize_settings( array_merge( Helper::get_settings(), (array) $request->get_param( 'settings' ) ) );

		update_option( GSBF_SETTINGS_NAME, $settings, false );

		return new WP_REST_Response( $this->present( $settings ) );
	}

	public function preview( WP_REST_Request $request ): WP_REST_Response {
		// Preview the unsaved values from the modal merged over the saved ones.
		$settings = Helper::sanitize_settings( array_merge( Helper::get_settings(), (array) $request->get_param( 'settings' ) ) );
		$accent   = Helper::sanitize_accent( (string) $request->get_param( 'accent' ) );

		return new WP_REST_Response(
			[ 'html' => Mailer::preview( (string) $request->get_param( 'kind' ), $settings, $accent ) ]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function send_test( WP_REST_Request $request ) {
		$accent = Helper::sanitize_accent( (string) $request->get_param( 'accent' ) );

		if ( ! Mailer::send_test( $accent ) ) {
			return new WP_Error( 'gsbf_mail_failed', __( 'The test email could not be sent. Check the admin email address and your site email setup.', 'gembooking-simple-booking-form' ), [ 'status' => 500 ] );
		}

		return new WP_REST_Response( [ 'sent' => true ] );
	}

	/**
	 * Settings plus the logo's URL for the modal.
	 *
	 * @param array $settings Saved settings.
	 */
	private function present( array $settings ): array {
		$settings['logoUrl'] = $settings['logo'] ? (string) wp_get_attachment_image_url( (int) $settings['logo'], 'thumbnail' ) : '';

		return $settings;
	}
}

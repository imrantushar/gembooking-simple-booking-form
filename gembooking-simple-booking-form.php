<?php

/**
 * Plugin Name: GemBooking Simple Booking Form
 * Plugin URI: https://gembooking.net
 * Description: A simple appointment booking form block. Visitors pick a date and time, you confirm by email. No setup screens, double-booking protected.
 * Version: 1.0.0
 * Author: Kodezen
 * Author URI: https://kodezen.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gembooking-simple-booking-form
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimum PHP version this plugin supports.
 */
define( 'GSBF_MINIMUM_PHP_VERSION', '8.1' );

if ( ! function_exists( 'gsbf_php_version_notice' ) ) {
	/**
	 * Admin notice shown when the server PHP version is below
	 * GSBF_MINIMUM_PHP_VERSION. Only registered when the check below fails.
	 */
	function gsbf_php_version_notice() {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: 1: minimum required PHP version, 2: current PHP version */
					__( 'GemBooking Simple Booking Form requires PHP version %1$s or higher. Your site is currently running PHP %2$s. Please ask your hosting provider to update PHP.', 'gembooking-simple-booking-form' ),
					GSBF_MINIMUM_PHP_VERSION,
					PHP_VERSION
				)
			)
		);
	}
}

// Bail out gracefully (no fatal) on unsupported PHP: show an admin notice and
// stop before any of the plugin's 8.1+ code is loaded.
if ( version_compare( PHP_VERSION, GSBF_MINIMUM_PHP_VERSION, '<' ) ) {
	add_action( 'admin_notices', 'gsbf_php_version_notice' );
	return;
}

final class GembookingSimpleBookingForm {

	private function __construct() {
		$this->define_constants();
		$this->load_dependency();
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
		add_action( 'gsbf_loaded', [ $this, 'init_plugin' ] );
	}

	public static function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	public function define_constants() {
		define( 'GSBF_VERSION', '1.0.0' );
		define( 'GSBF_SETTINGS_NAME', 'gsbf_settings' );
		define( 'GSBF_PLUGIN_FILE', __FILE__ );
		define( 'GSBF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		define( 'GSBF_PLUGIN_SLUG', 'gembooking-simple-booking-form' );
		define( 'GSBF_DB_PREFIX', 'gsbf' );
		define( 'GSBF_REST_NAMESPACE', 'gsbf/v1' );
		define( 'GSBF_BLOCK_NAME', 'gsbf/booking-form' );
		define( 'GSBF_PLUGIN_ROOT_URI', plugins_url( '/', __FILE__ ) );
		define( 'GSBF_ROOT_DIR_PATH', plugin_dir_path( __FILE__ ) );
		define( 'GSBF_INCLUDES_DIR_PATH', GSBF_ROOT_DIR_PATH . 'includes/' );
		define( 'GSBF_TEMPLATES_DIR_PATH', GSBF_ROOT_DIR_PATH . 'templates/' );
		define( 'GSBF_ASSETS_DIR_PATH', GSBF_ROOT_DIR_PATH . 'assets/' );
		define( 'GSBF_ASSETS_URI', GSBF_PLUGIN_ROOT_URI . 'assets/' );
	}

	/**
	 * When WP has loaded all plugins, trigger the `gsbf_loaded` hook.
	 */
	public function on_plugins_loaded() {
		do_action( 'gsbf_loaded' );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init_plugin() {
		do_action( 'gsbf_before_init' );
		$this->dispatch_hooks();
		do_action( 'gsbf_init' );
	}

	public function dispatch_hooks() {
		GembookingSimpleBookingForm\Database::init();
		GembookingSimpleBookingForm\Blocks::init();
		GembookingSimpleBookingForm\Assets::init();
		GembookingSimpleBookingForm\Api::init();
		GembookingSimpleBookingForm\Frontend::init();
		GembookingSimpleBookingForm\Privacy::init();
		GembookingSimpleBookingForm\Classes\CleanupExpiredBookings::init();
	}

	public function load_dependency() {
		require_once GSBF_INCLUDES_DIR_PATH . 'autoload.php';
	}

	public function activate() {
		GembookingSimpleBookingForm\Installer::init();
	}

	public function deactivate() {
		GembookingSimpleBookingForm\Classes\CleanupExpiredBookings::unschedule();
	}
}

GembookingSimpleBookingForm::init();

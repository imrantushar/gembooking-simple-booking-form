<?php

namespace GembookingSimpleBookingForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the script and style handles the block references in block.json
 * (`gsbf-blocks` for the editor, `gsbf-frontend` for visitors). WordPress then
 * enqueues them only where the block is used.
 *
 * Bundles are emitted by webpack as `assets/build/{name}.{version}.js`
 * together with a `.asset.php` file listing the WordPress script dependencies.
 */
class Assets {

	const EDITOR_HANDLE   = 'gsbf-blocks';
	const FRONTEND_HANDLE = 'gsbf-frontend';
	const GLOBAL_OBJECT   = 'GsbfGlobal';

	public static function init(): void {
		$self = new self();
		// Before Blocks::register() (priority 10) reads block.json.
		add_action( 'init', [ $self, 'register' ], 5 );
	}

	public function register(): void {
		// The editor bundle (and its timezone list) is only needed in wp-admin.
		if ( is_admin() ) {
			$this->register_bundle( 'blocks', self::EDITOR_HANDLE, $this->editor_data() );
		}
		$this->register_bundle( 'frontend', self::FRONTEND_HANDLE, $this->frontend_data() );
	}

	/**
	 * Register `{name}.{version}.js` and `{name}.css` under one handle.
	 *
	 * @param string $name   Webpack entry name.
	 * @param string $handle Script/style handle.
	 * @param array  $data   Data for window.GsbfGlobal.
	 */
	private function register_bundle( string $name, string $handle, array $data ): void {
		$base  = GSBF_ASSETS_DIR_PATH . 'build/' . $name . '.' . GSBF_VERSION;
		$asset = [
			'dependencies' => [],
			'version'      => GSBF_VERSION,
		];

		if ( file_exists( $base . '.asset.php' ) ) {
			$asset = array_merge( $asset, (array) include $base . '.asset.php' );
		}
		if ( ! file_exists( $base . '.js' ) ) {
			return; // Not built yet (a fresh clone before `npm run build`).
		}

		// Cache-busting version: the bundle's modified time. The version hash in the
		// .asset.php file would also do, but that file is include()d and can stay
		// stale in OPcache after a rebuild, so the browser would keep an old bundle.
		$mtime   = filemtime( $base . '.js' );
		$version = false !== $mtime ? (string) $mtime : $asset['version'];

		wp_register_script(
			$handle,
			GSBF_ASSETS_URI . 'build/' . $name . '.' . GSBF_VERSION . '.js',
			$asset['dependencies'],
			$version,
			[ 'in_footer' => true ]
		);
		// Exposes window.GsbfGlobal to the script, printed before it.
		wp_localize_script( $handle, self::GLOBAL_OBJECT, $data );
		wp_set_script_translations( $handle, 'gembooking-simple-booking-form', GSBF_ROOT_DIR_PATH . 'languages' );

		if ( file_exists( GSBF_ASSETS_DIR_PATH . 'build/' . $name . '.css' ) ) {
			wp_register_style(
				$handle,
				GSBF_ASSETS_URI . 'build/' . $name . '.css',
				[],
				$version
			);
			wp_style_add_data( $handle, 'rtl', 'replace' );
		}
	}

	private function frontend_data(): array {
		return [
			'restUrl' => esc_url_raw( rest_url( GSBF_REST_NAMESPACE . '/' ) ),
		];
	}

	/**
	 * Data for the block editor (sidebar and email modal).
	 *
	 * @return array
	 */
	private function editor_data(): array {
		$zones = \DateTimeZone::listIdentifiers();
		$site  = wp_timezone_string();

		return $this->frontend_data() + [
			'approval'     => Helper::get_settings()['approval'],
			'timezones'    => $zones,
			// wp_timezone_string() can be a "+06:00" offset, which is not a valid IANA zone.
			'siteTimezone' => in_array( $site, $zones, true ) ? $site : 'UTC',
		];
	}
}

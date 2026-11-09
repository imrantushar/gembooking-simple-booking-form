<?php
namespace GembookingSimpleBookingForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class autoloader.
 *
 * Same rule as the GemBooking plugin: strip the namespace, turn every
 * camelCase boundary and underscore into "-", turn "\" into "/" and
 * lowercase the result.
 *
 *   GembookingSimpleBookingForm\Classes\SlotCalendar  => includes/classes/slot-calendar.php
 *   GembookingSimpleBookingForm\API\BookingController => includes/api/booking-controller.php
 *   GembookingSimpleBookingForm\Blocks                => includes/blocks.php
 */
class Autoload {

	/**
	 * Instance
	 *
	 * @var object Class Instance.
	 */
	private static $instance;

	/**
	 * Autoload directories for different namespaces.
	 *
	 * @var array
	 */
	private $autoload_directories = array(
		'GembookingSimpleBookingForm' => GSBF_INCLUDES_DIR_PATH,
	);

	/**
	 * Initiator
	 *
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register autoload directories for namespaces.
	 *
	 * @param string $ns        Namespace to autoload.
	 * @param string $directory Directory path for the namespace.
	 */
	public function add_namespace_directory( $ns, $directory ) {
		$this->autoload_directories[ $ns ] = $directory;
	}

	/**
	 * Autoload classes.
	 *
	 * @param string $class_name Class name.
	 */
	public function autoload( $class_name ) {
		foreach ( $this->autoload_directories as $ns => $directory ) {
			if ( 0 === strpos( $class_name, $ns . '\\' ) ) {
				$filename = strtolower(
					preg_replace(
						[ '/^' . $ns . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
						[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
						$class_name
					)
				);
				$file     = $directory . $filename . '.php';
				// If the file is readable, include it.
				if ( is_readable( $file ) ) {
					require_once $file;
				}
			}
		}
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		spl_autoload_register( [ $this, 'autoload' ] );
	}
}

Autoload::get_instance();

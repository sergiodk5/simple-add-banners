<?php
/**
 * Test bootstrap file.
 *
 * Sets up the testing environment for Brain Monkey.
 *
 * @package SimpleAddBanners\Tests
 */

declare(strict_types=1);

// Get the plugin directory.
$plugin_dir = dirname( __DIR__ );

// Define WordPress constants that may be used in the plugin.
// Point ABSPATH to test fixtures for unit tests (stubs WordPress files).
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $plugin_dir . '/tests/fixtures/' );
}

if ( ! defined( 'SIMPLE_ADD_BANNERS_VERSION' ) ) {
	define( 'SIMPLE_ADD_BANNERS_VERSION', '1.0.0' );
}

if ( ! defined( 'SIMPLE_ADD_BANNERS_PLUGIN_DIR' ) ) {
	define( 'SIMPLE_ADD_BANNERS_PLUGIN_DIR', $plugin_dir . '/' );
}

if ( ! defined( 'SIMPLE_ADD_BANNERS_PLUGIN_URL' ) ) {
	define( 'SIMPLE_ADD_BANNERS_PLUGIN_URL', 'https://example.com/wp-content/plugins/simple-add-banners/' );
}

// Load Composer autoloader.
require_once $plugin_dir . '/vendor/autoload.php';

// Define WordPress REST API classes for testing.
if ( ! class_exists( 'WP_REST_Server' ) ) {
	/**
	 * Stub WP_REST_Server class for testing.
	 */
	class WP_REST_Server {
		const READABLE   = 'GET';
		const CREATABLE  = 'POST';
		const EDITABLE   = 'POST, PUT, PATCH';
		const DELETABLE  = 'DELETE';
		const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
	}
}

if ( ! class_exists( 'WP_REST_Controller' ) ) {
	// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
	// phpcs:disable Squiz.Classes.ClassFileName.NoMatch
	/**
	 * Stub WP_REST_Controller class for testing.
	 */
	class WP_REST_Controller {
		/**
		 * REST namespace.
		 *
		 * @var string
		 */
		protected $namespace = '';

		/**
		 * REST base.
		 *
		 * @var string
		 */
		protected $rest_base = '';

		/**
		 * Schema.
		 *
		 * @var array|null
		 */
		protected $schema = null;

		/**
		 * Get endpoint args for item schema.
		 *
		 * @param string $method HTTP method.
		 * @return array
		 */
		public function get_endpoint_args_for_item_schema( $method = 'GET' ) {
			return array();
		}

		/**
		 * Get public item schema.
		 *
		 * @return array
		 */
		public function get_public_item_schema() {
			return $this->get_item_schema();
		}

		/**
		 * Get item schema.
		 *
		 * @return array
		 */
		public function get_item_schema() {
			return array();
		}

		/**
		 * Add additional fields schema.
		 *
		 * @param array $schema Schema.
		 * @return array
		 */
		public function add_additional_fields_schema( $schema ) {
			return $schema;
		}
	}
	// phpcs:enable
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	/**
	 * Stub WP_REST_Response class for testing.
	 */
	class WP_REST_Response {
		/**
		 * Response data.
		 *
		 * @var mixed
		 */
		private $data;

		/**
		 * Response status code.
		 *
		 * @var int
		 */
		private $status;

		/**
		 * Response headers.
		 *
		 * @var array
		 */
		private $headers = array();

		/**
		 * Constructor.
		 *
		 * @param mixed $data   Response data.
		 * @param int   $status HTTP status code.
		 */
		public function __construct( $data = null, $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		/**
		 * Get response data.
		 *
		 * @return mixed
		 */
		public function get_data() {
			return $this->data;
		}

		/**
		 * Get response status.
		 *
		 * @return int
		 */
		public function get_status() {
			return $this->status;
		}

		/**
		 * Set response status.
		 *
		 * @param int $status HTTP status code.
		 */
		public function set_status( $status ) {
			$this->status = $status;
		}

		/**
		 * Set response header.
		 *
		 * @param string $name  Header name.
		 * @param string $value Header value.
		 */
		public function header( $name, $value ) {
			$this->headers[ $name ] = $value;
		}

		/**
		 * Get response headers.
		 *
		 * @return array
		 */
		public function get_headers() {
			return $this->headers;
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Stub WP_Error class for testing.
	 */
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		private $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Error data.
		 *
		 * @var array
		 */
		private $data;

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 * @param array  $data    Error data.
		 */
		public function __construct( $code = '', $message = '', $data = array() ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * Get error code.
		 *
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * Get error message.
		 *
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}

		/**
		 * Get error data.
		 *
		 * @return array
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}

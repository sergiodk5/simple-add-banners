<?php
/**
 * Main plugin class.
 *
 * @package SimpleAddBanners
 */

namespace SimpleAddBanners;

/**
 * Bootstraps the plugin and initializes all components.
 *
 * @since 1.0.0
 */
class Plugin {


	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * Plugin directory URL.
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Gets the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->plugin_path = plugin_dir_path( __DIR__ );
		$this->plugin_url  = plugin_dir_url( __DIR__ );

		$this->init_hooks();
	}

	/**
	 * Initializes WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'init_components' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Loads the plugin text domain for translations.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'simple-add-banners',
			false,
			dirname( plugin_basename( $this->plugin_path ) ) . '/languages'
		);
	}

	/**
	 * Initializes plugin components.
	 *
	 * @since 1.0.0
	 */
	public function init_components(): void {
		if ( is_admin() ) {
			$this->init_admin();
		} else {
			$this->init_frontend();
		}
	}

	/**
	 * Initializes admin components.
	 *
	 * @since 1.0.0
	 */
	private function init_admin(): void {
		new Admin\Admin_Menu();
	}

	/**
	 * Initializes frontend components.
	 *
	 * @since 1.0.0
	 */
	private function init_frontend(): void {
		// Frontend components will be initialized here.
	}

	/**
	 * Registers REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_routes(): void {
		$banner_controller = new Api\Banner_Controller();
		$banner_controller->register_routes();

		$placement_controller = new Api\Placement_Controller();
		$placement_controller->register_routes();

		$banner_placement_controller = new Api\Banner_Placement_Controller();
		$banner_placement_controller->register_routes();
	}

	/**
	 * Gets the plugin directory path.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_plugin_path(): string {
		return $this->plugin_path;
	}

	/**
	 * Gets the plugin directory URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_plugin_url(): string {
		return $this->plugin_url;
	}

	/**
	 * Gets the plugin version.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_version(): string {
		return self::VERSION;
	}
}

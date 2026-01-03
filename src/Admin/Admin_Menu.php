<?php
/**
 * Admin menu registration and Vue app loading.
 *
 * @package SimpleAddBanners\Admin
 */

declare(strict_types=1);

namespace SimpleAddBanners\Admin;

/**
 * Handles admin menu registration and Vue app asset loading.
 *
 * @since 1.0.0
 */
class Admin_Menu {

	/**
	 * Menu slug for the plugin admin page.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'simple-add-banners';

	/**
	 * Required capability to access the admin page.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Initializes the admin menu.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Registers the admin menu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'Banners', 'simple-add-banners' ),
			__( 'Banners', 'simple-add-banners' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-images-alt2',
			30
		);
	}

	/**
	 * Renders the admin page container for the Vue app.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_page(): void {
		// Check user capability.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'simple-add-banners' ) );
		}

		?>
		<div class="wrap">
			<div id="sab-admin-app">
				<!-- Vue app mounts here -->
				<p><?php esc_html_e( 'Loading...', 'simple-add-banners' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueues admin assets (Vue app) on the plugin page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Only load on our admin page.
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook_suffix ) {
			return;
		}

		$asset_path = SIMPLE_ADD_BANNERS_PLUGIN_DIR . 'assets/admin/';
		$asset_url  = SIMPLE_ADD_BANNERS_PLUGIN_URL . 'assets/admin/';

		// Check if built assets exist.
		if ( file_exists( $asset_path . 'js/admin.js' ) ) {
			// Production: Load built assets.
			wp_enqueue_script(
				'sab-admin',
				$asset_url . 'js/admin.js',
				array(),
				SIMPLE_ADD_BANNERS_VERSION,
				true
			);

			// Add type="module" to the script.
			add_filter( 'script_loader_tag', array( $this, 'add_module_type' ), 10, 2 );

			// Load CSS if it exists.
			if ( file_exists( $asset_path . 'css/admin.css' ) ) {
				wp_enqueue_style(
					'sab-admin',
					$asset_url . 'css/admin.css',
					array(),
					SIMPLE_ADD_BANNERS_VERSION
				);
			}
		} else {
			// Development: Load from Vite dev server.
			// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_script(
				'sab-admin-vite',
				'http://localhost:5173/@vite/client',
				array(),
				null,
				true
			);

			wp_enqueue_script(
				'sab-admin',
				'http://localhost:5173/src/main.ts',
				array( 'sab-admin-vite' ),
				null,
				true
			);
			// phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion

			// Add type="module" to the scripts.
			add_filter( 'script_loader_tag', array( $this, 'add_module_type' ), 10, 2 );
		}

		// Pass data to JavaScript.
		wp_localize_script(
			'sab-admin',
			'sabAdmin',
			array(
				'apiUrl'   => rest_url( 'sab/v1' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'adminUrl' => admin_url(),
			)
		);
	}

	/**
	 * Adds type="module" attribute to Vite scripts.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tag    The script tag.
	 * @param string $handle The script handle.
	 * @return string Modified script tag.
	 */
	public function add_module_type( string $tag, string $handle ): string {
		if ( 'sab-admin' === $handle || 'sab-admin-vite' === $handle ) {
			$tag = str_replace( '<script ', '<script type="module" ', $tag );
		}
		return $tag;
	}
}

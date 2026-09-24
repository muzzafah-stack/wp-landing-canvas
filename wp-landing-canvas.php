<?php
/**
 * Plugin Name:       WP LandingCanvas
 * Plugin URI:        https://github.com/muzzafah-stack
 * Description:       WP LandingCanvas lets you create lightweight, full-page WordPress landing pages using your own HTML—without relying on heavy page builders.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Tested up to:      7.1.2
 * Requires PHP:      7.4
 * Author:            Hipnolink Digital Team
 * Author URI:        https://www.hipnolink.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-landingcanvas
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
final class WP_LandingCanvas {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.1.0';

	/**
	 * Minimum PHP version required.
	 *
	 * @var string
	 */
	const MIN_PHP_VERSION = '7.4';

	/**
	 * Minimum WordPress version required.
	 *
	 * @var string
	 */
	const MIN_WP_VERSION = '6.0';

	/**
	 * Singleton instance.
	 *
	 * @var WP_LandingCanvas|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return WP_LandingCanvas
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->define_constants();

		if ( ! $this->is_compatible() ) {
			add_action( 'admin_notices', array( $this, 'render_compatibility_notice' ) );
			return;
		}

		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Check PHP and WordPress version compatibility.
	 *
	 * @return bool
	 */
	public function is_compatible() {
		if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
			return false;
		}

		global $wp_version;
		if ( version_compare( $wp_version, self::MIN_WP_VERSION, '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Render admin notice if environment does not meet minimum requirements.
	 */
	public function render_compatibility_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: 1: Minimum PHP version, 2: Minimum WP version */
					esc_html__( 'WP LandingCanvas requires PHP %1$s+ and WordPress %2$s+ to function properly.', 'wp-landingcanvas' ),
					esc_html( self::MIN_PHP_VERSION ),
					esc_html( self::MIN_WP_VERSION )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants() {
		define( 'WPLC_VERSION', self::VERSION );
		define( 'WPLC_PLUGIN_FILE', __FILE__ );
		define( 'WPLC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'WPLC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'WPLC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once WPLC_PLUGIN_DIR . 'includes/class-wplc-security.php';
		require_once WPLC_PLUGIN_DIR . 'includes/class-wplc-admin.php';
		require_once WPLC_PLUGIN_DIR . 'includes/class-wplc-assets.php';
		require_once WPLC_PLUGIN_DIR . 'includes/class-wplc-renderer.php';
		require_once WPLC_PLUGIN_DIR . 'includes/class-wplc-seo.php';
	}

	/**
	 * Initialize plugin hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Plugins loaded callback.
	 */
	public function on_plugins_loaded() {
		load_plugin_textdomain(
			'wp-landingcanvas',
			false,
			dirname( WPLC_PLUGIN_BASENAME ) . '/languages/'
		);

		// Initialize components.
		WPLC_Admin::init();
		WPLC_Assets::init();
		WPLC_Renderer::init();
		WPLC_SEO::init();
	}

	/**
	 * Plugin activation routine.
	 */
	public function activate() {
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation routine.
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}
}

/**
 * Initialize and return plugin instance.
 *
 * @return WP_LandingCanvas
 */
function wplc() {
	return WP_LandingCanvas::get_instance();
}

// Start plugin.
wplc();

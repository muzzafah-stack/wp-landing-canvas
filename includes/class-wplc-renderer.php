<?php
/**
 * Frontend Template Router & Asset Isolator for WP LandingCanvas
 *
 * @package WP_LandingCanvas
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLC_Renderer {

	/**
	 * Initialize rendering hooks.
	 */
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'route_template' ), 99999 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'isolate_assets' ), 99999 );
		add_filter( 'body_class', array( __CLASS__, 'filter_body_class' ), 99999 );
	}

	/**
	 * Check if current page is in LandingCanvas mode.
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return bool
	 */
	public static function is_canvas_active( $post_id = null ) {
		if ( ! $post_id ) {
			if ( ! is_singular() ) {
				return false;
			}
			$post_id = get_the_ID();
		}

		if ( ! $post_id ) {
			return false;
		}

		$is_canvas = get_post_meta( $post_id, WPLC_Admin::META_ENABLE_CANVAS, true );
		return '1' === $is_canvas;
	}

	/**
	 * Override the template file if LandingCanvas is active.
	 *
	 * @param string $template Current template file path.
	 * @return string Modified or original template path.
	 */
	public static function route_template( $template ) {
		if ( ! self::is_canvas_active() ) {
			return $template;
		}

		// Send no-cache headers during post previews.
		if ( is_preview() ) {
			nocache_headers();
		}

		$canvas_template = WPLC_PLUGIN_DIR . 'templates/canvas-template.php';

		if ( file_exists( $canvas_template ) ) {
			return $canvas_template;
		}

		return $template;
	}

	/**
	 * Dequeue theme styles & page builder assets to prevent layout/CSS bleed.
	 */
	public static function isolate_assets() {
		if ( ! self::is_canvas_active() ) {
			return;
		}

		// Allow disabling asset isolation via filter.
		if ( ! apply_filters( 'wplc_enable_asset_isolation', true ) ) {
			return;
		}

		global $wp_styles, $wp_scripts;

		// Default handles from popular builders & themes to dequeue on pure canvas.
		$default_dequeue_styles = array(
			// Active Theme handles
			get_stylesheet(),
			get_template(),
			get_stylesheet() . '-style',
			get_template() . '-style',
			'twentytwentyfour-style',
			'twentytwentyfive-style',
			'twentytwentysix-style',
			'astra-theme-css',
			'generatepress-style',
			'oceanwp-style',
			'kadence-global',

			// Elementor
			'elementor-frontend',
			'elementor-post-' . get_the_ID(),
			'elementor-global',
			'elementor-icons',
			'elementor-pro',

			// Divi
			'et-builder-modules-style',
			'divi-style',
			'et-shortcodes-responsive-css',

			// Bricks
			'bricks-main',
			'bricks-frontend',

			// Block library (WordPress default blocks & FSE supports)
			'wp-block-library',
			'wp-block-library-theme',
			'global-styles',
			'core-block-supports',
			'wp-elements',
			'wp-block-template-skip-link',
		);

		$styles_to_dequeue = apply_filters( 'wplc_dequeue_style_handles', $default_dequeue_styles );

		foreach ( $styles_to_dequeue as $handle ) {
			if ( ! empty( $handle ) && wp_style_is( $handle, 'enqueued' ) ) {
				wp_dequeue_style( $handle );
			}
		}

		// Builder scripts dequeue (Elementor/Divi frontends if not needed)
		$default_dequeue_scripts = array(
			'elementor-frontend',
			'elementor-pro-frontend',
			'et-builder-modules-script',
			'bricks-scripts',
		);

		$scripts_to_dequeue = apply_filters( 'wplc_dequeue_script_handles', $default_dequeue_scripts );

		foreach ( $scripts_to_dequeue as $handle ) {
			if ( ! empty( $handle ) && wp_script_is( $handle, 'enqueued' ) ) {
				wp_dequeue_script( $handle );
			}
		}
	}

	/**
	 * Clean and format body classes for blank canvas.
	 *
	 * @param array $classes Existing body classes.
	 * @return array
	 */
	public static function filter_body_class( $classes ) {
		if ( ! self::is_canvas_active() ) {
			return $classes;
		}

		// Keep essential classes, add wplc-canvas-mode.
		$clean_classes = array( 'wplc-canvas-page', 'wplc-landing-canvas' );

		if ( is_user_logged_in() ) {
			$clean_classes[] = 'logged-in';
			if ( is_admin_bar_showing() ) {
				$clean_classes[] = 'admin-bar';
			}
		}

		return apply_filters( 'wplc_canvas_body_classes', $clean_classes, get_the_ID() );
	}
}

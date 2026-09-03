<?php
/**
 * Admin Assets & CodeMirror Manager for WP LandingCanvas
 *
 * @package WP_LandingCanvas
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLC_Assets {

	/**
	 * Initialize asset hooks.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue styles and scripts on post edit screens.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_admin_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, WPLC_Admin::get_supported_post_types(), true ) ) {
			return;
		}

		// Enqueue WordPress core CodeMirror settings.
		$cm_html_settings = wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
		$cm_css_settings  = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
		$cm_js_settings   = wp_enqueue_code_editor( array( 'type' => 'text/html' ) ); // mixed mode supports <script> tags

		// Enqueue admin custom CSS.
		wp_enqueue_style(
			'wplc-admin-style',
			WPLC_PLUGIN_URL . 'assets/css/admin-style.css',
			array(),
			WPLC_VERSION
		);

		// Enqueue admin JS.
		wp_enqueue_script(
			'wplc-admin-editor',
			WPLC_PLUGIN_URL . 'assets/js/admin-editor.js',
			array( 'jquery' ),
			WPLC_VERSION,
			true
		);

		// Pass CodeMirror settings and localized strings to JS.
		wp_localize_script(
			'wplc-admin-editor',
			'wplcSettings',
			array(
				'cmHtml' => false !== $cm_html_settings ? $cm_html_settings : array(),
				'cmCss'  => false !== $cm_css_settings ? $cm_css_settings : array(),
				'cmJs'   => false !== $cm_js_settings ? $cm_js_settings : array(),
				'i18n'   => array(
					'active'   => __( 'Active', 'wp-landingcanvas' ),
					'disabled' => __( 'Disabled', 'wp-landingcanvas' ),
				),
			)
		);
	}
}

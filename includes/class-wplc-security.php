<?php
/**
 * Security & Permission Handler for WP LandingCanvas
 *
 * @package WP_LandingCanvas
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLC_Security {

	/**
	 * Nonce action name for meta box save.
	 */
	const NONCE_ACTION = 'wplc_save_canvas_meta';

	/**
	 * Nonce field name in form.
	 */
	const NONCE_NAME = 'wplc_canvas_nonce';

	/**
	 * Verify nonce from request.
	 *
	 * @return bool True if valid, false otherwise.
	 */
	public static function verify_nonce() {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	/**
	 * Check if current user can edit the given post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function can_edit( $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Check if current user has permission to save unfiltered HTML & JavaScript.
	 *
	 * @return bool
	 */
	public static function can_save_unfiltered_html() {
		return current_user_can( 'unfiltered_html' );
	}

	/**
	 * Sanitize HTML content based on user capability.
	 *
	 * Users with `unfiltered_html` capability (Administrators/Super Admins) get
	 * unmanipulated raw HTML/JS. Lower privileged users are filtered via wp_kses_post.
	 *
	 * @param string $raw_content Raw content from form.
	 * @return string Sanitized or raw content.
	 */
	public static function sanitize_html_content( $raw_content ) {
		if ( self::can_save_unfiltered_html() ) {
			// Intentionally preserve exact HTML/JS for administrators.
			return $raw_content;
		}

		// Ponytail: fallback safety for users without unfiltered_html capability.
		return wp_kses_post( $raw_content );
	}

	/**
	 * Sanitize raw CSS code.
	 *
	 * @param string $raw_css Raw CSS string.
	 * @return string
	 */
	public static function sanitize_css_content( $raw_css ) {
		// Strip tags to prevent script injection inside <style> blocks.
		return wp_strip_all_tags( $raw_css );
	}

	/**
	 * Sanitize raw script code (Head/Footer tags).
	 *
	 * @param string $raw_script Raw script string.
	 * @return string
	 */
	public static function sanitize_script_content( $raw_script ) {
		if ( self::can_save_unfiltered_html() ) {
			return $raw_script;
		}

		// Non-admin users cannot insert arbitrary script tags.
		return '';
	}
}

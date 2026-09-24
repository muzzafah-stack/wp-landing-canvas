<?php
/**
 * SEO Plugins Integration Bridge for WP LandingCanvas
 *
 * Integrates raw LandingCanvas HTML with popular WordPress SEO plugins
 * (Rank Math, Yoast SEO, SEOPress, Slim SEO, AIOSEO, The SEO Framework).
 *
 * @package WP_LandingCanvas
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLC_SEO {

	/**
	 * Initialize SEO integration hooks.
	 */
	public static function init() {
		// 1. Content Analysis Filters (Admin & Server-side evaluation).
		add_filter( 'rank_math/admin/filter_content_for_analysis', array( __CLASS__, 'filter_content' ), 10, 2 );
		add_filter( 'rank_math/sitemap/content', array( __CLASS__, 'filter_content' ), 10, 2 );
		add_filter( 'wpseo_pre_analysis_post_content', array( __CLASS__, 'filter_content' ), 10, 2 );
		add_filter( 'wpseo_content', array( __CLASS__, 'filter_content' ), 10, 1 );
		add_filter( 'seopress_content_analysis_content', array( __CLASS__, 'filter_content' ), 10, 2 );

		// 2. Automated Meta Description Fallbacks.
		add_filter( 'rank_math/paper/auto_description', array( __CLASS__, 'fallback_description' ), 10, 1 );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'fallback_description' ), 10, 2 );
		add_filter( 'seopress_titles_desc', array( __CLASS__, 'fallback_description' ), 10, 1 );
		add_filter( 'slim_seo_meta_description', array( __CLASS__, 'fallback_description' ), 10, 2 );
		add_filter( 'aioseo_description', array( __CLASS__, 'fallback_description' ), 10, 1 );
		add_filter( 'the_seo_framework_generated_description', array( __CLASS__, 'fallback_description' ), 10, 2 );
		add_filter( 'get_the_excerpt', array( __CLASS__, 'fallback_core_excerpt' ), 10, 2 );

		// 3. Social / OpenGraph Image Fallbacks.
		add_filter( 'rank_math/opengraph/facebook/image', array( __CLASS__, 'fallback_image' ), 10, 1 );
		add_filter( 'rank_math/opengraph/twitter/image', array( __CLASS__, 'fallback_image' ), 10, 1 );
		add_filter( 'wpseo_opengraph_image', array( __CLASS__, 'fallback_image' ), 10, 1 );
		add_filter( 'seopress_social_og_img', array( __CLASS__, 'fallback_image' ), 10, 1 );
		add_filter( 'slim_seo_open_graph_image', array( __CLASS__, 'fallback_image' ), 10, 2 );
		add_filter( 'slim_seo_twitter_image', array( __CLASS__, 'fallback_image' ), 10, 2 );
		add_filter( 'the_seo_framework_og_image_url', array( __CLASS__, 'fallback_image' ), 10, 2 );
	}

	/**
	 * Resolve post ID from various plugin callback signatures.
	 *
	 * @param mixed $post Post ID, WP_Post, or presentation object.
	 * @return int
	 */
	private static function resolve_post_id( $post = null ) {
		if ( is_numeric( $post ) && (int) $post > 0 ) {
			return (int) $post;
		}
		if ( $post instanceof WP_Post ) {
			return $post->ID;
		}
		if ( is_object( $post ) && isset( $post->model->id ) ) {
			return (int) $post->model->id;
		}
		if ( is_object( $post ) && isset( $post->ID ) ) {
			return (int) $post->ID;
		}
		return get_the_ID() ?: 0;
	}

	/**
	 * Get Canvas HTML for a post if active.
	 *
	 * @param int|null $post_id Post ID.
	 * @return string|null Canvas HTML string or null if inactive.
	 */
	public static function get_canvas_html( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id || ! WPLC_Renderer::is_canvas_active( $post_id ) ) {
			return null;
		}

		return get_post_meta( $post_id, WPLC_Admin::META_HTML_CONTENT, true );
	}

	/**
	 * Extract plain text summary from HTML.
	 *
	 * @param string $html Raw HTML content.
	 * @param int    $max_words Maximum word count.
	 * @return string Plain text summary.
	 */
	public static function extract_plain_text( $html, $max_words = 35 ) {
		if ( empty( $html ) ) {
			return '';
		}

		$clean = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $html );
		$clean = strip_shortcodes( $clean );
		$clean = wp_strip_all_tags( $clean, true );
		$clean = trim( preg_replace( '/\s+/', ' ', $clean ) );

		if ( empty( $clean ) ) {
			return '';
		}

		return wp_trim_words( $clean, $max_words, '...' );
	}

	/**
	 * Extract first image URL from HTML.
	 *
	 * @param string $html Raw HTML content.
	 * @return string Image URL or empty string.
	 */
	public static function extract_first_image_url( $html ) {
		if ( empty( $html ) ) {
			return '';
		}

		if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $match ) ) {
			return esc_url_raw( $match[1] );
		}

		return '';
	}

	/**
	 * General content filter for SEO analyzers and sitemaps.
	 *
	 * @param string $content Default content.
	 * @param mixed  $post Post ID or post object.
	 * @return string
	 */
	public static function filter_content( $content, $post = null ) {
		$post_id     = self::resolve_post_id( $post );
		$canvas_html = self::get_canvas_html( $post_id );
		return ( null !== $canvas_html && '' !== trim( $canvas_html ) ) ? $canvas_html : $content;
	}

	/**
	 * General fallback meta description handler for SEO plugins.
	 *
	 * @param string $description Existing description.
	 * @param mixed  $post Post ID, WP_Post, or presentation object.
	 * @return string
	 */
	public static function fallback_description( $description, $post = null ) {
		if ( ! empty( $description ) ) {
			return $description;
		}

		$post_id     = self::resolve_post_id( $post );
		$canvas_html = self::get_canvas_html( $post_id );
		return null !== $canvas_html ? self::extract_plain_text( $canvas_html ) : $description;
	}

	/**
	 * Fallback excerpt for core WordPress.
	 *
	 * @param string  $excerpt Current excerpt.
	 * @param WP_Post $post Current post object.
	 * @return string
	 */
	public static function fallback_core_excerpt( $excerpt, $post = null ) {
		if ( ! empty( $excerpt ) ) {
			return $excerpt;
		}

		$post_id     = self::resolve_post_id( $post );
		$canvas_html = self::get_canvas_html( $post_id );
		return null !== $canvas_html ? self::extract_plain_text( $canvas_html, 55 ) : $excerpt;
	}

	/**
	 * General fallback social / OpenGraph image handler.
	 *
	 * @param string $image_url Existing image URL.
	 * @param mixed  $post Post ID or object.
	 * @return string
	 */
	public static function fallback_image( $image_url, $post = null ) {
		if ( ! empty( $image_url ) ) {
			return $image_url;
		}

		$post_id = self::resolve_post_id( $post );
		if ( $post_id && has_post_thumbnail( $post_id ) ) {
			return $image_url;
		}

		$canvas_html = self::get_canvas_html( $post_id );
		if ( null !== $canvas_html ) {
			$found_image = self::extract_first_image_url( $canvas_html );
			if ( ! empty( $found_image ) ) {
				return $found_image;
			}
		}

		return $image_url;
	}
}

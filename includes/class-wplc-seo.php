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
		add_filter( 'rank_math/admin/filter_content_for_analysis', array( __CLASS__, 'filter_content_for_analysis' ), 10, 2 );
		add_filter( 'rank_math/sitemap/content', array( __CLASS__, 'filter_sitemap_content' ), 10, 2 );
		add_filter( 'wpseo_pre_analysis_post_content', array( __CLASS__, 'filter_yoast_analysis_content' ), 10, 2 );
		add_filter( 'wpseo_content', array( __CLASS__, 'filter_yoast_content' ), 10, 2 );
		add_filter( 'seopress_content_analysis_content', array( __CLASS__, 'filter_seopress_analysis_content' ), 10, 2 );

		// 2. Automated Meta Description Fallbacks.
		add_filter( 'rank_math/paper/auto_description', array( __CLASS__, 'fallback_meta_description' ), 10, 1 );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'fallback_yoast_description' ), 10, 2 );
		add_filter( 'seopress_titles_desc', array( __CLASS__, 'fallback_seopress_description' ), 10, 1 );
		add_filter( 'slim_seo_meta_description', array( __CLASS__, 'fallback_slim_seo_description' ), 10, 2 );
		add_filter( 'aioseo_description', array( __CLASS__, 'fallback_aioseo_description' ), 10, 1 );
		add_filter( 'the_seo_framework_generated_description', array( __CLASS__, 'fallback_tsf_description' ), 10, 2 );
		add_filter( 'get_the_excerpt', array( __CLASS__, 'fallback_core_excerpt' ), 10, 2 );

		// 3. Social / OpenGraph Image Fallbacks (Extracts first <img> from canvas HTML if no featured image).
		add_filter( 'rank_math/opengraph/facebook/image', array( __CLASS__, 'fallback_social_image' ), 10, 1 );
		add_filter( 'rank_math/opengraph/twitter/image', array( __CLASS__, 'fallback_social_image' ), 10, 1 );
		add_filter( 'wpseo_opengraph_image', array( __CLASS__, 'fallback_social_image' ), 10, 1 );
		add_filter( 'seopress_social_og_img', array( __CLASS__, 'fallback_social_image' ), 10, 1 );
		add_filter( 'slim_seo_open_graph_image', array( __CLASS__, 'fallback_slim_seo_image' ), 10, 2 );
		add_filter( 'slim_seo_twitter_image', array( __CLASS__, 'fallback_slim_seo_image' ), 10, 2 );
		add_filter( 'the_seo_framework_og_image_url', array( __CLASS__, 'fallback_tsf_image' ), 10, 2 );
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

		// Strip script, style, and comments before stripping all HTML tags.
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
	 * Filter content for Rank Math analysis.
	 *
	 * @param string $content Default content.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public static function filter_content_for_analysis( $content, $post_id ) {
		$canvas_html = self::get_canvas_html( $post_id );
		return null !== $canvas_html && '' !== trim( $canvas_html ) ? $canvas_html : $content;
	}

	/**
	 * Filter content for Rank Math sitemaps.
	 *
	 * @param string $content Default content.
	 * @param object $post Post object.
	 * @return string
	 */
	public static function filter_sitemap_content( $content, $post ) {
		if ( empty( $post->ID ) ) {
			return $content;
		}
		$canvas_html = self::get_canvas_html( $post->ID );
		return null !== $canvas_html && '' !== trim( $canvas_html ) ? $canvas_html : $content;
	}

	/**
	 * Filter content for Yoast SEO pre-analysis.
	 *
	 * @param string  $content Default content.
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	public static function filter_yoast_analysis_content( $content, $post ) {
		if ( empty( $post->ID ) ) {
			return $content;
		}
		$canvas_html = self::get_canvas_html( $post->ID );
		return null !== $canvas_html && '' !== trim( $canvas_html ) ? $canvas_html : $content;
	}

	/**
	 * Filter content for Yoast SEO frontend.
	 *
	 * @param string $content Default content.
	 * @return string
	 */
	public static function filter_yoast_content( $content ) {
		$canvas_html = self::get_canvas_html();
		return null !== $canvas_html && '' !== trim( $canvas_html ) ? $canvas_html : $content;
	}

	/**
	 * Filter content for SEOPress analysis.
	 *
	 * @param string $content Default content.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public static function filter_seopress_analysis_content( $content, $post_id ) {
		$canvas_html = self::get_canvas_html( $post_id );
		return null !== $canvas_html && '' !== trim( $canvas_html ) ? $canvas_html : $content;
	}

	/**
	 * Fallback meta description for Rank Math.
	 *
	 * @param string $description Existing description.
	 * @return string
	 */
	public static function fallback_meta_description( $description ) {
		if ( ! empty( $description ) ) {
			return $description;
		}

		$canvas_html = self::get_canvas_html();
		if ( null !== $canvas_html ) {
			return self::extract_plain_text( $canvas_html );
		}

		return $description;
	}

	/**
	 * Fallback meta description for Yoast SEO.
	 *
	 * @param string $description Existing description.
	 * @param object $presentation Yoast presentation object or post.
	 * @return string
	 */
	public static function fallback_yoast_description( $description, $presentation = null ) {
		if ( ! empty( $description ) ) {
			return $description;
		}

		$post_id = is_object( $presentation ) && isset( $presentation->model->id ) ? $presentation->model->id : get_the_ID();
		$canvas_html = self::get_canvas_html( $post_id );
		if ( null !== $canvas_html ) {
			return self::extract_plain_text( $canvas_html );
		}

		return $description;
	}

	/**
	 * Fallback meta description for SEOPress.
	 *
	 * @param string $description Existing description.
	 * @return string
	 */
	public static function fallback_seopress_description( $description ) {
		if ( ! empty( $description ) ) {
			return $description;
		}

		$canvas_html = self::get_canvas_html();
		if ( null !== $canvas_html ) {
			return self::extract_plain_text( $canvas_html );
		}

		return $description;
	}

	/**
	 * Fallback meta description for Slim SEO.
	 *
	 * @param string $description Existing description.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public static function fallback_slim_seo_description( $description, $post_id ) {
		if ( ! empty( $description ) ) {
			return $description;
		}

		$canvas_html = self::get_canvas_html( $post_id );
		if ( null !== $canvas_html ) {
			return self::extract_plain_text( $canvas_html );
		}

		return $description;
	}

	/**
	 * Fallback meta description for AIOSEO.
	 *
	 * @param string $description Existing description.
	 * @return string
	 */
	public static function fallback_aioseo_description( $description ) {
		if ( ! empty( $description ) ) {
			return $description;
		}

		$canvas_html = self::get_canvas_html();
		if ( null !== $canvas_html ) {
			return self::extract_plain_text( $canvas_html );
		}

		return $description;
	}

	/**
	 * Fallback meta description for The SEO Framework.
	 *
	 * @param string   $description Existing description.
	 * @param int|null $post_id Post ID.
	 * @return string
	 */
	public static function fallback_tsf_description( $description, $post_id = null ) {
		if ( ! empty( $description ) ) {
			return $description;
		}

		$canvas_html = self::get_canvas_html( $post_id );
		if ( null !== $canvas_html ) {
			return self::extract_plain_text( $canvas_html );
		}

		return $description;
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

		$post_id = $post instanceof WP_Post ? $post->ID : get_the_ID();
		$canvas_html = self::get_canvas_html( $post_id );

		if ( null !== $canvas_html ) {
			return self::extract_plain_text( $canvas_html, 55 );
		}

		return $excerpt;
	}

	/**
	 * Fallback social image for Rank Math, Yoast, and SEOPress.
	 *
	 * @param string $image_url Existing image URL.
	 * @return string
	 */
	public static function fallback_social_image( $image_url ) {
		if ( ! empty( $image_url ) ) {
			return $image_url;
		}

		$post_id = get_the_ID();
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

	/**
	 * Fallback social image for Slim SEO.
	 *
	 * @param string $image_url Existing image URL.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public static function fallback_slim_seo_image( $image_url, $post_id ) {
		if ( ! empty( $image_url ) || has_post_thumbnail( $post_id ) ) {
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

	/**
	 * Fallback social image for The SEO Framework.
	 *
	 * @param string   $image_url Existing image URL.
	 * @param int|null $post_id Post ID.
	 * @return string
	 */
	public static function fallback_tsf_image( $image_url, $post_id = null ) {
		if ( ! empty( $image_url ) || ( $post_id && has_post_thumbnail( $post_id ) ) ) {
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

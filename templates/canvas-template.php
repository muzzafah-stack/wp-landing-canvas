<?php
/**
 * Pristine Blank Canvas Template for WP LandingCanvas
 *
 * Provides a clean, lightweight HTML5 container without theme headers, footers,
 * navigation, or page builder wrappers, while preserving full SEO and analytics hooks.
 *
 * @package WP_LandingCanvas
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id        = get_the_ID();
$html_content   = get_post_meta( $post_id, WPLC_Admin::META_HTML_CONTENT, true );
$custom_css     = get_post_meta( $post_id, WPLC_Admin::META_CUSTOM_CSS, true );
$head_scripts   = get_post_meta( $post_id, WPLC_Admin::META_HEAD_SCRIPTS, true );
$footer_scripts = get_post_meta( $post_id, WPLC_Admin::META_FOOTER_SCRIPTS, true );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	
	<?php
	// Essential WordPress SEO, OpenGraph, Canonical, and Resource Hook.
	wp_head();
	?>

	<!-- WP LandingCanvas Minimal Reset -->
	<style id="wplc-canvas-reset">
		html, body {
			margin: 0;
			padding: 0;
			width: 100%;
			min-height: 100vh;
			-webkit-text-size-adjust: 100%;
		}
	</style>

	<?php if ( ! empty( $custom_css ) ) : ?>
		<!-- WP LandingCanvas Custom CSS -->
		<style id="wplc-canvas-custom-css">
			<?php echo wp_strip_all_tags( $custom_css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>
	<?php endif; ?>

	<?php if ( ! empty( $head_scripts ) ) : ?>
		<!-- WP LandingCanvas Head Scripts & Tracking Pixels -->
		<?php echo $head_scripts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
</head>

<body <?php body_class( 'wplc-canvas-body' ); ?>>
<?php
// Modern WordPress hook for GTM / Body Tracking scripts.
if ( function_exists( 'wp_body_open' ) ) {
	wp_body_open();
}
?>

<div id="wplc-landing-content" class="wplc-content-wrapper">
	<?php
	if ( ! empty( $html_content ) ) {
		// Output raw HTML with shortcode expansion support.
		echo do_shortcode( $html_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</div>

<?php if ( ! empty( $footer_scripts ) ) : ?>
	<!-- WP LandingCanvas Footer Scripts -->
	<?php echo $footer_scripts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endif; ?>

<?php
// Essential WordPress Footer Hook (Analytics, Admin bar if logged in, etc).
wp_footer();
?>
</body>
</html>

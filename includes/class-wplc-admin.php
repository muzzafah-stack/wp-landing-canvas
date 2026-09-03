<?php
/**
 * Admin Meta Box & UI Controller for WP LandingCanvas
 *
 * @package WP_LandingCanvas
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLC_Admin {

	/**
	 * Meta key constants.
	 */
	const META_ENABLE_CANVAS   = '_wplc_enable_canvas';
	const META_HTML_CONTENT    = '_wplc_html_content';
	const META_CUSTOM_CSS      = '_wplc_custom_css';
	const META_HEAD_SCRIPTS    = '_wplc_head_scripts';
	const META_FOOTER_SCRIPTS  = '_wplc_footer_scripts';

	/**
	 * Initialize admin hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta' ), 10, 2 );
		add_filter( 'display_post_states', array( __CLASS__, 'add_post_state' ), 10, 2 );
	}

	/**
	 * Get supported post types for LandingCanvas.
	 *
	 * @return array
	 */
	public static function get_supported_post_types() {
		$default_types = array( 'page', 'post' );
		return apply_filters( 'wplc_supported_post_types', $default_types );
	}

	/**
	 * Register meta box on post edit screens.
	 */
	public static function register_meta_boxes() {
		$screens = self::get_supported_post_types();

		foreach ( $screens as $screen ) {
			add_meta_box(
				'wplc_landing_canvas_metabox',
				__( '⚡ WP LandingCanvas — Landing Page Editor', 'wp-landingcanvas' ),
				array( __CLASS__, 'render_meta_box' ),
				$screen,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Add visual badge in Page/Post list table when Canvas Mode is active.
	 *
	 * @param array   $post_states Existing post states.
	 * @param WP_Post $post Current post object.
	 * @return array
	 */
	public static function add_post_state( $post_states, $post ) {
		$is_canvas = get_post_meta( $post->ID, self::META_ENABLE_CANVAS, true );
		if ( '1' === $is_canvas ) {
			$post_states['wplc_canvas'] = __( '⚡ LandingCanvas', 'wp-landingcanvas' );
		}
		return $post_states;
	}

	/**
	 * Render the Meta Box UI.
	 *
	 * @param WP_Post $post Current post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( WPLC_Security::NONCE_ACTION, WPLC_Security::NONCE_NAME );

		$enable_canvas   = get_post_meta( $post->ID, self::META_ENABLE_CANVAS, true );
		$html_content    = get_post_meta( $post->ID, self::META_HTML_CONTENT, true );
		$custom_css      = get_post_meta( $post->ID, self::META_CUSTOM_CSS, true );
		$head_scripts    = get_post_meta( $post->ID, self::META_HEAD_SCRIPTS, true );
		$footer_scripts  = get_post_meta( $post->ID, self::META_FOOTER_SCRIPTS, true );
		$has_permission  = WPLC_Security::can_save_unfiltered_html();
		$preview_url     = get_preview_post_link( $post );
		?>
		<div class="wplc-meta-box-wrap" id="wplc-app-container">
			
			<!-- Header / Mode Switcher -->
			<div class="wplc-header">
				<div class="wplc-toggle-group">
					<label class="wplc-switch">
						<input type="checkbox" name="<?php echo esc_attr( self::META_ENABLE_CANVAS ); ?>" id="wplc-enable-canvas" value="1" <?php checked( $enable_canvas, '1' ); ?>>
						<span class="wplc-slider"></span>
					</label>
					<div class="wplc-toggle-info">
						<label for="wplc-enable-canvas" class="wplc-toggle-title">
							<?php esc_html_e( 'Enable Landing Page Mode', 'wp-landingcanvas' ); ?>
						</label>
						<p class="wplc-toggle-desc">
							<?php esc_html_e( 'Bypasses theme layout, headers, footers & page builders to render pure HTML.', 'wp-landingcanvas' ); ?>
						</p>
					</div>
				</div>

				<div class="wplc-actions">
					<span class="wplc-badge <?php echo ( '1' === $enable_canvas ) ? 'wplc-badge-active' : 'wplc-badge-inactive'; ?>" id="wplc-status-badge">
						<?php echo ( '1' === $enable_canvas ) ? esc_html__( 'Active', 'wp-landingcanvas' ) : esc_html__( 'Disabled', 'wp-landingcanvas' ); ?>
					</span>
					<?php if ( ! empty( $preview_url ) ) : ?>
						<a href="<?php echo esc_url( $preview_url ); ?>" target="_blank" class="button wplc-btn-preview" id="wplc-preview-btn">
							<span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Preview Canvas', 'wp-landingcanvas' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- User Notice for Non-Admins -->
			<?php if ( ! $has_permission ) : ?>
				<div class="wplc-alert wplc-alert-warning">
					<span class="dashicons dashicons-warning"></span>
					<span><?php esc_html_e( 'Notice: You do not have unfiltered_html permission. Custom scripts (<script>) will be stripped upon saving.', 'wp-landingcanvas' ); ?></span>
				</div>
			<?php endif; ?>

			<!-- Editor Panel (Shown when Canvas is enabled or collapsible) -->
			<div class="wplc-editor-panel <?php echo ( '1' !== $enable_canvas ) ? 'wplc-hidden' : ''; ?>" id="wplc-editor-panel">
				
				<!-- Tabs Navigation -->
				<div class="wplc-tabs-nav" role="tablist">
					<button type="button" class="wplc-tab-btn active" data-tab="tab-html" role="tab" aria-selected="true">
						<span class="dashicons dashicons-html"></span> <?php esc_html_e( 'HTML Body', 'wp-landingcanvas' ); ?>
					</button>
					<button type="button" class="wplc-tab-btn" data-tab="tab-css" role="tab" aria-selected="false">
						<span class="dashicons dashicons-art"></span> <?php esc_html_e( 'Custom CSS', 'wp-landingcanvas' ); ?>
					</button>
					<button type="button" class="wplc-tab-btn" data-tab="tab-head" role="tab" aria-selected="false">
						<span class="dashicons dashicons-code-standards"></span> <?php esc_html_e( 'Head Scripts (<head>)', 'wp-landingcanvas' ); ?>
					</button>
					<button type="button" class="wplc-tab-btn" data-tab="tab-footer" role="tab" aria-selected="false">
						<span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'Footer Scripts (<body>)', 'wp-landingcanvas' ); ?>
					</button>
				</div>

				<!-- Tab 1: HTML Body -->
				<div class="wplc-tab-content active" id="tab-html" role="tabpanel">
					<div class="wplc-tab-helper">
						<p><?php esc_html_e( 'Paste your complete Landing Page HTML markup. Shortcodes like [contact-form-7] are supported.', 'wp-landingcanvas' ); ?></p>
					</div>
					<textarea name="<?php echo esc_attr( self::META_HTML_CONTENT ); ?>" id="wplc_html_content" class="wplc-code-editor" rows="18"><?php echo esc_textarea( $html_content ); ?></textarea>
				</div>

				<!-- Tab 2: Custom CSS -->
				<div class="wplc-tab-content" id="tab-css" role="tabpanel">
					<div class="wplc-tab-helper">
						<p><?php esc_html_e( 'Write custom CSS styles. Injected cleanly inside the <head> of this landing page.', 'wp-landingcanvas' ); ?></p>
					</div>
					<textarea name="<?php echo esc_attr( self::META_CUSTOM_CSS ); ?>" id="wplc_custom_css" class="wplc-code-editor" rows="14" placeholder="body { background-color: #0b0f19; font-family: sans-serif; }"><?php echo esc_textarea( $custom_css ); ?></textarea>
				</div>

				<!-- Tab 3: Head Scripts -->
				<div class="wplc-tab-content" id="tab-head" role="tabpanel">
					<div class="wplc-tab-helper">
						<p><?php esc_html_e( 'Insert Google Analytics, Meta Pixel, custom Google Fonts, or meta tags directly into <head>. Include full <script> or <link> tags.', 'wp-landingcanvas' ); ?></p>
					</div>
					<textarea name="<?php echo esc_attr( self::META_HEAD_SCRIPTS ); ?>" id="wplc_head_scripts" class="wplc-code-editor" rows="10" placeholder="<!-- Example: Google Tag Manager / Meta Pixel -->"><?php echo esc_textarea( $head_scripts ); ?></textarea>
				</div>

				<!-- Tab 4: Footer Scripts -->
				<div class="wplc-tab-content" id="tab-footer" role="tabpanel">
					<div class="wplc-tab-helper">
						<p><?php esc_html_e( 'Insert custom JavaScript, tracking widgets, or chat scripts loaded before closing </body> tag.', 'wp-landingcanvas' ); ?></p>
					</div>
					<textarea name="<?php echo esc_attr( self::META_FOOTER_SCRIPTS ); ?>" id="wplc_footer_scripts" class="wplc-code-editor" rows="10" placeholder="<script>console.log('Landing page loaded');</script>"><?php echo esc_textarea( $footer_scripts ); ?></textarea>
				</div>

				<div class="wplc-footer-bar">
					<span class="wplc-tip">
						💡 <strong><?php esc_html_e( 'Pro-Tip:', 'wp-landingcanvas' ); ?></strong> <?php esc_html_e( 'Press Ctrl+F / Cmd+F to search inside the code editor. Toggle Landing Page Mode anytime without losing normal post content.', 'wp-landingcanvas' ); ?>
					</span>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Save Meta Box Data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public static function save_meta( $post_id, $post ) {
		// Verify autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verify revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Verify nonce.
		if ( ! WPLC_Security::verify_nonce() ) {
			return;
		}

		// Verify user capability.
		if ( ! WPLC_Security::can_edit( $post_id ) ) {
			return;
		}

		// Check supported post types.
		if ( ! in_array( $post->post_type, self::get_supported_post_types(), true ) ) {
			return;
		}

		// 1. Enable Canvas toggle.
		$enable_canvas = isset( $_POST[ self::META_ENABLE_CANVAS ] ) ? '1' : '0';
		update_post_meta( $post_id, self::META_ENABLE_CANVAS, $enable_canvas );

		// 2. HTML Content.
		if ( isset( $_POST[ self::META_HTML_CONTENT ] ) ) {
			$raw_html = wp_unslash( $_POST[ self::META_HTML_CONTENT ] );
			$sanitized_html = WPLC_Security::sanitize_html_content( $raw_html );
			update_post_meta( $post_id, self::META_HTML_CONTENT, $sanitized_html );
		}

		// 3. Custom CSS.
		if ( isset( $_POST[ self::META_CUSTOM_CSS ] ) ) {
			$raw_css = wp_unslash( $_POST[ self::META_CUSTOM_CSS ] );
			$sanitized_css = WPLC_Security::sanitize_css_content( $raw_css );
			update_post_meta( $post_id, self::META_CUSTOM_CSS, $sanitized_css );
		}

		// 4. Head Scripts.
		if ( isset( $_POST[ self::META_HEAD_SCRIPTS ] ) ) {
			$raw_head = wp_unslash( $_POST[ self::META_HEAD_SCRIPTS ] );
			$sanitized_head = WPLC_Security::sanitize_script_content( $raw_head );
			update_post_meta( $post_id, self::META_HEAD_SCRIPTS, $sanitized_head );
		}

		// 5. Footer Scripts.
		if ( isset( $_POST[ self::META_FOOTER_SCRIPTS ] ) ) {
			$raw_footer = wp_unslash( $_POST[ self::META_FOOTER_SCRIPTS ] );
			$sanitized_footer = WPLC_Security::sanitize_script_content( $raw_footer );
			update_post_meta( $post_id, self::META_FOOTER_SCRIPTS, $sanitized_footer );
		}
	}
}

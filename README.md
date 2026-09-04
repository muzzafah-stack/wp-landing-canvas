# WP LandingCanvas

> **Build Landing Pages. Skip the Builder.**

WP LandingCanvas lets you create lightweight, full-page WordPress landing pages using your own HTML—without relying on heavy page builders like Elementor, Divi, or Bricks.

- **Author**: Hipnolink Digital Team ([www.hipnolink.com](https://www.hipnolink.com))
- **Plugin URI**: [https://github.com/muzzafah-stack](https://github.com/muzzafah-stack)
- **Version**: 1.0.0
- **Requires at least**: WordPress 7.0+
- **Requires PHP**: 8.2+
- **License**: GPL v2 or later

---

## 🚀 Key Features

1. **Pure Blank Canvas**: Bypasses theme headers, footers, sidebars, navigation, and page builder wrappers.
2. **Page Builder & Theme Isolation**: Automatically dequeues heavy page builder CSS/JS and theme stylesheets on canvas landing pages to eliminate CSS bleed.
3. **Deep SEO Plugin Integration**: Native real-time & server-side bridge for **Rank Math**, **Yoast SEO**, **SEOPress**, **Slim SEO**, **AIOSEO**, and **The SEO Framework**. Scores, focus keywords, word count, auto meta descriptions, and OpenGraph social images are automatically extracted directly from your custom LandingCanvas HTML!
4. **Native CodeMirror Editor**: Instant syntax-highlighted code editor for HTML, CSS, and JS using WordPress core's native engine (zero external dependency bloat).
5. **Full SEO & Tracking Hook Preservation**: Keeps `wp_head()`, `wp_body_open()`, and `wp_footer()` intact so Google Tag Manager, Meta Pixel, Schema.org JSON-LD, and analytics work seamlessly.
6. **Shortcode Compatible**: Embed contact forms, newsletter widgets, or dynamic plugins directly inside your HTML markup using standard WordPress shortcodes (e.g. `[contact-form-7]`, `[fluentform]`).
7. **100% Cache & CDN Friendly**: Fully compatible with Perfmatters, FlyingPress, WP Rocket, LiteSpeed Cache, and Cloudflare.
8. **Enterprise Security**: Strict `unfiltered_html` capability validation, CSRF nonces, and `wp_kses_post` fallbacks for lower privilege roles.

---

## 📂 Folder Structure

```
wp-landing-canvas/
├── wp-landing-canvas.php             # Main plugin bootstrap & constants
├── includes/
│   ├── class-wplc-admin.php          # Admin meta box & UI controller
│   ├── class-wplc-security.php       # Capability checks & contextual sanitization
│   ├── class-wplc-renderer.php       # Template router & asset isolation
│   ├── class-wplc-assets.php         # Admin CSS/JS enqueue & CodeMirror setup
│   └── class-wplc-seo.php            # SEO plugins integration bridge
├── templates/
│   └── canvas-template.php           # Lightweight HTML5 blank canvas layout
└── assets/
    ├── css/
    │   └── admin-style.css           # Modern 2026 admin design system
    └── js/
        └── admin-editor.js           # Tab switcher, CodeMirror manager & SEO bridge
```

---

## 📦 Installation & Setup

1. Download or copy the `wp-landing-canvas` folder to `/wp-content/plugins/`.
2. Navigate to **WordPress Admin → Plugins** and click **Activate** on **WP LandingCanvas**.
3. Create or edit any Page or Post (**Pages → Add New**).
4. Scroll down to the **⚡ WP LandingCanvas** box.
5. Toggle **Enable Landing Page Mode** to ON.
6. Paste your Landing Page HTML in the **HTML Body** tab, add optional CSS or Head/Footer scripts in their respective tabs.
7. Click **Publish** or **Preview Canvas**.

---

## 🔧 Developer Extensibility (Filters & Hooks)

### 1. Add Support for Custom Post Types
```php
add_filter( 'wplc_supported_post_types', function( $post_types ) {
    $post_types[] = 'campaign';
    return $post_types;
} );
```

### 2. Customize Style Handles Dequeued in Canvas Mode
```php
add_filter( 'wplc_dequeue_style_handles', function( $handles ) {
    $handles[] = 'my-custom-plugin-style';
    return $handles;
} );
```

### 3. Disable Asset Isolation (if theme styling is specifically desired)
```php
add_filter( 'wplc_enable_asset_isolation', '__return_false' );
```

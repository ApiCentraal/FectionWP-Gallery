<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once FECTIONWPGALLERY_DIR . 'includes/class-fectionwp-gallery-cpt.php';
require_once FECTIONWPGALLERY_DIR . 'includes/class-fectionwp-gallery-shortcode.php';
require_once FECTIONWPGALLERY_DIR . 'includes/class-fectionwp-gallery-widget.php';
require_once FECTIONWPGALLERY_DIR . 'includes/admin/class-fectionwp-gallery-admin.php';

class FectionWP_Gallery
{
    private const OPTION_CACHE_VERSION = 'fectionwp_gallery_cache_v';
    private const META_GALLERY_CACHE_VERSION = '_fection_gallery_cache_v';

    private static bool $public_assets_enqueued = false;

    private FectionWP_Gallery_CPT $cpt;
    private FectionWP_Gallery_Shortcode $shortcode;
    private FectionWP_Gallery_Widget $widget;
    private FectionWP_Gallery_Admin $admin;

    public function __construct()
    {
        $this->cpt = new FectionWP_Gallery_CPT();
        $this->shortcode = new FectionWP_Gallery_Shortcode();
        $this->widget = new FectionWP_Gallery_Widget();
        $this->admin = new FectionWP_Gallery_Admin();
    }

    public function run(): void
    {
        add_action('init', [$this->cpt, 'register']);

        add_action('widgets_init', [$this->widget, 'register']);

        add_action('init', [$this->shortcode, 'register']);
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_public_assets']);

        add_action('admin_enqueue_scripts', [$this->admin, 'enqueue_admin_assets']);
        add_action('add_meta_boxes', [$this->admin, 'register_metaboxes']);
        add_action('save_post', [$this->admin, 'save_gallery_meta']);

        add_action('admin_menu', [$this->admin, 'register_admin_menu']);
        add_action('admin_init', [$this->admin, 'register_settings']);
        add_action('admin_init', [$this->admin, 'maybe_apply_preset']);

        // Cache busting for shortcode HTML.
        add_action('updated_option', [$this, 'maybe_bump_global_cache_version'], 10, 3);

        // Admin live preview.
        add_action('wp_ajax_fg_render_preview', [$this->admin, 'ajax_render_preview']);

        // Ensure meta is registered for REST/editor.
        add_action('init', [$this->admin, 'register_meta']);
    }

    public function maybe_bump_global_cache_version(string $option, $old_value, $value): void
    {
        if ($option !== 'fectionwp_gallery_style') {
            return;
        }

        self::bump_global_cache_version();
    }

    public static function get_global_cache_version(): int
    {
        $v = absint(get_option(self::OPTION_CACHE_VERSION, 1));
        return $v > 0 ? $v : 1;
    }

    public static function bump_global_cache_version(): void
    {
        $v = self::get_global_cache_version();
        update_option(self::OPTION_CACHE_VERSION, $v + 1, false);
    }

    public static function get_gallery_cache_version(int $post_id): int
    {
        $v = absint(get_post_meta($post_id, self::META_GALLERY_CACHE_VERSION, true));
        return $v > 0 ? $v : 1;
    }

    public static function bump_gallery_cache_version(int $post_id): void
    {
        $v = self::get_gallery_cache_version($post_id);
        update_post_meta($post_id, self::META_GALLERY_CACHE_VERSION, (string) ($v + 1));
    }

    public function maybe_enqueue_public_assets(): void
    {
        if (is_admin()) {
            return;
        }

        // Force load (themes can opt in when they render shortcodes dynamically).
        if ((bool) apply_filters('fectionwp_gallery_always_enqueue_assets', false)) {
            self::ensure_public_assets();
            return;
        }

        global $post;
        if ($post instanceof WP_Post && has_shortcode((string) $post->post_content, FectionWP_Gallery_Shortcode::SHORTCODE)) {
            self::ensure_public_assets();
        }
    }

    public static function ensure_public_assets(): void
    {
        if (self::$public_assets_enqueued) {
            return;
        }
        self::$public_assets_enqueued = true;

        // For WP.org submission: default to local Bootstrap files.
        // Override with filter: 'local' (default), 'cdn', or 'none'.
        $bootstrap_source = (string) apply_filters('fectionwp_gallery_bootstrap_source', 'local');

        $bootstrap_style_handle = null;
        $bootstrap_script_handle = null;

        if ($bootstrap_source === 'cdn') {
            $bootstrap_style_handle = 'bootstrap-5-3';
            $bootstrap_script_handle = 'bootstrap-5-3';

            wp_enqueue_style(
                $bootstrap_style_handle,
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
                [],
                '5.3.3'
            );

            wp_enqueue_script(
                $bootstrap_script_handle,
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
                [],
                '5.3.3',
                true
            );
        } elseif ($bootstrap_source === 'local') {
            $bootstrap_style_handle = 'bootstrap-5-3-local';
            $bootstrap_script_handle = 'bootstrap-5-3-local';

            wp_enqueue_style(
                $bootstrap_style_handle,
                FECTIONWPGALLERY_URL . 'assets/vendor/bootstrap/bootstrap.min.css',
                [],
                '5.3.3'
            );

            wp_enqueue_script(
                $bootstrap_script_handle,
                FECTIONWPGALLERY_URL . 'assets/vendor/bootstrap/bootstrap.bundle.min.js',
                [],
                '5.3.3',
                true
            );
        }

        wp_enqueue_style(
            'fectionwp-gallery',
            FECTIONWPGALLERY_URL . 'assets/css/fectionwp-gallery.css',
            $bootstrap_style_handle ? [$bootstrap_style_handle] : [],
            FECTIONWPGALLERY_VERSION
        );

        $inline_css = FectionWP_Gallery_Admin::build_global_inline_css();
        if ($inline_css !== '') {
            wp_add_inline_style('fectionwp-gallery', $inline_css);
        }

        wp_enqueue_script(
            'fectionwp-gallery',
            FECTIONWPGALLERY_URL . 'assets/js/fectionwp-gallery.js',
            $bootstrap_script_handle ? [$bootstrap_script_handle] : [],
            FECTIONWPGALLERY_VERSION,
            true
        );
    }

    public function enqueue_public_assets(): void
    {
        self::ensure_public_assets();
    }
}

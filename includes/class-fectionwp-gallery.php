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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);

        add_action('admin_enqueue_scripts', [$this->admin, 'enqueue_admin_assets']);
        add_action('add_meta_boxes', [$this->admin, 'register_metaboxes']);
        add_action('save_post', [$this->admin, 'save_gallery_meta']);

        add_action('admin_menu', [$this->admin, 'register_admin_menu']);
        add_action('admin_init', [$this->admin, 'register_settings']);
        add_action('admin_init', [$this->admin, 'maybe_apply_preset']);

        // Admin live preview.
        add_action('wp_ajax_fg_render_preview', [$this->admin, 'ajax_render_preview']);

        // Ensure meta is registered for REST/editor.
        add_action('init', [$this->admin, 'register_meta']);
    }

    public function enqueue_public_assets(): void
    {
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
}

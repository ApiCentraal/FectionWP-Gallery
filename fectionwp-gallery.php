<?php
/**
 * Plugin Name: FectionWP Gallery
 * Description: Photo/video media galleries as beautiful Bootstrap 5.3 sliders, cards and widgets.
 * Version: 1.0.6
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: FectionLabs
 * License: GPL-2.0-or-later
 * Text Domain: fectionwp-gallery
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FECTIONWPGALLERY_VERSION', '1.0.6');
define('FECTIONWPGALLERY_FILE', __FILE__);
define('FECTIONWPGALLERY_DIR', plugin_dir_path(__FILE__));
define('FECTIONWPGALLERY_URL', plugin_dir_url(__FILE__));

require_once FECTIONWPGALLERY_DIR . 'includes/class-fectionwp-gallery.php';

add_action('plugins_loaded', function (): void {
    load_plugin_textdomain('fectionwp-gallery', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

function fectionwpgallery_run(): void {
    $plugin = new FectionWP_Gallery();
    $plugin->run();
}

fectionwpgallery_run();

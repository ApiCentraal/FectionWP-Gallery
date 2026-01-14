<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Keep user-created galleries, but remove plugin settings and known meta keys.
delete_option('fectionwp_gallery_style');

// Remove meta keys that the plugin stores.
delete_post_meta_by_key('_fection_gallery_media_ids');
delete_post_meta_by_key('_fection_gallery_layout');
delete_post_meta_by_key('_fection_gallery_cards_per_slide');
delete_post_meta_by_key('_fection_gallery_header_text');
delete_post_meta_by_key('_fection_gallery_footer_button');
delete_post_meta_by_key('_fection_gallery_style');
delete_post_meta_by_key('_fection_gallery_style_radius');
delete_post_meta_by_key('_fection_gallery_style_shadow');
delete_post_meta_by_key('_fection_gallery_style_media_bg');
delete_post_meta_by_key('_fection_gallery_style_card_header_bg');
delete_post_meta_by_key('_fection_gallery_style_card_header_color');

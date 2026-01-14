<?php

if (!defined('ABSPATH')) {
    exit;
}

class FectionWP_Gallery_CPT
{
    public const POST_TYPE = 'fection_gallery';

    public function register(): void
    {
        $labels = [
            'name' => __('Galleries', 'fectionwp-gallery'),
            'singular_name' => __('Gallery', 'fectionwp-gallery'),
            'add_new' => __('Add New', 'fectionwp-gallery'),
            'add_new_item' => __('Add New Gallery', 'fectionwp-gallery'),
            'edit_item' => __('Edit Gallery', 'fectionwp-gallery'),
            'new_item' => __('New Gallery', 'fectionwp-gallery'),
            'view_item' => __('View Gallery', 'fectionwp-gallery'),
            'search_items' => __('Search Galleries', 'fectionwp-gallery'),
            'not_found' => __('No galleries found', 'fectionwp-gallery'),
            'not_found_in_trash' => __('No galleries found in Trash', 'fectionwp-gallery'),
            'menu_name' => __('Galleries', 'fectionwp-gallery'),
        ];

        register_post_type(
            self::POST_TYPE,
            [
                'labels' => $labels,
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => 'fectionwp-gallery',
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-format-gallery',
                'supports' => ['title'],
                'capability_type' => 'post',
            ]
        );
    }
}

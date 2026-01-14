<?php

if (!defined('ABSPATH')) {
    exit;
}

class FectionWP_Gallery_Widget
{
    public function register(): void
    {
        register_widget(FectionWP_Gallery_Widget_Impl::class);
    }
}

class FectionWP_Gallery_Widget_Impl extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'fectionwp_gallery_widget',
            __('Fection Gallery', 'fectionwp-gallery'),
            ['description' => __('Show a Fection gallery in your sidebar.', 'fectionwp-gallery')]
        );
    }

    public function widget($args, $instance): void
    {
        $title = isset($instance['title']) ? (string) $instance['title'] : '';
        $gallery_id = isset($instance['gallery_id']) ? absint($instance['gallery_id']) : 0;
        $layout = isset($instance['layout']) ? sanitize_key((string) $instance['layout']) : '';

        echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        if ($title !== '') {
            echo $args['before_title'] . esc_html($title) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        if ($gallery_id) {
            $shortcode = sprintf(
                '[%s id="%d"%s]',
                FectionWP_Gallery_Shortcode::SHORTCODE,
                $gallery_id,
                $layout ? ' layout="' . esc_attr($layout) . '"' : ''
            );
            echo do_shortcode($shortcode); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function form($instance): void
    {
        $title = isset($instance['title']) ? (string) $instance['title'] : '';
        $gallery_id = isset($instance['gallery_id']) ? absint($instance['gallery_id']) : 0;
        $layout = isset($instance['layout']) ? sanitize_key((string) $instance['layout']) : '';

        $galleries = get_posts([
            'post_type' => FectionWP_Gallery_CPT::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => 200,
        ]);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php echo esc_html__('Title', 'fectionwp-gallery'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('gallery_id')); ?>"><?php echo esc_html__('Gallery', 'fectionwp-gallery'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('gallery_id')); ?>" name="<?php echo esc_attr($this->get_field_name('gallery_id')); ?>">
                <option value="0"><?php echo esc_html__('Select…', 'fectionwp-gallery'); ?></option>
                <?php foreach ($galleries as $g) : ?>
                    <option value="<?php echo esc_attr((string) $g->ID); ?>" <?php selected($gallery_id, $g->ID); ?>><?php echo esc_html($g->post_title ?: ('#' . $g->ID)); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('layout')); ?>"><?php echo esc_html__('Layout override', 'fectionwp-gallery'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('layout')); ?>" name="<?php echo esc_attr($this->get_field_name('layout')); ?>">
                <option value="" <?php selected($layout, ''); ?>><?php echo esc_html__('Use gallery setting', 'fectionwp-gallery'); ?></option>
                <option value="carousel" <?php selected($layout, 'carousel'); ?>><?php echo esc_html__('Carousel', 'fectionwp-gallery'); ?></option>
                <option value="cards" <?php selected($layout, 'cards'); ?>><?php echo esc_html__('Card slider', 'fectionwp-gallery'); ?></option>
            </select>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance): array
    {
        $instance = [];
        $instance['title'] = sanitize_text_field((string) ($new_instance['title'] ?? ''));
        $instance['gallery_id'] = absint($new_instance['gallery_id'] ?? 0);
        $instance['layout'] = sanitize_key((string) ($new_instance['layout'] ?? ''));
        return $instance;
    }
}

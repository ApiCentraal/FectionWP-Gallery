<?php

if (!defined('ABSPATH')) {
    exit;
}

class FectionWP_Gallery_Admin
{
    private const OPTION_STYLE = 'fectionwp_gallery_style';

    private const META_MEDIA_IDS = '_fection_gallery_media_ids';
    private const META_LAYOUT = '_fection_gallery_layout';
    private const META_CARDS_PER_SLIDE = '_fection_gallery_cards_per_slide';
    private const META_HEADER_TEXT = '_fection_gallery_header_text';
    private const META_FOOTER_BUTTON = '_fection_gallery_footer_button';

    private const META_CARD_SHOW_IMAGE = '_fection_gallery_card_show_image';
    private const META_CARD_IMAGE_SIZE = '_fection_gallery_card_image_size';

    private const META_STYLE_JSON = '_fection_gallery_style';

    private const META_STYLE_RADIUS = '_fection_gallery_style_radius';
    private const META_STYLE_SHADOW = '_fection_gallery_style_shadow';
    private const META_STYLE_MEDIA_BG = '_fection_gallery_style_media_bg';
    private const META_STYLE_CARD_HEADER_BG = '_fection_gallery_style_card_header_bg';
    private const META_STYLE_CARD_HEADER_COLOR = '_fection_gallery_style_card_header_color';

    public function register_admin_menu(): void
    {
        add_menu_page(
            __('Fection Gallery', 'fectionwp-gallery'),
            __('Fection Gallery', 'fectionwp-gallery'),
            'manage_options',
            'fectionwp-gallery',
            [$this, 'render_dashboard_page'],
            'dashicons-format-gallery',
            26
        );

        add_submenu_page(
            'fectionwp-gallery',
            __('Preview', 'fectionwp-gallery'),
            __('Preview', 'fectionwp-gallery'),
            'edit_posts',
            'fectionwp-gallery-preview',
            [$this, 'render_previewer_page']
        );

        add_submenu_page(
            'fectionwp-gallery',
            __('Styling', 'fectionwp-gallery'),
            __('Styling', 'fectionwp-gallery'),
            'manage_options',
            'fectionwp-gallery-styling',
            [$this, 'render_styling_page']
        );
    }

    public function render_dashboard_page(): void
    {
        if (!current_user_can('edit_posts')) {
            return;
        }

        $list_url = admin_url('edit.php?post_type=' . FectionWP_Gallery_CPT::POST_TYPE);
        $new_url = admin_url('post-new.php?post_type=' . FectionWP_Gallery_CPT::POST_TYPE);
        $styling_url = admin_url('admin.php?page=fectionwp-gallery-styling');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Fection Gallery', 'fectionwp-gallery'); ?></h1>
            <p><?php echo esc_html__('Create galleries with images and videos, then embed them via shortcode or widget.', 'fectionwp-gallery'); ?></p>

            <p>
                <a class="button button-primary" href="<?php echo esc_url($new_url); ?>"><?php echo esc_html__('Add New Gallery', 'fectionwp-gallery'); ?></a>
                <a class="button" href="<?php echo esc_url($list_url); ?>"><?php echo esc_html__('Manage Galleries', 'fectionwp-gallery'); ?></a>
                <a class="button" href="<?php echo esc_url($styling_url); ?>"><?php echo esc_html__('Styling', 'fectionwp-gallery'); ?></a>
            </p>

            <h2><?php echo esc_html__('Shortcode', 'fectionwp-gallery'); ?></h2>
            <p><code>[fection_gallery id="123"]</code></p>
            <p><code>[fection_gallery id="123" layout="cards" cards_per_slide="3" header="Mijn header" footer_button="1"]</code></p>
        </div>
        <?php
    }

    public function render_previewer_page(): void
    {
        if (!current_user_can('edit_posts')) {
            return;
        }

        $selected_id = isset($_GET['gallery_id']) ? absint($_GET['gallery_id']) : 0;
        $layout = isset($_GET['layout']) ? sanitize_key((string) $_GET['layout']) : '';
        $cards_per_slide = isset($_GET['cards_per_slide']) ? max(1, min(6, absint($_GET['cards_per_slide']))) : 3;
        $autoplay = isset($_GET['autoplay']) ? (int) (bool) absint($_GET['autoplay']) : 0;

        if (!in_array($layout, ['', 'carousel', 'cards'], true)) {
            $layout = '';
        }

        $galleries = get_posts([
            'post_type' => FectionWP_Gallery_CPT::POST_TYPE,
            'post_status' => 'any',
            'numberposts' => 200,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if ($selected_id === 0 && !empty($galleries) && isset($galleries[0]->ID)) {
            $selected_id = (int) $galleries[0]->ID;
        }

        $action_url = admin_url('admin.php');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Gallery preview', 'fectionwp-gallery'); ?></h1>

            <form method="get" action="<?php echo esc_url($action_url); ?>" style="margin: 12px 0;">
                <input type="hidden" name="page" value="fectionwp-gallery-preview" />

                <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                    <p style="margin:0; min-width: 320px;">
                        <label for="fg_preview_gallery"><strong><?php echo esc_html__('Select a gallery', 'fectionwp-gallery'); ?></strong></label><br />
                        <select class="regular-text" id="fg_preview_gallery" name="gallery_id">
                            <?php if (empty($galleries)) : ?>
                                <option value="0"><?php echo esc_html__('No galleries found', 'fectionwp-gallery'); ?></option>
                            <?php else : ?>
                                <?php foreach ($galleries as $gallery_post) : ?>
                                    <option value="<?php echo esc_attr((string) $gallery_post->ID); ?>" <?php selected($selected_id, (int) $gallery_post->ID); ?>>
                                        <?php
                                        $title = get_the_title($gallery_post);
                                        echo esc_html(($title !== '' ? $title : __('(no title)', 'fectionwp-gallery')) . ' (#' . (int) $gallery_post->ID . ')');
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </p>

                    <p style="margin:0;">
                        <label for="fg_preview_layout"><strong><?php echo esc_html__('Layout', 'fectionwp-gallery'); ?></strong></label><br />
                        <select id="fg_preview_layout" name="layout">
                            <option value="" <?php selected($layout, ''); ?>><?php echo esc_html__('Use gallery setting', 'fectionwp-gallery'); ?></option>
                            <option value="carousel" <?php selected($layout, 'carousel'); ?>><?php echo esc_html__('Carousel', 'fectionwp-gallery'); ?></option>
                            <option value="cards" <?php selected($layout, 'cards'); ?>><?php echo esc_html__('Card slider', 'fectionwp-gallery'); ?></option>
                        </select>
                    </p>

                    <p style="margin:0;">
                        <label for="fg_preview_cards"><strong><?php echo esc_html__('Cards per slide', 'fectionwp-gallery'); ?></strong></label><br />
                        <input id="fg_preview_cards" name="cards_per_slide" type="number" min="1" max="6" value="<?php echo esc_attr((string) $cards_per_slide); ?>" style="width: 90px;" />
                    </p>

                    <p style="margin:0;">
                        <label>
                            <input type="checkbox" name="autoplay" value="1" <?php checked($autoplay === 1); ?> />
                            <strong><?php echo esc_html__('Autoplay', 'fectionwp-gallery'); ?></strong>
                        </label>
                    </p>

                    <p style="margin:0;">
                        <button type="submit" class="button button-primary"><?php echo esc_html__('Preview', 'fectionwp-gallery'); ?></button>
                    </p>
                </div>
            </form>

            <?php if (empty($galleries)) : ?>
                <p>
                    <?php
                    $new_url = admin_url('post-new.php?post_type=' . FectionWP_Gallery_CPT::POST_TYPE);
                    echo esc_html__('Create a gallery first, then preview it here.', 'fectionwp-gallery') . ' ';
                    echo '<a class="button" href="' . esc_url($new_url) . '">' . esc_html__('Add New Gallery', 'fectionwp-gallery') . '</a>';
                    ?>
                </p>
            <?php elseif ($selected_id > 0) : ?>
                <div style="margin-top: 16px; padding: 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 8px;">
                    <?php
                    $shortcode = '[fection_gallery id="' . (int) $selected_id . '"';
                    if ($layout !== '') {
                        $shortcode .= ' layout="' . esc_attr($layout) . '"';
                    }
                    $shortcode .= ' cards_per_slide="' . (int) $cards_per_slide . '"';
                    $shortcode .= ' autoplay="' . (int) $autoplay . '"';
                    $shortcode .= ']';
                    echo do_shortcode($shortcode); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </div>

                <p class="description" style="margin-top:10px;">
                    <?php echo esc_html__('Tip: this preview uses the same shortcode renderer as the frontend. Styling is controlled via Fection Gallery → Styling and per-gallery overrides.', 'fectionwp-gallery'); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function register_settings(): void
    {
        register_setting(
            'fectionwp_gallery_styling',
            self::OPTION_STYLE,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_style_options'],
                'default' => self::default_style_options(),
            ]
        );

        $sections = [
            'general' => __('General', 'fectionwp-gallery'),
            'media' => __('Media', 'fectionwp-gallery'),
            'carousel' => __('Carousel', 'fectionwp-gallery'),
            'cards' => __('Cards', 'fectionwp-gallery'),
            'buttons' => __('Buttons', 'fectionwp-gallery'),
            'typography' => __('Typography', 'fectionwp-gallery'),
        ];

        foreach ($sections as $section_key => $title) {
            add_settings_section(
                'fg_style_' . $section_key,
                $title,
                function () use ($section_key) {
                    if ($section_key === 'general') {
                        echo '<p>' . esc_html__('These defaults apply to all galleries. Individual galleries can override styling in the gallery editor.', 'fectionwp-gallery') . '</p>';
                    }
                },
                'fectionwp-gallery-styling'
            );
        }

        foreach (self::get_style_schema() as $key => $def) {
            $section = isset($def['section']) ? (string) $def['section'] : 'general';
            $label = isset($def['label']) ? (string) $def['label'] : $key;
            $type = isset($def['type']) ? (string) $def['type'] : 'text';
            $choices = isset($def['choices']) && is_array($def['choices']) ? $def['choices'] : [];

            add_settings_field(
                'fg_style_' . $key,
                $label,
                function () use ($key, $type, $choices) {
                    $options = self::get_style_options();
                    $name = self::OPTION_STYLE . '[' . $key . ']';
                    $value = isset($options[$key]) ? (string) $options[$key] : '';
                    $schema = self::get_style_schema();
                    $def = isset($schema[$key]) && is_array($schema[$key]) ? $schema[$key] : [];
                    $default = isset($def['default']) ? (string) $def['default'] : '';

                    if ($type === 'select') {
                        echo '<select class="regular-text" name="' . esc_attr($name) . '">';
                        foreach ($choices as $choice_value => $choice_label) {
                            echo '<option value="' . esc_attr((string) $choice_value) . '"' . selected($value, (string) $choice_value, false) . '>' . esc_html((string) $choice_label) . '</option>';
                        }
                        echo '</select>';
                        return;
                    }

                    if ($type === 'color') {
                        printf(
                            '<input class="regular-text fg-color-field" type="text" name="%s" value="%s" data-default-color="%s">',
                            esc_attr($name),
                            esc_attr($value),
                            esc_attr($default)
                        );
                        return;
                    }

                    if ($type === 'number') {
                        printf(
                            '<input class="regular-text" type="number" step="0.01" name="%s" value="%s">',
                            esc_attr($name),
                            esc_attr($value)
                        );
                        return;
                    }

                    printf(
                        '<input class="regular-text" type="text" name="%s" value="%s">',
                        esc_attr($name),
                        esc_attr($value)
                    );
                },
                'fectionwp-gallery-styling',
                'fg_style_' . $section
            );
        }
    }

    public function render_styling_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $applied = isset($_GET['fg_preset_applied']) ? sanitize_key((string) $_GET['fg_preset_applied']) : '';
        if ($applied !== '') {
            $label = $applied === 'white' ? __('White', 'fectionwp-gallery') : ($applied === 'black' ? __('Black', 'fectionwp-gallery') : $applied);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sprintf(__('Preset applied: %s', 'fectionwp-gallery'), $label)) . '</p></div>';
        }

        $white_url = wp_nonce_url(
            admin_url('admin.php?page=fectionwp-gallery-styling&fg_preset=white'),
            'fg_apply_preset',
            'fg_nonce'
        );
        $black_url = wp_nonce_url(
            admin_url('admin.php?page=fectionwp-gallery-styling&fg_preset=black'),
            'fg_apply_preset',
            'fg_nonce'
        );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Fection Gallery Styling', 'fectionwp-gallery'); ?></h1>

            <p style="max-width: 900px;">
                <label for="fg-style-filter"><strong><?php echo esc_html__('Filter settings', 'fectionwp-gallery'); ?></strong></label><br />
                <input id="fg-style-filter" class="regular-text" type="text" placeholder="<?php echo esc_attr__('Type to filter (e.g. button, caption, radius)…', 'fectionwp-gallery'); ?>" />
            </p>

            <div style="margin: 12px 0; padding: 12px; background: #fff; border: 1px solid #c3c4c7; border-radius: 8px;">
                <strong style="display:block; margin-bottom:8px;"><?php echo esc_html__('Presets', 'fectionwp-gallery'); ?></strong>
                <a class="button" href="<?php echo esc_url($white_url); ?>"><?php echo esc_html__('Apply White', 'fectionwp-gallery'); ?></a>
                <a class="button" href="<?php echo esc_url($black_url); ?>"><?php echo esc_html__('Apply Black', 'fectionwp-gallery'); ?></a>
                <span class="description" style="margin-left:10px;"><?php echo esc_html__('Applies to global styling (all galleries).', 'fectionwp-gallery'); ?></span>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields('fectionwp_gallery_styling');
                do_settings_sections('fectionwp-gallery-styling');
                submit_button();
                ?>
            </form>

            <p class="description">
                <?php echo esc_html__('Tip: you can paste CSS values (e.g. 1rem, rgba(...), 16/9). Most fields are CSS variables mapped onto Bootstrap 5.3 components.', 'fectionwp-gallery'); ?>
            </p>
        </div>
        <?php
    }

    public function maybe_apply_preset(): void
    {
        if (!is_admin()) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!isset($_GET['page']) || (string) $_GET['page'] !== 'fectionwp-gallery-styling') {
            return;
        }
        if (!isset($_GET['fg_preset'])) {
            return;
        }

        $preset = sanitize_key((string) $_GET['fg_preset']);
        if (!in_array($preset, ['white', 'black'], true)) {
            return;
        }
        if (!isset($_GET['fg_nonce']) || !wp_verify_nonce((string) $_GET['fg_nonce'], 'fg_apply_preset')) {
            return;
        }

        $values = $this->get_preset_values($preset);
        $clean = $this->sanitize_style_values($values, false);
        update_option(self::OPTION_STYLE, $clean);

        $url = add_query_arg(
            [
                'page' => 'fectionwp-gallery-styling',
                'fg_preset_applied' => $preset,
            ],
            admin_url('admin.php')
        );
        wp_safe_redirect($url);
        exit;
    }

    private function get_preset_values(string $preset): array
    {
        $defaults = self::default_style_options();
        $o = $defaults;

        if ($preset === 'white') {
            $o['media_bg'] = '#f8fafc';
            $o['caption_bg'] = 'rgba(255,255,255,0.75)';
            $o['caption_color'] = '#0f172a';
            $o['ind_bg'] = 'rgba(15,23,42,0.35)';
            $o['ind_active_bg'] = '#0f172a';
            $o['ctrl_bg'] = 'rgba(255,255,255,0.45)';

            $o['card_bg'] = '#ffffff';
            $o['card_border_color'] = 'rgba(15, 23, 42, 0.10)';
            $o['card_body_color'] = '#0f172a';
            $o['card_header_bg'] = '#ffffff';
            $o['card_header_color'] = '#0f172a';

            $o['btn_bg'] = '#0f172a';
            $o['btn_color'] = '#ffffff';
            $o['btn_border_color'] = '#0f172a';
            $o['btn_hover_bg'] = '#111827';
            $o['btn_hover_color'] = '#ffffff';

            $o['shadow'] = '0 16px 40px rgba(15, 23, 42, 0.10)';
            return $o;
        }

        // black
        $o['media_bg'] = '#020617';
        $o['caption_bg'] = 'rgba(2, 6, 23, 0.65)';
        $o['caption_color'] = '#ffffff';
        $o['ind_bg'] = 'rgba(255,255,255,0.35)';
        $o['ind_active_bg'] = '#ffffff';
        $o['ctrl_bg'] = 'rgba(2, 6, 23, 0.35)';

        $o['card_bg'] = '#0b1220';
        $o['card_border_color'] = 'rgba(148, 163, 184, 0.18)';
        $o['card_body_color'] = '#e2e8f0';
        $o['card_header_bg'] = 'rgba(2, 6, 23, 0.85)';
        $o['card_header_color'] = '#ffffff';

        $o['btn_bg'] = '#2563eb';
        $o['btn_color'] = '#ffffff';
        $o['btn_border_color'] = '#2563eb';
        $o['btn_hover_bg'] = '#1d4ed8';
        $o['btn_hover_color'] = '#ffffff';

        $o['shadow'] = '0 18px 50px rgba(0, 0, 0, 0.45)';
        return $o;
    }

    public function sanitize_style_options($input): array
    {
        $input = is_array($input) ? $input : [];
        return $this->sanitize_style_values($input, false);
    }

    private function sanitize_style_values(array $input, bool $allow_empty): array
    {
        $defaults = self::default_style_options();
        $out = [];

        foreach (self::get_style_schema() as $key => $def) {
            $type = isset($def['type']) ? (string) $def['type'] : 'text';
            $fallback = isset($defaults[$key]) ? (string) $defaults[$key] : '';
            $value = isset($input[$key]) ? trim((string) $input[$key]) : '';

            if ($allow_empty && $value === '') {
                $out[$key] = '';
                continue;
            }

            if ($value === '') {
                $out[$key] = $fallback;
                continue;
            }

            if ($type === 'length') {
                $out[$key] = $this->sanitize_css_length($value, $fallback);
                continue;
            }

            if ($type === 'shadow') {
                $out[$key] = $this->sanitize_css_shadow($value, $fallback);
                continue;
            }

            if ($type === 'color') {
                $out[$key] = $this->sanitize_css_color($value, $fallback);
                continue;
            }

            if ($type === 'number') {
                $value = (string) sanitize_text_field($value);
                if (!is_numeric($value)) {
                    $out[$key] = $fallback;
                } else {
                    $n = (float) $value;
                    // Clamp for opacity-like settings.
                    if (isset($def['min']) && is_numeric($def['min'])) {
                        $n = max((float) $def['min'], $n);
                    }
                    if (isset($def['max']) && is_numeric($def['max'])) {
                        $n = min((float) $def['max'], $n);
                    }
                    $out[$key] = rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
                }
                continue;
            }

            if ($type === 'select') {
                $choices = isset($def['choices']) && is_array($def['choices']) ? array_keys($def['choices']) : [];
                $value = sanitize_key($value);
                $out[$key] = in_array($value, $choices, true) ? $value : $fallback;
                continue;
            }

            if ($type === 'ratio') {
                $value = preg_replace('/\s+/', '', $value);
                if (preg_match('/^\d+(?:\.\d+)?\/\d+(?:\.\d+)?$/', $value)) {
                    $out[$key] = $value;
                } elseif (is_numeric($value)) {
                    $out[$key] = (string) $value;
                } else {
                    $out[$key] = $fallback;
                }
                continue;
            }

            $value = sanitize_text_field($value);
            $out[$key] = strlen($value) > 200 ? $fallback : $value;
        }

        return $out;
    }

    private function sanitize_css_length(string $value, string $fallback): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }
        if (preg_match('/^(?:0|\d+(?:\.\d+)?)(?:px|rem|em|%|vh|vw)?$/', $value)) {
            return $value;
        }
        return $fallback;
    }

    private function sanitize_css_shadow(string $value, string $fallback): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }
        $value = sanitize_text_field($value);
        return strlen($value) > 200 ? $fallback : $value;
    }

    private function sanitize_css_color(string $value, string $fallback): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        $hex = sanitize_hex_color($value);
        if (is_string($hex) && $hex !== '') {
            return $hex;
        }

        if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/', $value)) {
            return $value;
        }

        return $fallback;
    }

    public static function get_style_schema(): array
    {
        return [
            // General
            'radius' => ['label' => __('Border radius', 'fectionwp-gallery'), 'default' => '1rem', 'type' => 'length', 'var' => '--fg-radius', 'section' => 'general'],
            'shadow' => ['label' => __('Shadow', 'fectionwp-gallery'), 'default' => '0 16px 40px rgba(15, 23, 42, 0.12)', 'type' => 'shadow', 'var' => '--fg-shadow', 'section' => 'general'],
            'gap' => ['label' => __('Gap (cards/grid)', 'fectionwp-gallery'), 'default' => '1rem', 'type' => 'length', 'var' => '--fg-gap', 'section' => 'general'],
            'header_mb' => ['label' => __('Header margin bottom', 'fectionwp-gallery'), 'default' => '0.75rem', 'type' => 'length', 'var' => '--fg-header-mb', 'section' => 'general'],

            // Media
            'media_bg' => ['label' => __('Media background', 'fectionwp-gallery'), 'default' => '#0b1220', 'type' => 'color', 'var' => '--fg-media-bg', 'section' => 'media'],
            'media_aspect' => ['label' => __('Media aspect ratio', 'fectionwp-gallery'), 'default' => '16/9', 'type' => 'ratio', 'var' => '--fg-media-aspect', 'section' => 'media'],
            'media_fit' => ['label' => __('Media fit', 'fectionwp-gallery'), 'default' => 'cover', 'type' => 'select', 'choices' => ['cover' => __('Cover', 'fectionwp-gallery'), 'contain' => __('Contain', 'fectionwp-gallery')], 'var' => '--fg-media-fit', 'section' => 'media'],
            'media_max_h' => ['label' => __('Media max height', 'fectionwp-gallery'), 'default' => '70vh', 'type' => 'length', 'var' => '--fg-media-max-h', 'section' => 'media'],

            // Carousel
            'ind_bg' => ['label' => __('Indicators color', 'fectionwp-gallery'), 'default' => 'rgba(255,255,255,0.5)', 'type' => 'color', 'var' => '--fg-ind-bg', 'section' => 'carousel'],
            'ind_active_bg' => ['label' => __('Indicators active color', 'fectionwp-gallery'), 'default' => '#ffffff', 'type' => 'color', 'var' => '--fg-ind-active-bg', 'section' => 'carousel'],
            'ind_w' => ['label' => __('Indicators width', 'fectionwp-gallery'), 'default' => '30px', 'type' => 'length', 'var' => '--fg-ind-w', 'section' => 'carousel'],
            'ind_h' => ['label' => __('Indicators height', 'fectionwp-gallery'), 'default' => '3px', 'type' => 'length', 'var' => '--fg-ind-h', 'section' => 'carousel'],
            'ind_gap' => ['label' => __('Indicators gap', 'fectionwp-gallery'), 'default' => '3px', 'type' => 'length', 'var' => '--fg-ind-gap', 'section' => 'carousel'],
            'ind_opacity' => ['label' => __('Indicators opacity', 'fectionwp-gallery'), 'default' => '0.5', 'type' => 'number', 'min' => 0, 'max' => 1, 'var' => '--fg-ind-opacity', 'section' => 'carousel'],
            'ind_active_opacity' => ['label' => __('Indicators active opacity', 'fectionwp-gallery'), 'default' => '1', 'type' => 'number', 'min' => 0, 'max' => 1, 'var' => '--fg-ind-active-opacity', 'section' => 'carousel'],
            'ctrl_icon_w' => ['label' => __('Controls icon width', 'fectionwp-gallery'), 'default' => '2rem', 'type' => 'length', 'var' => '--fg-ctrl-icon-w', 'section' => 'carousel'],
            'ctrl_opacity' => ['label' => __('Controls opacity', 'fectionwp-gallery'), 'default' => '0.85', 'type' => 'number', 'min' => 0, 'max' => 1, 'var' => '--fg-ctrl-opacity', 'section' => 'carousel'],
            'ctrl_hover_opacity' => ['label' => __('Controls hover opacity', 'fectionwp-gallery'), 'default' => '1', 'type' => 'number', 'min' => 0, 'max' => 1, 'var' => '--fg-ctrl-hover-opacity', 'section' => 'carousel'],
            'ctrl_bg' => ['label' => __('Controls background', 'fectionwp-gallery'), 'default' => 'rgba(2, 6, 23, 0.35)', 'type' => 'color', 'var' => '--fg-ctrl-bg', 'section' => 'carousel'],
            'ctrl_radius' => ['label' => __('Controls background radius', 'fectionwp-gallery'), 'default' => '999px', 'type' => 'length', 'var' => '--fg-ctrl-radius', 'section' => 'carousel'],

            'caption_bg' => ['label' => __('Caption background', 'fectionwp-gallery'), 'default' => 'rgba(2, 6, 23, 0.6)', 'type' => 'color', 'var' => '--fg-caption-bg', 'section' => 'carousel'],
            'caption_color' => ['label' => __('Caption text color', 'fectionwp-gallery'), 'default' => '#ffffff', 'type' => 'color', 'var' => '--fg-caption-color', 'section' => 'carousel'],
            'caption_p' => ['label' => __('Caption padding', 'fectionwp-gallery'), 'default' => '0.75rem 1rem', 'type' => 'text', 'var' => '--fg-caption-p', 'section' => 'carousel'],
            'caption_radius' => ['label' => __('Caption radius', 'fectionwp-gallery'), 'default' => '0.75rem', 'type' => 'length', 'var' => '--fg-caption-radius', 'section' => 'carousel'],

            // Cards
            'card_bg' => ['label' => __('Card background', 'fectionwp-gallery'), 'default' => '#ffffff', 'type' => 'color', 'var' => '--fg-card-bg', 'section' => 'cards'],
            'card_border_color' => ['label' => __('Card border color', 'fectionwp-gallery'), 'default' => 'rgba(15, 23, 42, 0.08)', 'type' => 'color', 'var' => '--fg-card-border-color', 'section' => 'cards'],
            'card_border_w' => ['label' => __('Card border width', 'fectionwp-gallery'), 'default' => '1px', 'type' => 'length', 'var' => '--fg-card-border-w', 'section' => 'cards'],
            'card_header_bg' => ['label' => __('Card header background', 'fectionwp-gallery'), 'default' => 'rgba(2, 6, 23, 0.85)', 'type' => 'color', 'var' => '--fg-card-header-bg', 'section' => 'cards'],
            'card_header_color' => ['label' => __('Card header text color', 'fectionwp-gallery'), 'default' => '#ffffff', 'type' => 'color', 'var' => '--fg-card-header-color', 'section' => 'cards'],
            'card_body_color' => ['label' => __('Card text color', 'fectionwp-gallery'), 'default' => '#0f172a', 'type' => 'color', 'var' => '--fg-card-body-color', 'section' => 'cards'],
            'card_body_p' => ['label' => __('Card body padding', 'fectionwp-gallery'), 'default' => '1rem', 'type' => 'length', 'var' => '--fg-card-body-p', 'section' => 'cards'],

            'card_media_aspect' => ['label' => __('Card media aspect ratio', 'fectionwp-gallery'), 'default' => '4/3', 'type' => 'ratio', 'var' => '--fg-card-media-aspect', 'section' => 'cards'],
            'card_media_fit' => ['label' => __('Card media fit', 'fectionwp-gallery'), 'default' => 'cover', 'type' => 'select', 'choices' => ['cover' => __('Cover', 'fectionwp-gallery'), 'contain' => __('Contain', 'fectionwp-gallery')], 'var' => '--fg-card-media-fit', 'section' => 'cards'],
            'card_media_max_h' => ['label' => __('Card media max height', 'fectionwp-gallery'), 'default' => '320px', 'type' => 'length', 'var' => '--fg-card-media-max-h', 'section' => 'cards'],

            // Buttons
            'btn_bg' => ['label' => __('Button background', 'fectionwp-gallery'), 'default' => '#2563eb', 'type' => 'color', 'var' => '--fg-btn-bg', 'section' => 'buttons'],
            'btn_color' => ['label' => __('Button text color', 'fectionwp-gallery'), 'default' => '#ffffff', 'type' => 'color', 'var' => '--fg-btn-color', 'section' => 'buttons'],
            'btn_border_color' => ['label' => __('Button border color', 'fectionwp-gallery'), 'default' => '#2563eb', 'type' => 'color', 'var' => '--fg-btn-border-color', 'section' => 'buttons'],
            'btn_radius' => ['label' => __('Button radius', 'fectionwp-gallery'), 'default' => '0.5rem', 'type' => 'length', 'var' => '--fg-btn-radius', 'section' => 'buttons'],
            'btn_p' => ['label' => __('Button padding', 'fectionwp-gallery'), 'default' => '0.5rem 0.75rem', 'type' => 'text', 'var' => '--fg-btn-p', 'section' => 'buttons'],
            'btn_hover_bg' => ['label' => __('Button hover background', 'fectionwp-gallery'), 'default' => '#1d4ed8', 'type' => 'color', 'var' => '--fg-btn-hover-bg', 'section' => 'buttons'],
            'btn_hover_color' => ['label' => __('Button hover text color', 'fectionwp-gallery'), 'default' => '#ffffff', 'type' => 'color', 'var' => '--fg-btn-hover-color', 'section' => 'buttons'],

            // Typography
            'font_family' => ['label' => __('Font family', 'fectionwp-gallery'), 'default' => 'inherit', 'type' => 'text', 'var' => '--fg-font-family', 'section' => 'typography'],
            'title_size' => ['label' => __('Title font size', 'fectionwp-gallery'), 'default' => '1.125rem', 'type' => 'length', 'var' => '--fg-title-size', 'section' => 'typography'],
            'text_size' => ['label' => __('Text font size', 'fectionwp-gallery'), 'default' => '1rem', 'type' => 'length', 'var' => '--fg-text-size', 'section' => 'typography'],
        ];
    }

    public static function default_style_options(): array
    {
        $out = [];
        foreach (self::get_style_schema() as $key => $def) {
            $out[$key] = isset($def['default']) ? (string) $def['default'] : '';
        }
        return $out;
    }

    public static function get_style_options(): array
    {
        $defaults = self::default_style_options();
        $opt = get_option(self::OPTION_STYLE, []);
        $opt = is_array($opt) ? $opt : [];
        return wp_parse_args($opt, $defaults);
    }

    public static function build_global_inline_css(): string
    {
        $o = self::get_style_options();

        $vars = [];
        foreach (self::get_style_schema() as $key => $def) {
            $var = isset($def['var']) ? (string) $def['var'] : '';
            if ($var === '') {
                continue;
            }
            $value = isset($o[$key]) ? (string) $o[$key] : '';
            if ($value === '') {
                continue;
            }
            $vars[] = $var . ':' . $value . ';';
        }

        if (!$vars) {
            return '';
        }

        return ".fectionwp-gallery{" . implode('', array_map('esc_html', $vars)) . "}";
    }

    public function register_meta(): void
    {
        $meta_args = [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ];

        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_MEDIA_IDS, $meta_args);
        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_LAYOUT, $meta_args);
        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_CARDS_PER_SLIDE, $meta_args);
        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_HEADER_TEXT, $meta_args);
        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_FOOTER_BUTTON, $meta_args);

        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_CARD_SHOW_IMAGE, $meta_args);
        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_CARD_IMAGE_SIZE, $meta_args);

        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_STYLE_JSON, $meta_args);

        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_STYLE_RADIUS, $meta_args);
        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_STYLE_SHADOW, $meta_args);
        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_STYLE_MEDIA_BG, $meta_args);
        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_STYLE_CARD_HEADER_BG, $meta_args);
        register_post_meta(FectionWP_Gallery_CPT::POST_TYPE, self::META_STYLE_CARD_HEADER_COLOR, $meta_args);
    }

    public function enqueue_admin_assets(string $hook): void
    {
        global $post;
        $is_gallery_post = $post && $post->post_type === FectionWP_Gallery_CPT::POST_TYPE;
        $is_plugin_page = strpos((string) $hook, 'fectionwp-gallery') !== false;
        $is_preview_page = strpos((string) $hook, 'fectionwp-gallery-preview') !== false;

        if (!$is_gallery_post && !$is_plugin_page) {
            return;
        }

        if ($is_gallery_post) {
            wp_enqueue_media();

            // Ensure the live preview renders like the frontend.
            wp_enqueue_style(
                'bootstrap-5-3-local',
                FECTIONWPGALLERY_URL . 'assets/vendor/bootstrap/bootstrap.min.css',
                [],
                '5.3.3'
            );

            wp_enqueue_script(
                'bootstrap-5-3-local',
                FECTIONWPGALLERY_URL . 'assets/vendor/bootstrap/bootstrap.bundle.min.js',
                [],
                '5.3.3',
                true
            );

            wp_enqueue_style(
                'fectionwp-gallery',
                FECTIONWPGALLERY_URL . 'assets/css/fectionwp-gallery.css',
                ['bootstrap-5-3-local'],
                FECTIONWPGALLERY_VERSION
            );

            $inline_css = self::build_global_inline_css();
            if ($inline_css !== '') {
                wp_add_inline_style('fectionwp-gallery', $inline_css);
            }

            wp_enqueue_script(
                'fectionwp-gallery',
                FECTIONWPGALLERY_URL . 'assets/js/fectionwp-gallery.js',
                ['bootstrap-5-3-local'],
                FECTIONWPGALLERY_VERSION,
                true
            );
        }

        wp_enqueue_style(
            'fectionwp-gallery-admin',
            FECTIONWPGALLERY_URL . 'assets/css/fectionwp-gallery-admin.css',
            [],
            FECTIONWPGALLERY_VERSION
        );

        if ($is_gallery_post || $is_plugin_page) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script(
                'fectionwp-gallery-admin-styling',
                FECTIONWPGALLERY_URL . 'assets/js/admin-styling.js',
                ['jquery', 'wp-color-picker'],
                FECTIONWPGALLERY_VERSION,
                true
            );
        }

        if ($is_preview_page) {
            // Ensure the preview renders like the frontend (Bootstrap + plugin CSS/JS).
            wp_enqueue_style(
                'bootstrap-5-3-local',
                FECTIONWPGALLERY_URL . 'assets/vendor/bootstrap/bootstrap.min.css',
                [],
                '5.3.3'
            );

            wp_enqueue_script(
                'bootstrap-5-3-local',
                FECTIONWPGALLERY_URL . 'assets/vendor/bootstrap/bootstrap.bundle.min.js',
                [],
                '5.3.3',
                true
            );

            wp_enqueue_style(
                'fectionwp-gallery',
                FECTIONWPGALLERY_URL . 'assets/css/fectionwp-gallery.css',
                ['bootstrap-5-3-local'],
                FECTIONWPGALLERY_VERSION
            );

            $inline_css = self::build_global_inline_css();
            if ($inline_css !== '') {
                wp_add_inline_style('fectionwp-gallery', $inline_css);
            }

            wp_enqueue_script(
                'fectionwp-gallery',
                FECTIONWPGALLERY_URL . 'assets/js/fectionwp-gallery.js',
                ['bootstrap-5-3-local'],
                FECTIONWPGALLERY_VERSION,
                true
            );
        }

        if ($is_gallery_post) {
            wp_enqueue_script(
                'fectionwp-gallery-admin',
                FECTIONWPGALLERY_URL . 'assets/js/admin-media.js',
                ['jquery', 'jquery-ui-sortable'],
                FECTIONWPGALLERY_VERSION,
                true
            );

            wp_enqueue_script(
                'fectionwp-gallery-admin-live-preview',
                FECTIONWPGALLERY_URL . 'assets/js/admin-live-preview.js',
                ['jquery', 'fectionwp-gallery-admin'],
                FECTIONWPGALLERY_VERSION,
                true
            );

            wp_localize_script('fectionwp-gallery-admin', 'FectionGalleryAdmin', [
                'frameTitle' => __('Select media', 'fectionwp-gallery'),
                'frameButton' => __('Use selected', 'fectionwp-gallery'),
                'remove' => __('Remove', 'fectionwp-gallery'),
                'openInLibrary' => __('Open in Media Library', 'fectionwp-gallery'),
            ]);

            wp_localize_script('fectionwp-gallery-admin-live-preview', 'FectionGalleryLivePreview', [
                'nonce' => wp_create_nonce('fg_live_preview'),
                'loading' => __('Loading preview…', 'fectionwp-gallery'),
                'error' => __('Could not render preview.', 'fectionwp-gallery'),
                'noMedia' => __('Select media to see a preview.', 'fectionwp-gallery'),
            ]);
        }
    }

    public function register_metaboxes(): void
    {
        add_meta_box(
            'fection_gallery_builder',
            __('Gallery Builder', 'fectionwp-gallery'),
            [$this, 'render_builder_metabox'],
            FectionWP_Gallery_CPT::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'fection_gallery_settings',
            __('Gallery Settings', 'fectionwp-gallery'),
            [$this, 'render_settings_metabox'],
            FectionWP_Gallery_CPT::POST_TYPE,
            'side',
            'default'
        );
    }

    public function render_builder_metabox(WP_Post $post): void
    {
        wp_nonce_field('fection_gallery_save', 'fection_gallery_nonce');

        $meta = self::get_gallery_meta($post->ID);
        $ids_csv = implode(',', $meta['media_ids']);
        $media_library_url = admin_url('upload.php?mode=list&post_mime_type=image');
        ?>
        <div class="fection-gallery-admin">
            <div class="fg-builder-grid">
                <div class="fg-builder-left">
                    <p>
                        <button type="button" class="button button-primary" id="fg-pick-media"><?php echo esc_html__('Choose media', 'fectionwp-gallery'); ?></button>
                        <button type="button" class="button" id="fg-clear-media"><?php echo esc_html__('Clear', 'fectionwp-gallery'); ?></button>
                        <a class="button" href="<?php echo esc_url($media_library_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Open Media Library', 'fectionwp-gallery'); ?></a>
                    </p>

                    <input type="hidden" id="fg-media-ids" name="fg_media_ids" value="<?php echo esc_attr($ids_csv); ?>" />

                    <div id="fg-media-preview" class="fg-media-preview">
                        <?php foreach ($meta['media_ids'] as $aid) :
                            $thumb = wp_get_attachment_image($aid, 'thumbnail');
                            $edit_link = get_edit_post_link($aid, '');
                            if ($thumb) {
                                echo '<div class="fg-thumb" data-id="' . esc_attr((string) $aid) . '">' . $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                echo '<div class="fg-thumb-actions">';
                                if ($edit_link) {
                                    echo '<a href="' . esc_url($edit_link) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open in Media Library', 'fectionwp-gallery') . '</a>';
                                }
                                echo '<button type="button" class="button-link fg-remove-media" data-id="' . esc_attr((string) $aid) . '">' . esc_html__('Remove', 'fectionwp-gallery') . '</button>';
                                echo '</div>';
                                echo '</div>';
                            }
                        endforeach; ?>
                    </div>

                    <p class="description">
                        <?php echo esc_html__('Tip: select images and videos from the Media Library. Order is preserved.', 'fectionwp-gallery'); ?>
                        <br />
                        <?php echo esc_html__('Tip: drag thumbnails to reorder. Click “Remove” to exclude an item (save the gallery to apply).', 'fectionwp-gallery'); ?>
                    </p>

                    <p>
                        <?php echo esc_html__('Shortcode:', 'fectionwp-gallery'); ?>
                        <code>[fection_gallery id="<?php echo esc_html((string) $post->ID); ?>"]</code>
                    </p>
                </div>

                <div class="fg-builder-right">
                    <div class="fg-live-preview-panel" id="fg-live-preview" data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
                        <div class="fg-live-preview-toolbar">
                            <strong><?php echo esc_html__('Live preview', 'fectionwp-gallery'); ?></strong>
                            <button type="button" class="button" id="fg-refresh-preview"><?php echo esc_html__('Refresh', 'fectionwp-gallery'); ?></button>
                            <label style="margin-left:auto;">
                                <input type="checkbox" id="fg_preview_autoplay" value="1" checked />
                                <?php echo esc_html__('Autoplay', 'fectionwp-gallery'); ?>
                            </label>
                            <label>
                                <?php echo esc_html__('Interval (ms)', 'fectionwp-gallery'); ?>
                                <input type="number" id="fg_preview_interval" value="5000" min="0" step="100" style="width: 100px;" />
                            </label>
                        </div>

                        <div class="fg-live-preview-status" id="fg-live-preview-status"></div>
                        <div class="fg-live-preview-output" id="fg-live-preview-output"></div>
                        <p class="description" style="margin-top:10px;">
                            <?php echo esc_html__('This preview updates automatically when you change media, settings, or styling overrides.', 'fectionwp-gallery'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings_metabox(WP_Post $post): void
    {
        $meta = self::get_gallery_meta($post->ID);
        $styling_url = admin_url('admin.php?page=fectionwp-gallery-styling');
        $sizes = get_intermediate_image_sizes();
        $sizes[] = 'full';
        ?>
        <p>
            <label for="fg_layout"><strong><?php echo esc_html__('Layout', 'fectionwp-gallery'); ?></strong></label>
            <select class="widefat" id="fg_layout" name="fg_layout">
                <option value="carousel" <?php selected($meta['layout'], 'carousel'); ?>><?php echo esc_html__('Carousel', 'fectionwp-gallery'); ?></option>
                <option value="cards" <?php selected($meta['layout'], 'cards'); ?>><?php echo esc_html__('Card slider', 'fectionwp-gallery'); ?></option>
            </select>
        </p>
        <p>
            <label for="fg_cards_per_slide"><strong><?php echo esc_html__('Cards per slide', 'fectionwp-gallery'); ?></strong></label>
            <input class="widefat" type="number" min="1" max="6" id="fg_cards_per_slide" name="fg_cards_per_slide" value="<?php echo esc_attr((string) $meta['cards_per_slide']); ?>" />
        </p>
        <p>
            <label for="fg_header_text"><strong><?php echo esc_html__('Header text (optional)', 'fectionwp-gallery'); ?></strong></label>
            <input class="widefat" type="text" id="fg_header_text" name="fg_header_text" value="<?php echo esc_attr($meta['header_text']); ?>" />
        </p>
        <p>
            <label>
                <input type="checkbox" name="fg_footer_button" value="1" <?php checked($meta['footer_button']); ?> />
                <?php echo esc_html__('Show “Open” button in card footer', 'fectionwp-gallery'); ?>
            </label>
        </p>

        <hr />

        <p>
            <label>
                <input type="checkbox" id="fg_card_show_image" name="fg_card_show_image" value="1" <?php checked($meta['card_show_image']); ?> />
                <?php echo esc_html__('Show media in cards', 'fectionwp-gallery'); ?>
            </label>
        </p>

        <p>
            <label for="fg_card_image_size"><strong><?php echo esc_html__('Card image size', 'fectionwp-gallery'); ?></strong></label>
            <select class="widefat" id="fg_card_image_size" name="fg_card_image_size">
                <?php foreach ($sizes as $size) : ?>
                    <option value="<?php echo esc_attr((string) $size); ?>" <?php selected($meta['card_image_size'], (string) $size); ?>><?php echo esc_html((string) $size); ?></option>
                <?php endforeach; ?>
            </select>
            <span class="description"><?php echo esc_html__('Only applies to the card slider layout. Use Styling to control aspect ratio / height.', 'fectionwp-gallery'); ?></span>
        </p>

        <p class="description">
            <?php echo esc_html__('Global defaults:', 'fectionwp-gallery'); ?>
            <a href="<?php echo esc_url($styling_url); ?>"><?php echo esc_html__('Fection Gallery → Styling', 'fectionwp-gallery'); ?></a>
        </p>

        <details class="fg-style-details" style="margin-top:10px;">
            <summary><strong><?php echo esc_html__('Advanced styling overrides (this gallery)', 'fectionwp-gallery'); ?></strong></summary>
            <p class="description"><?php echo esc_html__('Leave fields empty to inherit the global styling.', 'fectionwp-gallery'); ?></p>

            <div class="fg-style-grid">
                <?php
                foreach (self::get_style_schema() as $key => $def) {
                    $label = isset($def['label']) ? (string) $def['label'] : $key;
                    $type = isset($def['type']) ? (string) $def['type'] : 'text';
                    $choices = isset($def['choices']) && is_array($def['choices']) ? $def['choices'] : [];
                    $value = isset($meta['style'][$key]) ? (string) $meta['style'][$key] : '';
                    $default = isset($def['default']) ? (string) $def['default'] : '';
                    $field_id = 'fg_style_' . $key;
                    $name = 'fg_style[' . $key . ']';
                    ?>
                    <p>
                        <label for="<?php echo esc_attr($field_id); ?>"><strong><?php echo esc_html($label); ?></strong></label>
                        <?php if ($type === 'select') : ?>
                            <select class="widefat" id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($name); ?>">
                                <option value=""><?php echo esc_html__('Inherit', 'fectionwp-gallery'); ?></option>
                                <?php foreach ($choices as $choice_value => $choice_label) : ?>
                                    <option value="<?php echo esc_attr((string) $choice_value); ?>" <?php selected($value, (string) $choice_value); ?>><?php echo esc_html((string) $choice_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <input class="widefat<?php echo $type === 'color' ? ' fg-color-field' : ''; ?>" type="text" id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr($default); ?>"<?php echo $type === 'color' ? ' data-default-color="' . esc_attr($default) . '"' : ''; ?> />
                        <?php endif; ?>
                    </p>
                    <?php
                }
                ?>
            </div>
        </details>
        <?php
    }

    public function save_gallery_meta(int $post_id): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['fection_gallery_nonce']) || !wp_verify_nonce((string) $_POST['fection_gallery_nonce'], 'fection_gallery_save')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (get_post_type($post_id) !== FectionWP_Gallery_CPT::POST_TYPE) {
            return;
        }

        $media_ids_raw = isset($_POST['fg_media_ids']) ? (string) wp_unslash($_POST['fg_media_ids']) : '';
        $media_ids = array_values(array_filter(array_map('absint', preg_split('/\s*,\s*/', $media_ids_raw) ?: [])));

        $layout = isset($_POST['fg_layout']) ? sanitize_key((string) wp_unslash($_POST['fg_layout'])) : 'carousel';
        if (!in_array($layout, ['carousel', 'cards'], true)) {
            $layout = 'carousel';
        }

        $cards_per_slide = isset($_POST['fg_cards_per_slide']) ? absint($_POST['fg_cards_per_slide']) : 3;
        $cards_per_slide = max(1, min(6, $cards_per_slide));

        $header_text = isset($_POST['fg_header_text']) ? sanitize_text_field((string) wp_unslash($_POST['fg_header_text'])) : '';
        $footer_button = isset($_POST['fg_footer_button']) ? 1 : 0;

        $card_show_image = isset($_POST['fg_card_show_image']) ? 1 : 0;
        $card_image_size = isset($_POST['fg_card_image_size']) ? sanitize_key((string) wp_unslash($_POST['fg_card_image_size'])) : 'large';
        $valid_sizes = get_intermediate_image_sizes();
        $valid_sizes[] = 'full';
        if (!in_array($card_image_size, $valid_sizes, true)) {
            $card_image_size = 'large';
        }

        $style_input = [];
        if (isset($_POST['fg_style']) && is_array($_POST['fg_style'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $style_input = wp_unslash($_POST['fg_style']);
        }

        $style_clean = $this->sanitize_style_values(is_array($style_input) ? $style_input : [], true);

        update_post_meta($post_id, self::META_MEDIA_IDS, implode(',', $media_ids));
        update_post_meta($post_id, self::META_LAYOUT, $layout);
        update_post_meta($post_id, self::META_CARDS_PER_SLIDE, (string) $cards_per_slide);
        update_post_meta($post_id, self::META_HEADER_TEXT, $header_text);
        update_post_meta($post_id, self::META_FOOTER_BUTTON, (string) $footer_button);

        update_post_meta($post_id, self::META_CARD_SHOW_IMAGE, (string) $card_show_image);
        update_post_meta($post_id, self::META_CARD_IMAGE_SIZE, (string) $card_image_size);

        update_post_meta($post_id, self::META_STYLE_JSON, wp_json_encode($style_clean));

        // Back-compat for older installs (keep the original 5 keys in sync).
        update_post_meta($post_id, self::META_STYLE_RADIUS, (string) ($style_clean['radius'] ?? ''));
        update_post_meta($post_id, self::META_STYLE_SHADOW, (string) ($style_clean['shadow'] ?? ''));
        update_post_meta($post_id, self::META_STYLE_MEDIA_BG, (string) ($style_clean['media_bg'] ?? ''));
        update_post_meta($post_id, self::META_STYLE_CARD_HEADER_BG, (string) ($style_clean['card_header_bg'] ?? ''));
        update_post_meta($post_id, self::META_STYLE_CARD_HEADER_COLOR, (string) ($style_clean['card_header_color'] ?? ''));
    }

    public static function get_gallery_meta(int $post_id): array
    {
        $media_ids_csv = (string) get_post_meta($post_id, self::META_MEDIA_IDS, true);
        $media_ids = array_values(array_filter(array_map('absint', preg_split('/\s*,\s*/', $media_ids_csv) ?: [])));

        $layout = (string) get_post_meta($post_id, self::META_LAYOUT, true);
        if (!in_array($layout, ['carousel', 'cards'], true)) {
            $layout = 'carousel';
        }

        $cards_per_slide = absint(get_post_meta($post_id, self::META_CARDS_PER_SLIDE, true));
        if ($cards_per_slide < 1 || $cards_per_slide > 6) {
            $cards_per_slide = 3;
        }

        $header_text = (string) get_post_meta($post_id, self::META_HEADER_TEXT, true);
        $footer_button = absint(get_post_meta($post_id, self::META_FOOTER_BUTTON, true)) === 1;

        $card_show_image = absint(get_post_meta($post_id, self::META_CARD_SHOW_IMAGE, true));
        if ($card_show_image !== 0 && $card_show_image !== 1) {
            $card_show_image = 1;
        }

        $card_image_size = sanitize_key((string) get_post_meta($post_id, self::META_CARD_IMAGE_SIZE, true));
        $valid_sizes = get_intermediate_image_sizes();
        $valid_sizes[] = 'full';
        if ($card_image_size === '' || !in_array($card_image_size, $valid_sizes, true)) {
            $card_image_size = 'large';
        }

        $style = [];
        $style_json = (string) get_post_meta($post_id, self::META_STYLE_JSON, true);
        if ($style_json !== '') {
            $decoded = json_decode($style_json, true);
            if (is_array($decoded)) {
                $style = $decoded;
            }
        }

        // Merge legacy meta fields if present.
        $legacy = [
            'radius' => (string) get_post_meta($post_id, self::META_STYLE_RADIUS, true),
            'shadow' => (string) get_post_meta($post_id, self::META_STYLE_SHADOW, true),
            'media_bg' => (string) get_post_meta($post_id, self::META_STYLE_MEDIA_BG, true),
            'card_header_bg' => (string) get_post_meta($post_id, self::META_STYLE_CARD_HEADER_BG, true),
            'card_header_color' => (string) get_post_meta($post_id, self::META_STYLE_CARD_HEADER_COLOR, true),
        ];
        foreach ($legacy as $k => $v) {
            if (!isset($style[$k]) || $style[$k] === '') {
                $style[$k] = $v;
            }
        }

        return [
            'media_ids' => $media_ids,
            'layout' => $layout,
            'cards_per_slide' => $cards_per_slide,
            'header_text' => $header_text,
            'footer_button' => $footer_button,
            'card_show_image' => $card_show_image === 1,
            'card_image_size' => $card_image_size,
            'style' => $style,
        ];
    }

    public function ajax_render_preview(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }

        check_ajax_referer('fg_live_preview', 'nonce');

        $post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        if (!$post_id || get_post_type($post_id) !== FectionWP_Gallery_CPT::POST_TYPE) {
            wp_send_json_error(['message' => 'invalid'], 400);
        }
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }

        $layout = isset($_POST['layout']) ? sanitize_key((string) wp_unslash($_POST['layout'])) : '';
        if (!in_array($layout, ['carousel', 'cards'], true)) {
            $layout = '';
        }

        $cards_per_slide = isset($_POST['cards_per_slide']) ? absint($_POST['cards_per_slide']) : 3;
        $cards_per_slide = max(1, min(6, $cards_per_slide));

        $header = isset($_POST['header']) ? sanitize_text_field((string) wp_unslash($_POST['header'])) : '';
        $footer_button = isset($_POST['footer_button']) ? absint($_POST['footer_button']) : 0;

        $card_show_image = isset($_POST['card_show_image']) ? absint($_POST['card_show_image']) : 1;
        $card_image_size = isset($_POST['card_image_size']) ? sanitize_key((string) wp_unslash($_POST['card_image_size'])) : 'large';
        $valid_sizes = get_intermediate_image_sizes();
        $valid_sizes[] = 'full';
        if (!in_array($card_image_size, $valid_sizes, true)) {
            $card_image_size = 'large';
        }

        $autoplay = isset($_POST['autoplay']) ? absint($_POST['autoplay']) : 1;
        $interval = isset($_POST['interval']) ? absint($_POST['interval']) : 5000;
        $interval = max(0, $interval);

        $media_ids = isset($_POST['media_ids']) ? (string) wp_unslash($_POST['media_ids']) : '';
        $style = isset($_POST['style']) ? (string) wp_unslash($_POST['style']) : '';

        $renderer = new FectionWP_Gallery_Shortcode();
        $html = $renderer->render_shortcode([
            'id' => $post_id,
            'layout' => $layout,
            'cards_per_slide' => $cards_per_slide,
            'header' => $header,
            'footer_button' => (int) (bool) $footer_button,
            'autoplay' => (int) (bool) $autoplay,
            'interval' => $interval,
            'media_ids' => $media_ids,
            'style' => $style,
            'card_show_image' => (int) (bool) $card_show_image,
            'card_image_size' => $card_image_size,
        ]);
        wp_send_json_success(['html' => $html]);
    }
}

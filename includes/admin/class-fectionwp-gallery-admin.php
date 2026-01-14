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
            __('Galleries', 'fectionwp-gallery'),
            __('Galleries', 'fectionwp-gallery'),
            'edit_posts',
            'edit.php?post_type=' . FectionWP_Gallery_CPT::POST_TYPE
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

                    if ($type === 'select') {
                        echo '<select class="regular-text" name="' . esc_attr($name) . '">';
                        foreach ($choices as $choice_value => $choice_label) {
                            echo '<option value="' . esc_attr((string) $choice_value) . '"' . selected($value, (string) $choice_value, false) . '>' . esc_html((string) $choice_label) . '</option>';
                        }
                        echo '</select>';
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

        if (!$is_gallery_post && !$is_plugin_page) {
            return;
        }

        if ($is_gallery_post) {
            wp_enqueue_media();
        }

        wp_enqueue_style(
            'fectionwp-gallery-admin',
            FECTIONWPGALLERY_URL . 'assets/css/fectionwp-gallery-admin.css',
            [],
            FECTIONWPGALLERY_VERSION
        );

        if ($is_gallery_post) {
            wp_enqueue_script(
                'fectionwp-gallery-admin',
                FECTIONWPGALLERY_URL . 'assets/js/admin-media.js',
                ['jquery'],
                FECTIONWPGALLERY_VERSION,
                true
            );

            wp_localize_script('fectionwp-gallery-admin', 'FectionGalleryAdmin', [
                'frameTitle' => __('Select media', 'fectionwp-gallery'),
                'frameButton' => __('Use selected', 'fectionwp-gallery'),
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
        ?>
        <div class="fection-gallery-admin">
            <p>
                <button type="button" class="button button-primary" id="fg-pick-media"><?php echo esc_html__('Choose media', 'fectionwp-gallery'); ?></button>
                <button type="button" class="button" id="fg-clear-media"><?php echo esc_html__('Clear', 'fectionwp-gallery'); ?></button>
            </p>

            <input type="hidden" id="fg-media-ids" name="fg_media_ids" value="<?php echo esc_attr($ids_csv); ?>" />

            <div id="fg-media-preview" class="fg-media-preview">
                <?php foreach ($meta['media_ids'] as $aid) :
                    $thumb = wp_get_attachment_image($aid, 'thumbnail');
                    if ($thumb) {
                        echo '<div class="fg-thumb">' . $thumb . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                endforeach; ?>
            </div>

            <p class="description">
                <?php echo esc_html__('Tip: select images and videos from the Media Library. Order is preserved.', 'fectionwp-gallery'); ?>
            </p>

            <p>
                <?php echo esc_html__('Shortcode:', 'fectionwp-gallery'); ?>
                <code>[fection_gallery id="<?php echo esc_html((string) $post->ID); ?>"]</code>
            </p>
        </div>
        <?php
    }

    public function render_settings_metabox(WP_Post $post): void
    {
        $meta = self::get_gallery_meta($post->ID);
        $styling_url = admin_url('admin.php?page=fectionwp-gallery-styling');
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
                            <input class="widefat" type="text" id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr((string) ($def['default'] ?? '')); ?>" />
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
            'style' => $style,
        ];
    }
}

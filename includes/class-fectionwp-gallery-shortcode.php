<?php

if (!defined('ABSPATH')) {
    exit;
}

class FectionWP_Gallery_Shortcode
{
    public const SHORTCODE = 'fection_gallery';

    private static int $instance_counter = 0;

    public function register(): void
    {
        add_shortcode(self::SHORTCODE, [$this, 'render_shortcode']);
    }

    /**
     * Usage:
     * [fection_gallery id="123" layout="carousel|cards" cards_per_slide="3" header="" footer_button="1" autoplay="1" interval="5000" card_show_image="1" card_image_size="large"]
     */
    public function render_shortcode(array $atts = []): string
    {
        if (!is_admin() && class_exists(FectionWP_Gallery::class)) {
            FectionWP_Gallery::ensure_public_assets();
        }

        $atts = shortcode_atts(
            [
                'id' => 0,
                'layout' => '',
                'cards_per_slide' => 3,
                'header' => '',
                'footer_button' => 0,
                'autoplay' => 1,
                'interval' => 5000,
                // Live preview/admin overrides (optional):
                'media_ids' => '',
                'style' => '',
                'card_show_image' => '',
                'card_image_size' => '',
            ],
            $atts,
            self::SHORTCODE
        );

        $post_id = absint($atts['id']);
        if (!$post_id) {
            return '';
        }

        if (get_post_type($post_id) !== FectionWP_Gallery_CPT::POST_TYPE) {
            return '';
        }

        $meta = FectionWP_Gallery_Admin::get_gallery_meta($post_id);

        $layout = sanitize_key($atts['layout']) ?: $meta['layout'];
        if (!in_array($layout, ['carousel', 'cards'], true)) {
            $layout = 'carousel';
        }

        $cards_per_slide = max(1, min(6, absint($atts['cards_per_slide'] ?: $meta['cards_per_slide'])));

        $header = (string) $atts['header'];
        if ($header === '') {
            $header = $meta['header_text'];
        }

        $footer_button = (bool) absint($atts['footer_button']);
        if (absint($atts['footer_button']) === 0) {
            $footer_button = (bool) $meta['footer_button'];
        }

        $autoplay = (bool) absint($atts['autoplay']);
        $interval = max(0, absint($atts['interval']));

        $attachment_ids = [];
        $media_ids_csv = trim((string) $atts['media_ids']);
        if ($media_ids_csv !== '') {
            $attachment_ids = array_values(array_filter(array_map('absint', preg_split('/\s*,\s*/', $media_ids_csv) ?: [])));
        } else {
            $attachment_ids = array_map('absint', $meta['media_ids']);
            $attachment_ids = array_values(array_filter($attachment_ids));
        }
        if (!$attachment_ids) {
            return '';
        }

        // Ensure the ID is unique within the request (multiple shortcodes on one page).
        self::$instance_counter++;
        $gallery_dom_id = 'fg_' . $post_id . '_' . self::$instance_counter;

        // When caching, we store a template HTML using a stable placeholder ID,
        // then replace it per-render to keep IDs unique within the request.
        $cache_dom_id = 'fg_' . $post_id . '__tpl__';

        $style = is_array($meta['style'] ?? null) ? $meta['style'] : [];
        $style_json = trim((string) $atts['style']);
        if ($style_json !== '') {
            $decoded = json_decode($style_json, true);
            if (is_array($decoded)) {
                $style = $this->sanitize_style_overrides($decoded);
            }
        }

        $style_attr = $this->build_wrapper_style_attr($style);

        $card_show_image = (bool) $meta['card_show_image'];
        if ((string) $atts['card_show_image'] !== '') {
            $card_show_image = absint($atts['card_show_image']) === 1;
        }

        $card_image_size = (string) $meta['card_image_size'];
        if ((string) $atts['card_image_size'] !== '') {
            $card_image_size = sanitize_key((string) $atts['card_image_size']);
        }
        $valid_sizes = get_intermediate_image_sizes();
        $valid_sizes[] = 'full';
        if (!in_array($card_image_size, $valid_sizes, true)) {
            $card_image_size = 'large';
        }

        $cache_key = null;
        $cache_enabled = (bool) apply_filters('fectionwp_gallery_shortcode_cache_enabled', true);
        $can_cache = $cache_enabled
            && !is_admin()
            && !wp_doing_ajax()
            // Avoid caching ad-hoc preview overrides (prevents transient spam).
            && trim((string) $atts['media_ids']) === ''
            && trim((string) $atts['style']) === '';

        if ($can_cache) {
            $gallery_v = class_exists(FectionWP_Gallery::class) ? FectionWP_Gallery::get_gallery_cache_version($post_id) : 1;
            $global_v = class_exists(FectionWP_Gallery::class) ? FectionWP_Gallery::get_global_cache_version() : 1;

            $cache_payload = [
                'post_id' => $post_id,
                'gallery_v' => $gallery_v,
                'global_v' => $global_v,
                'layout' => $layout,
                'cards_per_slide' => $cards_per_slide,
                'header' => $header,
                'footer_button' => $footer_button,
                'autoplay' => $autoplay,
                'interval' => $interval,
                'card_show_image' => $card_show_image,
                'card_image_size' => $card_image_size,
                'attachments' => $attachment_ids,
                'style_attr' => $style_attr,
                'locale' => function_exists('get_locale') ? get_locale() : '',
                'rtl' => function_exists('is_rtl') ? is_rtl() : false,
            ];

            $cache_key = 'fg_sc_' . md5(wp_json_encode($cache_payload));
            $cached = get_transient($cache_key);
            if (is_string($cached) && $cached !== '') {
                return str_replace($cache_dom_id, $gallery_dom_id, $cached);
            }
        }

        if ($layout === 'cards') {
            $html_template = $this->render_cards_slider($can_cache ? $cache_dom_id : $gallery_dom_id, $style_attr, $attachment_ids, $header, $footer_button, $cards_per_slide, $autoplay, $interval, $card_show_image, $card_image_size);
        } else {
            $html_template = $this->render_carousel($can_cache ? $cache_dom_id : $gallery_dom_id, $style_attr, $attachment_ids, $header, $autoplay, $interval);
        }

        $html = $can_cache ? str_replace($cache_dom_id, $gallery_dom_id, $html_template) : $html_template;

        if ($can_cache && $cache_key) {
            $ttl = absint(apply_filters('fectionwp_gallery_shortcode_cache_ttl', 3600));
            if ($ttl > 0) {
                set_transient($cache_key, $html_template, $ttl);
            }
        }

        return $html;
    }

    private function render_carousel(string $gallery_dom_id, string $style_attr, array $attachment_ids, string $header, bool $autoplay, int $interval): string
    {
        $has_video = false;
        foreach ($attachment_ids as $aid) {
            $mime = get_post_mime_type($aid);
            if (is_string($mime) && strpos($mime, 'video/') === 0) {
                $has_video = true;
                break;
            }
        }

        $ride = ($autoplay && !$has_video) ? 'carousel' : false;
        $data_interval = ($autoplay && !$has_video && $interval > 0) ? $interval : false;

        $title_id = $header !== '' ? ($gallery_dom_id . '_title') : '';
        $count = count($attachment_ids);

        ob_start();
        ?>
        <div class="fectionwp-gallery" id="<?php echo esc_attr($gallery_dom_id); ?>_wrap"<?php echo $style_attr; ?> role="region"<?php echo $title_id ? ' aria-labelledby="' . esc_attr($title_id) . '"' : ' aria-label="' . esc_attr__('Media gallery', 'fectionwp-gallery') . '"'; ?>>
            <div class="fg-section">
                <div class="container-fluid p-0">
                    <?php if ($header !== '') : ?>
                        <div class="mb-3">
                            <h3 class="fg-title" id="<?php echo esc_attr($title_id); ?>"><?php echo esc_html($header); ?></h3>
                        </div>
                    <?php endif; ?>

                    <div id="<?php echo esc_attr($gallery_dom_id); ?>" class="carousel slide"<?php echo $ride ? ' data-bs-ride="' . esc_attr($ride) . '"' : ''; ?><?php echo $data_interval !== false ? ' data-bs-interval="' . esc_attr((string) $data_interval) . '"' : ' data-bs-interval="false"'; ?> aria-roledescription="carousel"<?php echo $title_id ? ' aria-labelledby="' . esc_attr($title_id) . '"' : ''; ?>>
                    <div class="carousel-indicators" aria-label="<?php echo esc_attr__('Slides', 'fectionwp-gallery'); ?>">
                        <?php foreach ($attachment_ids as $index => $aid) : ?>
                            <button type="button" data-bs-target="#<?php echo esc_attr($gallery_dom_id); ?>" data-bs-slide-to="<?php echo esc_attr((string) $index); ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr(sprintf(__('Slide %d', 'fectionwp-gallery'), $index + 1)); ?>"></button>
                        <?php endforeach; ?>
                    </div>

                    <div class="carousel-inner">
                        <?php foreach ($attachment_ids as $index => $aid) : ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr(sprintf(__('%1$d of %2$d', 'fectionwp-gallery'), $index + 1, $count)); ?>">
                                <div class="fg-media">
                                    <?php echo $this->render_media($aid, 'w-100'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo esc_attr($gallery_dom_id); ?>" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden"><?php echo esc_html__('Previous', 'fectionwp-gallery'); ?></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#<?php echo esc_attr($gallery_dom_id); ?>" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden"><?php echo esc_html__('Next', 'fectionwp-gallery'); ?></span>
                    </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function render_cards_slider(string $gallery_dom_id, string $style_attr, array $attachment_ids, string $header, bool $footer_button, int $cards_per_slide, bool $autoplay, int $interval, bool $card_show_image, string $card_image_size): string
    {
        $chunks = array_chunk($attachment_ids, $cards_per_slide);

        $ride = ($autoplay && $interval > 0) ? 'carousel' : false;
        $data_interval = ($autoplay && $interval > 0) ? $interval : false;

        if ($cards_per_slide === 1) {
            $col_class = 'col-12';
        } elseif ($cards_per_slide === 2) {
            $col_class = 'col-12 col-md-6';
        } elseif ($cards_per_slide === 3) {
            $col_class = 'col-12 col-md-4';
        } elseif ($cards_per_slide === 4) {
            $col_class = 'col-12 col-md-6 col-lg-3';
        } elseif ($cards_per_slide === 5) {
            $col_class = 'col-12 col-md-6 col-lg-3';
        } else {
            $col_class = 'col-12 col-md-4 col-lg-2';
        }

        $title_id = $header !== '' ? ($gallery_dom_id . '_title') : '';
        $slide_count = count($chunks);

        ob_start();
        ?>
        <div class="fectionwp-gallery" id="<?php echo esc_attr($gallery_dom_id); ?>_wrap"<?php echo $style_attr; ?> role="region"<?php echo $title_id ? ' aria-labelledby="' . esc_attr($title_id) . '"' : ' aria-label="' . esc_attr__('Media gallery', 'fectionwp-gallery') . '"'; ?>>
            <div class="fg-section">
                <?php if ($header !== '') : ?>
                    <div class="mb-3">
                        <h3 class="fg-title" id="<?php echo esc_attr($title_id); ?>"><?php echo esc_html($header); ?></h3>
                    </div>
                <?php endif; ?>

                <div id="<?php echo esc_attr($gallery_dom_id); ?>" class="carousel slide"<?php echo $ride ? ' data-bs-ride="' . esc_attr($ride) . '"' : ''; ?><?php echo $data_interval !== false ? ' data-bs-interval="' . esc_attr((string) $data_interval) . '"' : ' data-bs-interval="false"'; ?> aria-roledescription="carousel"<?php echo $title_id ? ' aria-labelledby="' . esc_attr($title_id) . '"' : ''; ?>>
                    <div class="carousel-inner">
                        <?php foreach ($chunks as $slide_index => $slide_items) : ?>
                            <div class="carousel-item <?php echo $slide_index === 0 ? 'active' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr(sprintf(__('%1$d of %2$d', 'fectionwp-gallery'), $slide_index + 1, $slide_count)); ?>">
                                <div class="row g-3">
                                <?php foreach ($slide_items as $aid) : ?>
                                    <div class="<?php echo esc_attr($col_class); ?>">
                                        <div class="card h-100">
                                            <?php if ($card_show_image) : ?>
                                                <div class="fg-media">
                                                    <?php echo $this->render_media($aid, 'card-img-top', $card_image_size); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php
                                            $caption = wp_get_attachment_caption($aid);
                                            if ($caption) :
                                                ?>
                                                <div class="card-body">
                                                    <p class="card-text mb-0"><?php echo esc_html($caption); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($footer_button) : ?>
                                                <div class="card-footer bg-transparent border-0 pt-0">
                                                    <a class="btn btn-sm fg-open-btn" href="<?php echo esc_url(wp_get_attachment_url($aid)); ?>" target="_blank" rel="noopener">
                                                        <?php echo esc_html__('Open', 'fectionwp-gallery'); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo esc_attr($gallery_dom_id); ?>" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden"><?php echo esc_html__('Previous', 'fectionwp-gallery'); ?></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#<?php echo esc_attr($gallery_dom_id); ?>" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden"><?php echo esc_html__('Next', 'fectionwp-gallery'); ?></span>
                </button>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function build_wrapper_style_attr(array $style): string
    {
        $vars = [];

        foreach (FectionWP_Gallery_Admin::get_style_schema() as $key => $def) {
            $var = isset($def['var']) ? (string) $def['var'] : '';
            if ($var === '') {
                continue;
            }
            $value = isset($style[$key]) ? trim((string) $style[$key]) : '';
            if ($value === '') {
                continue;
            }
            $vars[] = $var . ':' . $value;
        }

        if (!$vars) {
            return '';
        }

        // Style attribute value is escaped as a whole.
        return ' style="' . esc_attr(implode(';', $vars) . ';') . '"';
    }

    private function sanitize_style_overrides(array $decoded): array
    {
        $out = [];

        foreach (FectionWP_Gallery_Admin::get_style_schema() as $key => $def) {
            if (!array_key_exists($key, $decoded)) {
                continue;
            }

            $raw = trim((string) $decoded[$key]);
            if ($raw === '') {
                continue;
            }

            $type = isset($def['type']) ? (string) $def['type'] : 'text';
            $clean = $this->sanitize_style_value_by_type($raw, $type, $def);
            if ($clean === '') {
                continue;
            }

            $out[$key] = $clean;
        }

        return $out;
    }

    private function sanitize_style_value_by_type(string $value, string $type, array $def): string
    {
        if ($type === 'length') {
            return $this->sanitize_css_length($value);
        }
        if ($type === 'shadow') {
            return $this->sanitize_css_shadow($value);
        }
        if ($type === 'color') {
            return $this->sanitize_css_color($value);
        }
        if ($type === 'number') {
            $value = (string) sanitize_text_field($value);
            if (!is_numeric($value)) {
                return '';
            }
            $n = (float) $value;
            if (isset($def['min']) && is_numeric($def['min'])) {
                $n = max((float) $def['min'], $n);
            }
            if (isset($def['max']) && is_numeric($def['max'])) {
                $n = min((float) $def['max'], $n);
            }
            return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
        }
        if ($type === 'select') {
            $choices = isset($def['choices']) && is_array($def['choices']) ? array_keys($def['choices']) : [];
            $value = sanitize_key($value);
            return in_array($value, $choices, true) ? $value : '';
        }
        if ($type === 'ratio') {
            $value = preg_replace('/\s+/', '', $value);
            if (preg_match('/^\d+(?:\.\d+)?\/\d+(?:\.\d+)?$/', $value)) {
                return $value;
            }
            if (is_numeric($value)) {
                return (string) $value;
            }
            return '';
        }

        $value = sanitize_text_field($value);
        return strlen($value) > 200 ? '' : $value;
    }

    private function sanitize_css_length(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        return preg_match('/^(?:0|\d+(?:\.\d+)?)(?:px|rem|em|%|vh|vw)?$/', $value) ? $value : '';
    }

    private function sanitize_css_shadow(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $value = sanitize_text_field($value);
        return strlen($value) > 200 ? '' : $value;
    }

    private function sanitize_css_color(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $hex = sanitize_hex_color($value);
        if (is_string($hex) && $hex !== '') {
            return $hex;
        }

        if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/', $value)) {
            return $value;
        }

        return '';
    }

    private function render_media(int $attachment_id, string $class, string $image_size = 'large'): string
    {
        $mime = (string) get_post_mime_type($attachment_id);

        if (strpos($mime, 'image/') === 0) {
            return wp_get_attachment_image(
                $attachment_id,
                $image_size,
                false,
                [
                    'class' => trim('d-block ' . $class),
                    'loading' => 'lazy',
                    'decoding' => 'async',
                ]
            );
        }

        if (strpos($mime, 'video/') === 0) {
            $url = wp_get_attachment_url($attachment_id);
            if (!$url) {
                return '';
            }

            $label = wp_get_attachment_caption($attachment_id);
            if (!$label) {
                $label = get_the_title($attachment_id);
            }
            $label = is_string($label) ? trim($label) : '';
            if ($label === '') {
                $label = (string) __('Video', 'fectionwp-gallery');
            }

            return sprintf(
                '<video class="%s" controls playsinline preload="metadata" aria-label="%s" title="%s"><source src="%s" type="%s"></video>',
                esc_attr(trim('d-block ' . $class)),
                esc_attr($label),
                esc_attr($label),
                esc_url($url),
                esc_attr($mime)
            );
        }

        $url = wp_get_attachment_url($attachment_id);
        if (!$url) {
            return '';
        }

        return sprintf(
            '<a class="btn btn-outline-light" href="%s" target="_blank" rel="noopener">%s</a>',
            esc_url($url),
            esc_html__('Open media', 'fectionwp-gallery')
        );
    }
}

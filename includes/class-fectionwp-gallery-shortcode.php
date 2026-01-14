<?php

if (!defined('ABSPATH')) {
    exit;
}

class FectionWP_Gallery_Shortcode
{
    public const SHORTCODE = 'fection_gallery';

    public function register(): void
    {
        add_shortcode(self::SHORTCODE, [$this, 'render_shortcode']);
    }

    /**
     * Usage:
     * [fection_gallery id="123" layout="carousel|cards" cards_per_slide="3" header="" footer_button="1" autoplay="1" interval="5000"]
     */
    public function render_shortcode(array $atts = []): string
    {
        $atts = shortcode_atts(
            [
                'id' => 0,
                'layout' => '',
                'cards_per_slide' => 3,
                'header' => '',
                'footer_button' => 0,
                'autoplay' => 1,
                'interval' => 5000,
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

        $attachment_ids = array_map('absint', $meta['media_ids']);
        $attachment_ids = array_values(array_filter($attachment_ids));
        if (!$attachment_ids) {
            return '';
        }

        $gallery_dom_id = 'fg_' . $post_id . '_' . wp_generate_uuid4();

        $style_attr = $this->build_wrapper_style_attr(is_array($meta['style'] ?? null) ? $meta['style'] : []);

        if ($layout === 'cards') {
            return $this->render_cards_slider($gallery_dom_id, $style_attr, $attachment_ids, $header, $footer_button, $cards_per_slide, $autoplay, $interval);
        }

        return $this->render_carousel($gallery_dom_id, $style_attr, $attachment_ids, $header, $autoplay, $interval);
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

        ob_start();
        ?>
        <div class="fectionwp-gallery" id="<?php echo esc_attr($gallery_dom_id); ?>_wrap"<?php echo $style_attr; ?>>
            <div class="container-fluid p-0">
                <?php if ($header !== '') : ?>
                    <div class="mb-3">
                        <h3 class="fg-title"><?php echo esc_html($header); ?></h3>
                    </div>
                <?php endif; ?>

                <div id="<?php echo esc_attr($gallery_dom_id); ?>" class="carousel slide"<?php echo $ride ? ' data-bs-ride="' . esc_attr($ride) . '"' : ''; ?><?php echo $data_interval !== false ? ' data-bs-interval="' . esc_attr((string) $data_interval) . '"' : ' data-bs-interval="false"'; ?> aria-label="<?php echo esc_attr__('Media gallery', 'fectionwp-gallery'); ?>">
                    <div class="carousel-indicators">
                        <?php foreach ($attachment_ids as $index => $aid) : ?>
                            <button type="button" data-bs-target="#<?php echo esc_attr($gallery_dom_id); ?>" data-bs-slide-to="<?php echo esc_attr((string) $index); ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr(sprintf(__('Slide %d', 'fectionwp-gallery'), $index + 1)); ?>"></button>
                        <?php endforeach; ?>
                    </div>

                    <div class="carousel-inner">
                        <?php foreach ($attachment_ids as $index => $aid) : ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
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
        <?php
        return (string) ob_get_clean();
    }

    private function render_cards_slider(string $gallery_dom_id, string $style_attr, array $attachment_ids, string $header, bool $footer_button, int $cards_per_slide, bool $autoplay, int $interval): string
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

        ob_start();
        ?>
        <div class="fectionwp-gallery" id="<?php echo esc_attr($gallery_dom_id); ?>_wrap"<?php echo $style_attr; ?>>
            <?php if ($header !== '') : ?>
                <div class="mb-3">
                    <h3 class="fg-title"><?php echo esc_html($header); ?></h3>
                </div>
            <?php endif; ?>

            <div id="<?php echo esc_attr($gallery_dom_id); ?>" class="carousel slide"<?php echo $ride ? ' data-bs-ride="' . esc_attr($ride) . '"' : ''; ?><?php echo $data_interval !== false ? ' data-bs-interval="' . esc_attr((string) $data_interval) . '"' : ' data-bs-interval="false"'; ?>>
                <div class="carousel-inner">
                    <?php foreach ($chunks as $slide_index => $slide_items) : ?>
                        <div class="carousel-item <?php echo $slide_index === 0 ? 'active' : ''; ?>">
                            <div class="row g-3">
                                <?php foreach ($slide_items as $aid) : ?>
                                    <div class="<?php echo esc_attr($col_class); ?>">
                                        <div class="card h-100">
                                            <div class="fg-media">
                                                <?php echo $this->render_media($aid, 'card-img-top'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                            </div>
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

    private function render_media(int $attachment_id, string $class): string
    {
        $mime = (string) get_post_mime_type($attachment_id);

        if (strpos($mime, 'image/') === 0) {
            return wp_get_attachment_image(
                $attachment_id,
                'large',
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

            return sprintf(
                '<video class="%s" controls playsinline preload="metadata"><source src="%s" type="%s"></video>',
                esc_attr(trim('d-block ' . $class)),
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

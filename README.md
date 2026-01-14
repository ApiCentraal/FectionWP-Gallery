# FectionWP Gallery

A Bootstrap 5.3 powered WordPress gallery plugin for images and videos.

## Features

- Custom Post Type (Galleries) with media picker and ordering
- Shortcode renderer (carousel or card slider)
- Widget for sidebars
- Global styling page + per-gallery styling overrides

## Usage

- Basic: `[fection_gallery id="123"]`
- Cards: `[fection_gallery id="123" layout="cards" cards_per_slide="3" header="My header" footer_button="1"]`

## Filters

- `fectionwp_gallery_bootstrap_source`: `local` (default), `cdn`, `none`
- `fectionwp_gallery_always_enqueue_assets`: return `true` to always enqueue frontend assets
- `fectionwp_gallery_shortcode_cache_enabled`: return `false` to disable shortcode HTML caching
- `fectionwp_gallery_shortcode_cache_ttl`: cache TTL in seconds (default: `3600`)

## Development

- PHP lint: `php -l includes/class-fectionwp-gallery.php`
- Compile translations: `python3 tools/compile_mo.py languages/fectionwp-gallery-nl_NL.po languages/fectionwp-gallery-nl_NL.mo`

## Security & Contributing

- Security policy: see `SECURITY.md`
- Contributing guide: see `CONTRIBUTING.md`

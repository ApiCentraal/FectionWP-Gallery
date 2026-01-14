# Contributing

Thanks for contributing to FectionWP Gallery.

## Development setup

- PHP: 7.4+ (8.x supported)
- WordPress: latest stable recommended

## Local checks

PHP lint (fast sanity check):

- `php -l fectionwp-gallery.php`
- `php -l includes/class-fectionwp-gallery.php`
- `php -l includes/class-fectionwp-gallery-shortcode.php`

## Translations

Translations live in `languages/`.

If you edit a `.po` file and don’t have gettext tooling available, you can compile the `.mo` via:

- `python3 tools/compile_mo.py languages/fectionwp-gallery-nl_NL.po languages/fectionwp-gallery-nl_NL.mo`

## Pull requests

- Keep PRs focused and small when possible.
- Avoid unrelated formatting churn.
- Include a short description and screenshots for UI changes.

## Security

If you believe you found a security issue, please follow the private process in `SECURITY.md`.

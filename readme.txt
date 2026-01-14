=== FectionWP Gallery ===
Contributors: fectionlabs
Tags: gallery, slider, carousel, bootstrap, video
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Bootstrap 5.3 powered gallery plugin for images and videos. Create galleries in wp-admin, then embed via shortcode or widget.

== Description ==

FectionWP Gallery lets you:

* Create galleries (custom post type) with images and videos.
* Render as a Bootstrap carousel or a “card slider”.
* Use a sidebar widget.
* Control styling globally (Fection Gallery → Styling) and override per gallery.
* Apply 1-click presets (White/Black).

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Fection Gallery → Galleries and create a new gallery.

== Usage ==

Shortcode:

`[fection_gallery id="123"]`

Card slider:

`[fection_gallery id="123" layout="cards" cards_per_slide="3" header="Mijn header" footer_button="1"]`

Widget:

Appearance → Widgets → “Fection Gallery”.

== Frequently Asked Questions ==

= Does it load Bootstrap from a CDN? =

By default the plugin uses bundled Bootstrap 5.3.3 files (recommended for WordPress.org submission). Themes can override using the filter `fectionwp_gallery_bootstrap_source` with values: `local`, `cdn`, or `none`.

= Does uninstall delete my galleries? =

No. Uninstall removes plugin options and known meta keys, but keeps the gallery posts.

== Changelog ==

= 1.0.7 =
* Improve: Styling page navigation + filtering (hides empty sections and shows a no-results message).
* Improve: section wrapper behaves more reliably (prevents margin-collapsing).
* Tweak: update translations.

= 1.0.6 =
* Add: “Business” preset for a more professional (white/dark) look.
* Add: gallery output is wrapped in its own section with configurable background/border/padding/radius.

= 1.0.5 =
* Change: removed the separate “Preview” menu page (preview is now integrated in the gallery edit screen).

= 1.0.4 =
* Improve: edit screen now includes a live preview so you can immediately see what you are changing.
* Add: card slider can optionally hide media and supports configurable card image size.
* Add: styling options for card media sizing (aspect/fit/max height).

= 1.0.3 =
* Improve: gallery builder now supports drag & drop ordering and quick actions.
* Improve: styling page adds color picker + filter.

= 1.0.2 =
* Add: Gallery Preview admin page (preview a gallery in a slider from the plugin menu).

= 1.0.1 =
* Fix: prevent duplicate “Galleries” items in the admin menu.

= 1.0.0 =
* First stable release.
* Adds Dutch translation template and `nl_NL` (source) translations.

= 0.1.0 =
* Initial release (development).

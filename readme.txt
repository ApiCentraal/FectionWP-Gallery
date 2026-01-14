=== FectionWP Gallery ===
Contributors: fectionlabs
Tags: gallery, slider, carousel, bootstrap, video
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
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

= 0.1.0 =
* Initial release.

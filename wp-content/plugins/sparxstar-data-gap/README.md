=== Sparxstar Data Gap — Neural Language Map ===
Contributors: starisian
Tags: language map, three.js, globe, visualization, data-gap
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Interactive data-gap visualization of language families and internet presence inequality via [sparxstar_data_gap].

== Description ==

Use the shortcode `[sparxstar_data_gap]` on any page or post to embed the interactive Data Gap visualization. It shows two views:

* **Language Inter-Connectivity** — language communities plotted by cultural relatedness, with Bantu, trade-route, diaspora, and distant edges.
* **Internet Presence** — the same communities seen through the internet's eyes, with city nodes colored by content density and arcs showing data-pipe inequality.

Features:
* Three.js r184 — self-hosted, no external CDN.
* Embedded land-ring data — no GeoJSON/TopoJSON runtime fetch required.
* Self-hosted DuneRise font slot (place `DuneRise.woff2` in `assets/fonts/`).
* Drag-to-rotate, touch support, tooltip on hover, spark animation.
* Responsive — adapts to small screens.

Shortcode attributes:

| Attribute | Default | Description                       |
|-----------|---------|-----------------------------------|
| height    | 600     | Container height in pixels (min 300). |

Example: `[sparxstar_data_gap height="700"]`

== Installation ==

1. Upload the `sparxstar-data-gap` folder to `/wp-content/plugins/`.
2. Place `DuneRise.woff2` (and optionally `DuneRise.woff`) in `assets/fonts/`.
3. Activate the plugin through the **Plugins** screen in WordPress.
4. Add `[sparxstar_data_gap]` to any page, post, or widget.

== Changelog ==

= 1.1.0 =
* Restored original Data Gap visualization (language inter-connectivity + internet presence globe).
* Fixed plugin header: removed invalid Network header, removed unused Domain Path.
* Removed discouraged load_plugin_textdomain() call (WP 4.6+ auto-loads).
* Updated Tested up to 6.9.

= 1.0.0 =
* Initial release.

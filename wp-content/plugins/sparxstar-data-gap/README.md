=== Sparxstar Data Gap — Neural Language Map ===
Contributors: starisian
Tags: language map, three.js, globe, visualization, data-gap
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An interactive Three.js neural globe of world language families, embedded via
a WordPress shortcode.  Fully self-hosted — no external CDN or runtime fetches.

== Description ==

Use the shortcode `[sparxstar_data_gap]` on any page or post to embed the
interactive Neural Language Map.

Features:
* Three.js r0.184.0 globe — served from the plugin, not an external CDN.
* Embedded land-ring data — no GeoJSON/TopoJSON runtime fetch.
* Self-hosted DuneRise font — no db.onlinewebfonts.com request.
* Drag-to-rotate, touch support, click-to-identify language nodes.
* Accessible labeling and descriptive text for assistive technologies.
* Responsive — degrades gracefully on 2G/3G and low-end Android devices.

Shortcode attributes:

| Attribute | Default              | Description                          |
|-----------|----------------------|--------------------------------------|
| height    | 600                  | Canvas height in pixels (min 200).   |
| heading   | Neural Language Map  | Overlay heading text.                |

Example: `[sparxstar_data_gap height="500" heading="World Languages"]`

== Installation ==

1. Upload the `sparxstar-data-gap` folder to `/wp-content/plugins/`.
2. Place `DuneRise.woff2` (and optionally `DuneRise.woff`) in
   `assets/fonts/`.  See `assets/fonts/README.md` for details.
3. Activate the plugin through the **Plugins** screen in WordPress.
4. Add `[sparxstar_data_gap]` to any page, post, or widget.

== Changelog ==

= 1.0.0 =
* Initial release.
* Self-hosted Three.js r0.184.0 (bundled via esbuild, no CDN).
* Embedded LAND_RINGS — async GeoJSON/TopoJSON fetch removed.
* Self-hosted DuneRise font slot.
* Shortcode [sparxstar_data_gap] with height and heading attributes.

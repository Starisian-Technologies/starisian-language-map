# Sparxstar Data Gap — Neural Language Map

A WordPress plugin that embeds an interactive Three.js globe of world language
families via a shortcode.  All assets are self-hosted — no external CDN or
runtime fetches, no CORS issues, no Content Security Policy conflicts.

## Shortcode

```
[sparxstar_data_gap]
[sparxstar_data_gap height="500" heading="World Languages"]
```

| Attribute | Default             | Description                        |
|-----------|---------------------|------------------------------------|
| `height`  | `600`               | Canvas height in pixels (min 200). |
| `heading` | Neural Language Map | Overlay heading text.              |

## Installation

1. Download the latest `sparxstar-data-gap.zip` from the
   [Releases](../../releases) page.
2. In WordPress → **Plugins → Add New → Upload Plugin**, select the zip.
3. Activate.
4. Place `DuneRise.woff2` (and optionally `DuneRise.woff`) in
   `wp-content/plugins/sparxstar-data-gap/assets/fonts/`.
   See [`assets/fonts/README.md`](wp-content/plugins/sparxstar-data-gap/assets/fonts/README.md).
5. Add `[sparxstar_data_gap]` to any page, post, or widget.

## Requirements

| Requirement      | Minimum |
|------------------|---------|
| WordPress        | 6.2     |
| PHP              | 8.2     |
| Browser (WebGL)  | Chrome 80 / Firefox 75 / Safari 14 |

## Development

### Prerequisites

- Node.js 20+
- npm 10+
- PHP 8.2+ (for lint)

### Setup

```bash
npm install
```

### Build (minify JS + CSS)

```bash
npm run build
```

This produces:

- `assets/js/neural-map.min.js` — minified via [Terser](https://terser.org/)
- `assets/css/neural-map.min.css` — minified via [cssnano](https://cssnano.github.io/cssnano/)

The plugin loads `.min.` variants in production and the source files when
`SCRIPT_DEBUG` is `true`.

### Workflows

| Workflow | Trigger | Description |
|----------|---------|-------------|
| **Build** | push / pull_request → `main` | Install deps, minify JS+CSS, PHP lint |
| **Release** | push tag `v*` | Update versions, minify, zip plugin, publish GitHub Release with SHA-256 checksum |

## Release process

Push a semver tag to trigger the automated release:

```bash
git tag v1.0.0
git push origin v1.0.0
```

The release workflow will:

1. Strip the `v` prefix to derive the version number (e.g. `1.0.0`).
2. Update `Version:` in the PHP plugin header.
3. Update `SPX_DATA_GAP_VERSION` constant in PHP.
4. Update `@version` in `neural-map.js` and `neural-map.css`.
5. Minify JS and CSS.
6. Zip the plugin folder as `sparxstar-data-gap.zip`.
7. Compute the SHA-256 checksum and save it as `sparxstar-data-gap.zip.sha256`.
8. Publish a GitHub Release with both files attached.

## Architecture

```
wp-content/plugins/sparxstar-data-gap/
├── sparxstar-data-gap.php          # Plugin entry — shortcode + asset enqueue
├── assets/
│   ├── js/
│   │   ├── three.min.js            # Three.js r0.184 (self-hosted, MIT)
│   │   ├── neural-map.js           # Visualization source
│   │   └── neural-map.min.js       # Minified (generated — not committed)
│   ├── css/
│   │   ├── neural-map.css          # Stylesheet source
│   │   └── neural-map.min.css      # Minified (generated — not committed)
│   └── fonts/
│       ├── DuneRise.woff2          # Self-hosted font (not committed — see README)
│       └── README.md
└── README.md                       # Plugin readme (WordPress.org format)
```

## License

GPL-2.0-or-later — see [GNU GPL v2](https://www.gnu.org/licenses/gpl-2.0.html).

Three.js is MIT licensed — copyright 2010–2021 Three.js Authors.

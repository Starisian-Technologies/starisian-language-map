# DuneRise Font — Placement Instructions

Place the following self-hosted font files in this directory:

| File             | Format |
|------------------|--------|
| `DuneRise.woff2` | WOFF2  |
| `DuneRise.woff`  | WOFF   |

The `@font-face` declaration in `assets/css/neural-map.css` references both
files.  If only the `.woff2` variant is available the `.woff` line may be
removed from the stylesheet; modern browsers (2018+) support WOFF2.

## License reminder

Ensure your licence for the Dune Rise typeface permits self-hosting and web
embedding before placing the files here.  The CSS `font-display: swap`
property is already set, so pages will render immediately with the system
fallback font if the asset has not yet been loaded — no layout-blocking occurs.

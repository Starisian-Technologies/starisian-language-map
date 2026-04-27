<?php
/**
 * Plugin Name:       Sparxstar Data Gap — Neural Language Map
 * Plugin URI:        https://starisian.com/plugins/sparxstar-data-gap
 * Description:       Embeds an interactive Three.js neural map of world language families via the [sparxstar_data_gap] shortcode. All assets are self-hosted — no external CDN or runtime fetches.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * Author:            Starisian Technologies
 * Author URI:        https://starisian.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sparxstar-data-gap
 * Domain Path:       /languages
 * Network:           false
 *
 * @package Starisian\Sparxstar\DataGap
 */

declare( strict_types=1 );

namespace Starisian\Sparxstar\DataGap;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Constants ────────────────────────────────────────────────────────────────

/** Absolute path to the plugin root directory, with trailing slash. */
if ( ! defined( 'SPX_DATA_GAP_DIR' ) ) {
	define( 'SPX_DATA_GAP_DIR', plugin_dir_path( __FILE__ ) );
}

/** Public URL to the plugin root, with trailing slash. */
if ( ! defined( 'SPX_DATA_GAP_URL' ) ) {
	define( 'SPX_DATA_GAP_URL', plugin_dir_url( __FILE__ ) );
}

/** Plugin version — bump on every release to bust asset caches. */
if ( ! defined( 'SPX_DATA_GAP_VERSION' ) ) {
	define( 'SPX_DATA_GAP_VERSION', '1.0.0' );
}

// ── i18n ──────────────────────────────────────────────────────────────────────

/**
 * Load the plugin text domain so that translations in /languages are picked up
 * on installs that are not using translate.wordpress.org auto-loading.
 *
 * @return void
 */
function spx_data_gap_load_textdomain(): void {
	load_plugin_textdomain(
		'sparxstar-data-gap',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\spx_data_gap_load_textdomain' );

// ── Asset registration ────────────────────────────────────────────────────────

/**
 * Register and enqueue front-end assets when the shortcode is present on the
 * current request.  Assets are registered on every request so that page
 * builders and caching plugins can discover them; they are only enqueued after
 * the shortcode has been rendered.
 *
 * @return void
 */
function spx_data_gap_register_assets(): void {
	$script_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;

	// Prefer unminified assets when SCRIPT_DEBUG is on.  When SCRIPT_DEBUG is
	// off, load the .min. variant only if the build has been run; otherwise fall
	// back to the source file so the plugin works in a fresh checkout too.
	$js_min_path  = SPX_DATA_GAP_DIR . 'assets/js/neural-map.min.js';
	$css_min_path = SPX_DATA_GAP_DIR . 'assets/css/neural-map.min.css';

	// True when we will load the esbuild production bundle (neural-map.min.js).
	// That bundle includes Three.js internally, so no separate spx-three-js
	// script is needed.  The source neural-map.js (SCRIPT_DEBUG / fresh
	// checkout) still expects window.THREE set by three.min.js.
	$use_bundle = ! $script_debug && file_exists( $js_min_path );

	$js_suffix  = $use_bundle ? '.min' : '';
	$css_suffix = ( ! $script_debug && file_exists( $css_min_path ) ) ? '.min' : '';

	// Three.js — self-hosted bundle generated via `npm run vendor:three`.
	// Loaded only when using the source neural-map.js (SCRIPT_DEBUG / fresh
	// checkout).  When the production esbuild bundle is used, Three.js is
	// already included inside neural-map.min.js and this script is skipped.
	wp_register_script(
		'spx-three-js',
		SPX_DATA_GAP_URL . 'assets/js/three.min.js',
		[],
		'0.184.0',
		true   // Load in footer.
	);

	// Neural map visualization.
	// • Production bundle (neural-map.min.js): Three.js bundled inside via
	//   esbuild `--inject`, so no spx-three-js dependency.
	// • Source file (neural-map.js, SCRIPT_DEBUG / fresh checkout): reads
	//   window.THREE, so spx-three-js must load first.
	$map_deps = $use_bundle ? [] : [ 'spx-three-js' ];
	wp_register_script(
		'spx-neural-map',
		SPX_DATA_GAP_URL . "assets/js/neural-map{$js_suffix}.js",
		$map_deps,
		SPX_DATA_GAP_VERSION,
		true   // Load in footer.
	);

	// Stylesheet.
	wp_register_style(
		'spx-neural-map',
		SPX_DATA_GAP_URL . "assets/css/neural-map{$css_suffix}.css",
		[],
		SPX_DATA_GAP_VERSION
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\spx_data_gap_register_assets' );

// ── Shortcode ─────────────────────────────────────────────────────────────────

/**
 * Render the [sparxstar_data_gap] shortcode.
 *
 * Accepted attributes:
 *   height  – Canvas height in px (default 600).
 *   heading – Overlay heading text (default 'Neural Language Map').
 *
 * Example:
 *   [sparxstar_data_gap height="500" heading="World Languages"]
 *
 * @param array<string,string>|string $atts Raw shortcode attributes.
 * @return string                           HTML output.
 */
function spx_data_gap_shortcode( $atts ): string {
	// Ensure assets are registered even when the shortcode runs before the
	// wp_enqueue_scripts hook fires (e.g. theme calling do_shortcode() early,
	// page-builder preview contexts).  wp_register_script/style are idempotent
	// so calling this a second time after the hook is a no-op.
	spx_data_gap_register_assets();

	// Enqueue assets — safe to call multiple times; WP deduplicates.
	wp_enqueue_style( 'spx-neural-map' );
	wp_enqueue_script( 'spx-neural-map' );

	// Sanitize and validate attributes.
	$atts = shortcode_atts(
		[
			'height'  => '600',
			'heading' => __( 'Neural Language Map', 'sparxstar-data-gap' ),
		],
		$atts,
		'sparxstar_data_gap'
	);

	$height  = absint( $atts['height'] );
	$heading = sanitize_text_field( $atts['heading'] );

	// Default height if absint() produced 0; otherwise clamp to the minimum.
	if ( 0 === $height ) {
		$height = 600;
	} elseif ( $height < 200 ) {
		$height = 200;
	}

	// Generate unique IDs so multiple shortcode instances can coexist on one
	// page without duplicate IDs or settings collisions.  Configuration is
	// passed via data-* attributes on the container element; the JS reads from
	// those attributes when it initialises each instance.
	$container_id = wp_unique_id( 'spx-neural-map-' );
	$tooltip_id   = $container_id . '-tooltip';

	// Build language-family legend entries for accessibility / non-JS users.
	$legend_items = spx_data_gap_legend_html();

	// Height applied via CSS custom property so the stylesheet (and its
	// responsive @media rules) can control sizing consistently.
	$height_style = esc_attr( (string) $height . 'px' );

	ob_start();
	?>
	<div class="spx-neural-map-wrap">
		<p id="<?php echo esc_attr( $container_id . '-heading' ); ?>" class="spx-neural-map-heading">
			<?php echo esc_html( $heading ); ?>
		</p>
		<p id="<?php echo esc_attr( $container_id . '-subtitle' ); ?>" class="spx-neural-map-subtitle">
			<?php esc_html_e( 'Interactive · Drag to rotate · Click a node', 'sparxstar-data-gap' ); ?>
		</p>

		<div
			id="<?php echo esc_attr( $container_id ); ?>"
			class="spx-neural-map-canvas"
			aria-labelledby="<?php echo esc_attr( $container_id . '-heading' ); ?>"
			aria-describedby="<?php echo esc_attr( $container_id . '-subtitle' ); ?>"
			data-tooltip-id="<?php echo esc_attr( $tooltip_id ); ?>"
			style="--spx-map-height:<?php echo $height_style; ?>"
		></div>

		<div id="<?php echo esc_attr( $tooltip_id ); ?>" class="spx-neural-map-tooltip" role="tooltip" aria-live="polite"></div>

		<div class="spx-neural-map-legend" aria-label="<?php esc_attr_e( 'Language family legend', 'sparxstar-data-gap' ); ?>">
			<?php echo $legend_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside helper. ?>
		</div>

		<p class="spx-neural-map-instructions" aria-hidden="true">
			<?php esc_html_e( 'Drag · Scroll · Click', 'sparxstar-data-gap' ); ?>
		</p>
	</div><!-- .spx-neural-map-wrap -->
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'sparxstar_data_gap', __NAMESPACE__ . '\\spx_data_gap_shortcode' );

// ── Internal helpers ──────────────────────────────────────────────────────────

/**
 * Build escaped HTML for the language-family legend.
 *
 * Colours must match the hex values used in neural-map.js so that the legend
 * and canvas nodes stay in sync.
 *
 * @return string Safe HTML string.
 */
function spx_data_gap_legend_html(): string {
	$families = [
		[ 'Indo-European',      '#4488ff' ],
		[ 'Sino-Tibetan',       '#ff8844' ],
		[ 'Niger-Congo',        '#44ff88' ],
		[ 'Afro-Asiatic',       '#ffcc44' ],
		[ 'Austronesian',       '#ff44cc' ],
		[ 'Dravidian',          '#44ccff' ],
		[ 'Turkic',             '#cc44ff' ],
		[ 'Japonic',            '#ff4444' ],
		[ 'Koreanic',           '#44ffcc' ],
		[ 'Uralic',             '#aaffaa' ],
		[ 'Tai-Kadai',          '#ffaaaa' ],
		[ 'Austro-Asiatic',     '#aaaaff' ],
		[ 'Nilo-Saharan',       '#ffff44' ],
		[ 'Trans-New Guinea',   '#ff8800' ],
		[ 'Na-Dene',            '#88ff44' ],
		[ 'Algic',              '#8844ff' ],
		[ 'Quechuan',           '#ff6688' ],
		[ 'Tupian',             '#66ff88' ],
		[ 'Nakh-Daghestanian',  '#ffcc88' ],
		[ 'Khoisan',            '#ff88ff' ],
	];

	$html = '';
	foreach ( $families as $family ) {
		$name  = esc_html( $family[0] );
		$color = esc_attr( $family[1] );
		$html .= sprintf(
			'<div class="spx-neural-map-legend-item">'
			. '<span class="spx-neural-map-legend-dot" style="background:%s" aria-hidden="true"></span>'
			. '<span>%s</span>'
			. '</div>',
			$color,
			$name
		);
	}
	return $html;
}

// ── Plugin lifecycle ──────────────────────────────────────────────────────────

/**
 * Runs on plugin activation.  No database tables needed by this version.
 *
 * @return void
 */
function spx_data_gap_activate(): void {
	// Future: create any required DB tables here.
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\spx_data_gap_activate' );

/**
 * Runs on plugin deactivation.
 *
 * @return void
 */
function spx_data_gap_deactivate(): void {
	// Future: clean up any plugin data here.
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\spx_data_gap_deactivate' );

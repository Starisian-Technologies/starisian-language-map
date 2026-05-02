<?php
/**
 * Plugin Name:       Sparxstar Data Gap — Neural Language Map
 * Plugin URI:        https://starisian.com/plugins/sparxstar-data-gap
 * Description:       Interactive data-gap visualization: language inter-connectivity and internet presence inequality, embedded via [sparxstar_data_gap].
 * Version:           1.1.0
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * Author:            Starisian Technologies
 * Author URI:        https://starisian.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sparxstar-data-gap
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
	define( 'SPX_DATA_GAP_VERSION', '1.1.0' );
}

// ── Asset registration ────────────────────────────────────────────────────────

/**
 * Register front-end assets.
 *
 * Assets are registered on every request so that page builders and caching
 * plugins can discover them; they are enqueued only after the shortcode
 * renders.
 *
 * @return void
 */
function spx_data_gap_register_assets(): void {
	// Google Fonts — Noto Sans + Noto Sans Mono (used by the visualization UI).
	wp_register_style(
		'spx-noto-sans',
		'https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500&family=Noto+Sans+Mono:wght@400&display=swap',
		[],
		null  // External; version managed by Google.
	);

	// Self-hosted Three.js (r184) — sets window.THREE.
	wp_register_script(
		'spx-three-js',
		SPX_DATA_GAP_URL . 'assets/js/three.min.js',
		[],
		'0.184.0',
		true
	);

	// Visualization script — depends on Three.js being loaded first.
	wp_register_script(
		'spx-neural-map',
		SPX_DATA_GAP_URL . 'assets/js/neural-map.js',
		[ 'spx-three-js' ],
		SPX_DATA_GAP_VERSION,
		true
	);

	// Stylesheet.
	wp_register_style(
		'spx-neural-map',
		SPX_DATA_GAP_URL . 'assets/css/neural-map.css',
		[ 'spx-noto-sans' ],
		SPX_DATA_GAP_VERSION
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\spx_data_gap_register_assets' );

// ── Shortcode ─────────────────────────────────────────────────────────────────

/**
 * Render the [sparxstar_data_gap] shortcode.
 *
 * Accepted attributes:
 *   height – Visualization height in px (default 600, min 300, max 750).
 *
 * Example:
 *   [sparxstar_data_gap height="700"]
 *
 * @param array<string,string>|string $atts Raw shortcode attributes.
 * @return string                           HTML output.
 */
function spx_data_gap_shortcode( $atts ): string {
	// Ensure assets are registered even when the shortcode runs before the
	// wp_enqueue_scripts hook (e.g. page-builder preview contexts).
	spx_data_gap_register_assets();

	// Enqueue assets — safe to call multiple times; WP deduplicates.
	wp_enqueue_style( 'spx-neural-map' );
	wp_enqueue_script( 'spx-neural-map' );

	$atts = shortcode_atts(
		[ 'height' => '750' ],
		$atts,
		'sparxstar_data_gap'
	);

	$height = absint( $atts['height'] );
	if ( $height < 300 ) {
		$height = $height > 0 ? 300 : 750;
	} elseif ( $height > 750 ) {
		$height = 750;
	}

	// Value is already sanitised by esc_attr(); phpcs:ignore used to avoid
	// false-positive on the pre-escaped variable echo below.
	$height_css = esc_attr( $height . 'px' );

	ob_start();
	?>
	<div class="spx-data-gap-wrap" style="--spx-dg-height:<?php echo $height_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- value is esc_attr sanitised above. ?>">

		<div id="wordmark">
			<div id="wm-title">The Data Gap</div>
			<div id="wm-sub">Sovereign Knowledge Network</div>
		</div>

		<div id="view-wrap">
			<button class="vtab active" data-v="net">Language Inter-Connectivity</button>
			<button class="vtab" data-v="globe">Internet Presence</button>
		</div>

		<div id="net-legend" class="vis">
			<div id="nl-title">Cultural relatedness</div>
			<div class="nl-row clickable" data-edge="bantu"><div class="nl-line" style="background:#7F77DD;height:3px;"></div>Bantu language family</div>
			<div class="nl-row clickable" data-edge="trade"><div class="nl-line" style="background:#1D9E75;height:2px;"></div>Trade route lineage</div>
			<div class="nl-row clickable" data-edge="diaspora"><div class="nl-line" style="background:#D85A30;height:2px;"></div>Diaspora / colonial link</div>
			<div class="nl-row clickable" data-edge="distant"><div class="nl-line" style="background:rgba(255,255,255,0.15);height:1px;"></div>Distant relatedness</div>
		</div>

		<div id="globe-legend">
			<div id="gl-title">Content Density &#8212; internet presence</div>
			<div class="gl-row"><div class="gl-dot" style="background:#b2d8ff;"></div>85&#x2013;100 &middot; Sovereign producer</div>
			<div class="gl-row"><div class="gl-dot" style="background:#5599cc;"></div>60&#x2013;85 &middot; Established presence</div>
			<div class="gl-row"><div class="gl-dot" style="background:#cc7700;"></div>35&#x2013;60 &middot; Dependent / emerging</div>
			<div class="gl-row"><div class="gl-dot" style="background:#661111;"></div>0&#x2013;35 &middot; Near invisible online</div>
			<div class="gl-row" style="margin-top:4px;">
				<div style="width:30px;height:2px;background:rgba(100,150,255,0.6);border-radius:1px;"></div>Sovereign data pipe
			</div>
			<div class="gl-row">
				<div style="width:30px;height:1px;background:rgba(100,100,150,0.25);border-radius:1px;"></div>Dependent outflow
			</div>
		</div>

		<div id="info">
			<div id="info-label">What you&#x2019;re seeing</div>
			<div id="info-text">33% of world languages are African. Each node is a language community plotted by cultural relatedness &#8212; not geography. The dense African core shows ancient interconnection invisible on any map.</div>
		</div>

		<div id="stats">
			<div class="stat-card"><div class="stat-num" id="stat-gap">&#8212;</div><div class="stat-lbl">Avg latency gap &middot; Africa</div></div>
			<div class="stat-card"><div class="stat-num" id="stat-content">&#8212;</div><div class="stat-lbl">Avg content density &middot; Africa</div></div>
		</div>

		<button id="spark-btn">Spark sovereignty</button>

		<div id="tip">
			<div id="tip-city"></div>
			<div id="tip-rows">
				<div class="tip-bar"><span class="tip-label">Usage</span><div class="tip-track"><div class="tip-fill" id="tf-usage" style="background:#7F77DD;"></div></div><span id="tv-usage"></span></div>
				<div class="tip-bar"><span class="tip-label">Content</span><div class="tip-track"><div class="tip-fill" id="tf-content" style="background:#1D9E75;"></div></div><span id="tv-content"></span></div>
				<div class="tip-bar"><span class="tip-label">Gap</span><div class="tip-track"><div class="tip-fill" id="tf-gap" style="background:#D85A30;"></div></div><span id="tv-gap"></span></div>
			</div>
		</div>

		<div id="flash"></div>
		<canvas id="c3d"></canvas>

	</div><!-- .spx-data-gap-wrap -->
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'sparxstar_data_gap', __NAMESPACE__ . '\\spx_data_gap_shortcode' );

// ── Plugin lifecycle ──────────────────────────────────────────────────────────

/**
 * Runs on plugin activation.
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

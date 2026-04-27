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
define( 'SPX_DATA_GAP_DIR', plugin_dir_path( __FILE__ ) );

/** Public URL to the plugin root, with trailing slash. */
define( 'SPX_DATA_GAP_URL', plugin_dir_url( __FILE__ ) );

/** Plugin version — bump on every release to bust asset caches. */
define( 'SPX_DATA_GAP_VERSION', '1.0.0' );

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

	$js_suffix  = ( ! $script_debug && file_exists( $js_min_path )  ) ? '.min' : '';
	$css_suffix = ( ! $script_debug && file_exists( $css_min_path ) ) ? '.min' : '';

	// Three.js r128 — self-hosted, no external CDN.
	wp_register_script(
		'spx-three-js',
		SPX_DATA_GAP_URL . 'assets/js/three.min.js',
		[],
		'128',
		true   // Load in footer.
	);

	// Neural map visualization — depends on Three.js.
	wp_register_script(
		'spx-neural-map',
		SPX_DATA_GAP_URL . "assets/js/neural-map{$js_suffix}.js",
		[ 'spx-three-js' ],
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

	// Guard: default height if absint produced 0.
	if ( $height < 200 ) {
		$height = 600;
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
	<div class="spx-neural-map-wrap" style="--spx-map-height:<?php echo $height_style; ?>">
		<p class="spx-neural-map-heading" aria-hidden="true">
			<?php echo esc_html( $heading ); ?>
		</p>
		<p class="spx-neural-map-subtitle" aria-hidden="true">
			<?php esc_html_e( 'Interactive · Drag to rotate · Click a node', 'sparxstar-data-gap' ); ?>
		</p>

		<div
			id="<?php echo esc_attr( $container_id ); ?>"
			class="spx-neural-map-canvas"
			role="img"
			aria-label="<?php echo esc_attr( $heading ); ?>"
			data-tooltip-id="<?php echo esc_attr( $tooltip_id ); ?>"
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

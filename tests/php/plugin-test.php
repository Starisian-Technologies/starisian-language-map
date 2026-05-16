<?php
declare(strict_types=1);

$GLOBALS['state'] = [
	'registered_styles'   => [],
	'registered_scripts'  => [],
	'enqueued_styles'     => [],
	'enqueued_scripts'    => [],
	'actions'             => [],
	'shortcodes'          => [],
	'activation_hooks'    => [],
	'deactivation_hooks'  => [],
	'assertions'          => 0,
];

function spx_test_reset_runtime_state(): void {
	$GLOBALS['state']['registered_styles']  = [];
	$GLOBALS['state']['registered_scripts'] = [];
	$GLOBALS['state']['enqueued_styles']    = [];
	$GLOBALS['state']['enqueued_scripts']   = [];
}

function spx_test_assert_true( bool $condition, string $message ): void {
	$GLOBALS['state']['assertions']++;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function spx_test_assert_same( $expected, $actual, string $message ): void {
	$GLOBALS['state']['assertions']++;
	if ( $expected !== $actual ) {
		throw new RuntimeException(
			$message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true )
		);
	}
}

function spx_test_assert_contains( string $needle, string $haystack, string $message ): void {
	$GLOBALS['state']['assertions']++;
	if ( false === strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . "\nMissing: " . $needle );
	}
}

// Minimal WordPress function stubs.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}

function plugin_dir_path( string $file ): string {
	return dirname( $file ) . '/';
}

function plugin_dir_url( string $file ): string {
	return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}

function wp_register_style( string $handle, string $src, array $deps = [], $ver = false, string $media = 'all' ): void {
	$GLOBALS['state']['registered_styles'][ $handle ] = [
		'src'   => $src,
		'deps'  => $deps,
		'ver'   => $ver,
		'media' => $media,
	];
}

function wp_register_script( string $handle, string $src, array $deps = [], $ver = false, bool $in_footer = false ): void {
	$GLOBALS['state']['registered_scripts'][ $handle ] = [
		'src'       => $src,
		'deps'      => $deps,
		'ver'       => $ver,
		'in_footer' => $in_footer,
	];
}

function wp_enqueue_style( string $handle ): void {
	$GLOBALS['state']['enqueued_styles'][] = $handle;
}

function wp_enqueue_script( string $handle ): void {
	$GLOBALS['state']['enqueued_scripts'][] = $handle;
}

function shortcode_atts( array $pairs, $atts, string $shortcode = '' ): array {
	$atts = is_array( $atts ) ? $atts : [];
	return array_merge( $pairs, $atts );
}

function absint( $maybeint ): int {
	return abs( (int) $maybeint );
}

function esc_attr( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function add_action( string $hook, $callback ): void {
	$GLOBALS['state']['actions'][ $hook ][] = $callback;
}

function add_shortcode( string $tag, $callback ): void {
	$GLOBALS['state']['shortcodes'][ $tag ] = $callback;
}

function register_activation_hook( string $file, $callback ): void {
	$GLOBALS['state']['activation_hooks'][ $file ] = $callback;
}

function register_deactivation_hook( string $file, $callback ): void {
	$GLOBALS['state']['deactivation_hooks'][ $file ] = $callback;
}

try {
	require_once __DIR__ . '/../../wp-content/plugins/sparxstar-data-gap/sparxstar-data-gap.php';

	$namespace = 'Starisian\\Sparxstar\\DataGap\\';

	spx_test_assert_true(
		in_array( $namespace . 'spx_data_gap_register_assets', $GLOBALS['state']['actions']['wp_enqueue_scripts'] ?? [], true ),
		'Expected wp_enqueue_scripts action to register plugin assets callback.'
	);
	spx_test_assert_same(
		$namespace . 'spx_data_gap_shortcode',
		$GLOBALS['state']['shortcodes']['sparxstar_data_gap'] ?? null,
		'Expected sparxstar_data_gap shortcode registration callback.'
	);

	spx_test_reset_runtime_state();
	\Starisian\Sparxstar\DataGap\spx_data_gap_register_assets();

	spx_test_assert_true(
		isset( $GLOBALS['state']['registered_styles']['spx-noto-sans'] ),
		'Expected spx-noto-sans to be registered.'
	);
	spx_test_assert_true(
		isset( $GLOBALS['state']['registered_scripts']['spx-three-js'] ),
		'Expected spx-three-js to be registered.'
	);
	spx_test_assert_true(
		isset( $GLOBALS['state']['registered_scripts']['spx-neural-map'] ),
		'Expected spx-neural-map to be registered.'
	);
	spx_test_assert_same(
		[ 'spx-three-js' ],
		$GLOBALS['state']['registered_scripts']['spx-neural-map']['deps'],
		'Expected spx-neural-map to depend on spx-three-js.'
	);
	spx_test_assert_same(
		'0.184.0',
		$GLOBALS['state']['registered_scripts']['spx-three-js']['ver'],
		'Expected three.js asset version to be pinned.'
	);
	spx_test_assert_same(
		true,
		$GLOBALS['state']['registered_scripts']['spx-three-js']['in_footer'],
		'Expected three.js asset to load in footer.'
	);

	$render_cases = [
		[ 'atts' => [], 'height' => '750px', 'label' => 'default height' ],
		[ 'atts' => [ 'height' => '750' ], 'height' => '750px', 'label' => 'max boundary exact value' ],
		[ 'atts' => [ 'height' => '900' ], 'height' => '750px', 'label' => 'max clamp' ],
		[ 'atts' => [ 'height' => '250' ], 'height' => '300px', 'label' => 'min clamp positive value' ],
		[ 'atts' => [ 'height' => '0' ], 'height' => '750px', 'label' => 'zero height defaults' ],
		[ 'atts' => [ 'height' => 'abc' ], 'height' => '750px', 'label' => 'non-numeric defaults' ],
		[ 'atts' => [ 'height' => '-25' ], 'height' => '300px', 'label' => 'negative coerced then clamped' ],
		[ 'atts' => [ 'height' => '-500' ], 'height' => '500px', 'label' => 'larger negative coerced via absint' ],
		[ 'atts' => [ 'height' => '640' ], 'height' => '640px', 'label' => 'valid custom height' ],
	];

	foreach ( $render_cases as $case ) {
		spx_test_reset_runtime_state();
		$html = \Starisian\Sparxstar\DataGap\spx_data_gap_shortcode( $case['atts'] );

		spx_test_assert_contains(
			'--spx-dg-height:' . $case['height'],
			$html,
			'Expected inline CSS height for case: ' . $case['label']
		);
		spx_test_assert_contains(
			'class="spx-data-gap-wrap"',
			$html,
			'Expected shortcode wrapper markup for case: ' . $case['label']
		);
		spx_test_assert_contains(
			'<canvas id="c3d"></canvas>',
			$html,
			'Expected visualization canvas for case: ' . $case['label']
		);
		spx_test_assert_true(
			in_array( 'spx-neural-map', $GLOBALS['state']['enqueued_styles'], true ),
			'Expected style enqueue for case: ' . $case['label']
		);
		spx_test_assert_true(
			in_array( 'spx-neural-map', $GLOBALS['state']['enqueued_scripts'], true ),
			'Expected script enqueue for case: ' . $case['label']
		);
	}

	\Starisian\Sparxstar\DataGap\spx_data_gap_activate();
	\Starisian\Sparxstar\DataGap\spx_data_gap_deactivate();

	echo "OK (" . $GLOBALS['state']['assertions'] . " assertions)\n";
	exit( 0 );
} catch ( Throwable $e ) {
	fwrite( STDERR, "FAIL: " . $e->getMessage() . "\n" );
	exit( 1 );
}

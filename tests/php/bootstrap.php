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
];

function spx_test_reset_runtime_state(): void {
	$GLOBALS['state']['registered_styles']  = [];
	$GLOBALS['state']['registered_scripts'] = [];
	$GLOBALS['state']['enqueued_styles']    = [];
	$GLOBALS['state']['enqueued_scripts']   = [];
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}

function plugin_dir_path( string $file ): string {
	return dirname( $file ) . '/';
}

function plugin_dir_url( string $file ): string {
	return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}

function wp_register_style( $handle, $src, array $deps = [], $ver = false, string $media = 'all' ): void {
	$GLOBALS['state']['registered_styles'][ (string) $handle ] = [
		'src'   => $src,
		'deps'  => $deps,
		'ver'   => $ver,
		'media' => $media,
	];
}

function wp_register_script( $handle, $src, array $deps = [], $ver = false, bool $in_footer = false ): void {
	$GLOBALS['state']['registered_scripts'][ (string) $handle ] = [
		'src'       => $src,
		'deps'      => $deps,
		'ver'       => $ver,
		'in_footer' => $in_footer,
	];
}

function wp_enqueue_style( $handle ): void {
	$GLOBALS['state']['enqueued_styles'][] = (string) $handle;
}

function wp_enqueue_script( $handle ): void {
	$GLOBALS['state']['enqueued_scripts'][] = (string) $handle;
}

function shortcode_atts( array $pairs, $atts, string $shortcode = '' ): array {
	$atts = is_array( $atts ) ? $atts : [];
	$out  = [];

	foreach ( $pairs as $name => $default ) {
		if ( array_key_exists( $name, $atts ) ) {
			$out[ $name ] = $atts[ $name ];
		} else {
			$out[ $name ] = $default;
		}
	}

	return $out;
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

require_once __DIR__ . '/../../wp-content/plugins/sparxstar-data-gap/sparxstar-data-gap.php';

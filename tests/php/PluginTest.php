<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PluginTest extends TestCase {
	private const NS = 'Starisian\\Sparxstar\\DataGap\\';

	protected function setUp(): void {
		spx_test_reset_runtime_state();
	}

	public function test_plugin_registers_hook_and_shortcode_callbacks(): void {
		self::assertContains(
			self::NS . 'spx_data_gap_register_assets',
			$GLOBALS['state']['actions']['wp_enqueue_scripts'] ?? []
		);
		self::assertSame(
			self::NS . 'spx_data_gap_shortcode',
			$GLOBALS['state']['shortcodes']['sparxstar_data_gap'] ?? null
		);
	}

	public function test_plugin_registers_activation_and_deactivation_hooks(): void {
		$plugin_file = realpath( __DIR__ . '/../../wp-content/plugins/sparxstar-data-gap/sparxstar-data-gap.php' );

		self::assertNotFalse( $plugin_file );
		self::assertArrayHasKey( $plugin_file, $GLOBALS['state']['activation_hooks'] );
		self::assertArrayHasKey( $plugin_file, $GLOBALS['state']['deactivation_hooks'] );
		self::assertSame(
			self::NS . 'spx_data_gap_activate',
			$GLOBALS['state']['activation_hooks'][ $plugin_file ]
		);
		self::assertSame(
			self::NS . 'spx_data_gap_deactivate',
			$GLOBALS['state']['deactivation_hooks'][ $plugin_file ]
		);
	}

	public function test_asset_registration_contracts(): void {
		\Starisian\Sparxstar\DataGap\spx_data_gap_register_assets();

		self::assertArrayHasKey( 'spx-noto-sans', $GLOBALS['state']['registered_styles'] );
		self::assertArrayHasKey( 'spx-three-js', $GLOBALS['state']['registered_scripts'] );
		self::assertArrayHasKey( 'spx-neural-map', $GLOBALS['state']['registered_scripts'] );
		self::assertSame(
			[ 'spx-three-js' ],
			$GLOBALS['state']['registered_scripts']['spx-neural-map']['deps']
		);
		self::assertSame(
			'0.184.0',
			$GLOBALS['state']['registered_scripts']['spx-three-js']['ver']
		);
		self::assertTrue(
			$GLOBALS['state']['registered_scripts']['spx-three-js']['in_footer']
		);
	}

	/**
	 * @dataProvider shortcodeHeightCases
	 *
	 * @param array<string, string> $atts
	 */
	public function test_shortcode_output_and_enqueues(array $atts, string $height, string $label): void {
		$html = \Starisian\Sparxstar\DataGap\spx_data_gap_shortcode( $atts );

		self::assertStringContainsString(
			'--spx-dg-height:' . $height,
			$html,
			'Inline CSS height mismatch for case: ' . $label
		);
		self::assertStringContainsString(
			'class="spx-data-gap-wrap"',
			$html,
			'Missing wrapper for case: ' . $label
		);
		self::assertStringContainsString(
			'<canvas id="c3d"></canvas>',
			$html,
			'Missing canvas for case: ' . $label
		);
		self::assertContains(
			'spx-neural-map',
			$GLOBALS['state']['enqueued_styles'],
			'Expected style enqueue for case: ' . $label
		);
		self::assertContains(
			'spx-neural-map',
			$GLOBALS['state']['enqueued_scripts'],
			'Expected script enqueue for case: ' . $label
		);
	}

	/**
	 * @return array<string, array{0: array<string, string>, 1: string, 2: string}>
	 */
	public function shortcodeHeightCases(): array {
		return [
			'default height'                       => [ [], '750px', 'default height' ],
			'max boundary exact value'            => [ [ 'height' => '750' ], '750px', 'max boundary exact value' ],
			'max clamp'                           => [ [ 'height' => '900' ], '750px', 'max clamp' ],
			'min clamp positive value'            => [ [ 'height' => '250' ], '300px', 'min clamp positive value' ],
			'zero height defaults'                => [ [ 'height' => '0' ], '750px', 'zero height defaults' ],
			'non-numeric defaults'                => [ [ 'height' => 'abc' ], '750px', 'non-numeric defaults' ],
			'negative coerced then clamped'       => [ [ 'height' => '-25' ], '300px', 'negative coerced then clamped' ],
			'larger negative coerced via absint'  => [ [ 'height' => '-500' ], '500px', 'larger negative coerced via absint' ],
			'valid custom height'                 => [ [ 'height' => '640' ], '640px', 'valid custom height' ],
		];
	}
}

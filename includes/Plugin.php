<?php
/**
 * Plugin coordinator.
 *
 * @package GutenbergMotion
 */

declare(strict_types=1);

namespace GutenbergMotion;

use GutenbergMotion\Blocks\AttributeRegistrar;
use GutenbergMotion\Blocks\CompatibilityRegistry;
use GutenbergMotion\Blocks\RenderAnnotator;
use GutenbergMotion\Capabilities\Registry;
use GutenbergMotion\Config\Sanitizer;
use GutenbergMotion\Settings\SettingsPage;

final class Plugin {
	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public static function activate(): void {
		if ( false === get_option( 'gmotion_settings', false ) ) {
			add_option(
				'gmotion_settings',
				array(
					'enabled'        => true,
					'reduced_motion' => 'inherit',
					'mobile_max'     => 767,
					'tablet_max'     => 1024,
					'debug'          => false,
				)
			);
		}
	}

	public static function deactivate(): void {
		// Deliberately retain settings and inert block metadata.
	}

	public function boot(): void {
		$registry  = new CompatibilityRegistry();
		$sanitizer = new Sanitizer();
		$assets    = new Assets();
		$capabilities = new Registry();

		( new AttributeRegistrar( $registry ) )->register();
		( new RenderAnnotator( $registry, $sanitizer, $assets, $capabilities ) )->register();
		add_action(
			'enqueue_block_editor_assets',
			static function () use ( $capabilities ): void {
				wp_add_inline_script( Assets::EDITOR_HANDLE, 'window.KinetivoCapabilities=' . wp_json_encode( $capabilities->public_manifest() ) . ';', 'before' );
			},
			20
		);
		( new SettingsPage() )->register();
		$assets->register();

	}
}

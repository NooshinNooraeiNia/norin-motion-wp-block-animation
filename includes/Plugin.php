<?php
/**
 * Plugin coordinator.
 *
 * @package NorinMotion
 */

declare(strict_types=1);

namespace NorinMotion;

use NorinMotion\Blocks\AttributeRegistrar;
use NorinMotion\Blocks\CompatibilityRegistry;
use NorinMotion\Blocks\RenderAnnotator;
use NorinMotion\Config\Sanitizer;
use NorinMotion\Settings\SettingsPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public static function activate(): void {
		if ( false === get_option( 'norinmotion_settings', false ) ) {
			add_option(
				'norinmotion_settings',
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

		( new AttributeRegistrar( $registry ) )->register();
		( new RenderAnnotator( $registry, $sanitizer, $assets ) )->register();
		( new SettingsPage() )->register();
		$assets->register();

	}
}

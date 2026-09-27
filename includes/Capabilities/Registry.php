<?php
/**
 * Free capabilities and extension boundary for Kinetivo Pro.
 *
 * @package Kinetivo
 */

declare(strict_types=1);

namespace GutenbergMotion\Capabilities;

final class Registry {
	public const FREE_EFFECTS = array(
		'fade-in', 'fade-up', 'fade-down', 'fade-left', 'fade-right', 'fade-scale',
		'slide-up', 'slide-down', 'slide-left', 'slide-right',
		'zoom-in', 'zoom-out', 'scale-up', 'scale-down',
		'rotate-in-left', 'rotate-in-right', 'blur-in', 'flip-x', 'flip-y', 'reveal-top',
	);

	public function is_free_effect( string $preset ): bool {
		return in_array( $preset, self::FREE_EFFECTS, true );
	}

	/** @param array<string, mixed> $config */
	public function is_available( string $preset, array $config ): bool {
		$available = $this->is_free_effect( $preset );
		return (bool) apply_filters( 'kinetivo_effect_available', $available, $preset, $config );
	}

	/** @return array<string, mixed> */
	public function public_manifest(): array {
		return (array) apply_filters(
			'kinetivo_capability_manifest',
			array(
				'tier' => 'free',
				'engine' => 'native',
				'effects' => self::FREE_EFFECTS,
				'features' => array( 'viewport.enter', 'responsive.enabled', 'a11y.reduced-motion' ),
			)
		);
	}
}

<?php
/**
 * Pure, engine-neutral schema migrations.
 *
 * @package Kinetivo
 */

declare(strict_types=1);

namespace GutenbergMotion\Config;

final class Migrator {
	/** @param array<string, mixed> $config @return array<string, mixed> */
	public function migrate( array $config ): array {
		$version = isset( $config['v'] ) ? (int) $config['v'] : 0;
		if ( 2 === $version ) {
			return $config;
		}
		if ( $version > 2 ) {
			return $config;
		}

		if ( 1 === $version ) {
			return $this->from_v1( $config );
		}

		if ( isset( $config['type'] ) || isset( $config['trigger'] ) || isset( $config['timing'] ) ) {
			$timing = isset( $config['timing'] ) && is_array( $config['timing'] ) ? $config['timing'] : array();
			return array(
				'v'       => 2,
				'enabled' => ! empty( $config['enabled'] ),
				'effect'  => array(
					'preset' => 'fade-up',
					'target' => array( 'mode' => 'root', 'selector' => '' ),
					'from'   => array( 'opacity' => 0, 'x' => 0, 'y' => (float) ( $config['distance'] ?? 32 ), 'scale' => 1, 'rotation' => 0, 'blur' => 0 ),
					'to'     => array( 'opacity' => 1, 'x' => 0, 'y' => 0, 'scale' => 1, 'rotation' => 0, 'blur' => 0 ),
				),
				'timing'  => array( 'duration' => $timing['duration'] ?? 0.7, 'delay' => $timing['delay'] ?? 0, 'easing' => $this->ease_token( $timing['ease'] ?? 'power2.out' ), 'iterations' => 1, 'direction' => 'normal', 'stagger' => null ),
				'trigger' => array( 'type' => 'viewport', 'once' => true, 'start' => $config['start'] ?? 'top 85%', 'end' => 'bottom 20%', 'mode' => 'enter', 'smoothing' => 0 ),
				'responsive' => array( 'tablet' => array( 'enabled' => true ), 'mobile' => array( 'enabled' => true ) ),
				'a11y' => array( 'reducedMotion' => 'disable', 'fallback' => 'final-state' ),
				'meta' => array( 'sourcePreset' => 'fade-up', 'migratedFrom' => 0 ),
			);
		}

		return $config;
	}

	/** @param array<string, mixed> $config @return array<string, mixed> */
	private function from_v1( array $config ): array {
		$effect = is_array( $config['effect'] ?? null ) ? $config['effect'] : array();
		$from = is_array( $effect['from'] ?? null ) ? $effect['from'] : array();
		$timing = is_array( $config['timing'] ?? null ) ? $config['timing'] : array();
		$trigger = is_array( $config['trigger'] ?? null ) ? $config['trigger'] : array();
		$scroll = is_array( $trigger['scroll'] ?? null ) ? $trigger['scroll'] : array();
		$responsive = is_array( $config['responsive'] ?? null ) ? $config['responsive'] : array();
		$preset = is_string( $effect['preset'] ?? null ) ? $effect['preset'] : 'fade-up';
		$mode = is_array( $effect['target'] ?? null ) && is_string( $effect['target']['mode'] ?? null ) ? $effect['target']['mode'] : 'root';
		$text = is_array( $effect['text'] ?? null ) ? $effect['text'] : array();

		$result = array(
			'v'       => 2,
			'enabled' => ! empty( $config['enabled'] ),
			'effect'  => array(
				'preset' => $preset,
				'target' => array( 'mode' => $mode, 'selector' => '' ),
				'from'   => array( 'opacity' => 0, 'x' => 0, 'y' => $from['y'] ?? 32, 'scale' => $from['scale'] ?? 1, 'rotation' => $from['rotation'] ?? 0, 'blur' => $from['blur'] ?? 0 ),
				'to'     => array( 'opacity' => 1, 'x' => 0, 'y' => 0, 'scale' => 1, 'rotation' => 0, 'blur' => 0 ),
			),
			'timing'  => array(
				'duration' => $timing['duration'] ?? 0.7,
				'delay' => $timing['delay'] ?? 0,
				'easing' => $this->ease_token( $timing['ease'] ?? 'power2.out' ),
				'iterations' => 1 + max( 0, (int) ( $timing['repeat'] ?? 0 ) ),
				'direction' => ! empty( $timing['yoyo'] ) ? 'alternate' : 'normal',
				'stagger' => $timing['stagger'] ?? null,
			),
			'trigger' => array(
				'type' => $trigger['type'] ?? 'viewport',
				'once' => ! array_key_exists( 'once', $trigger ) || false !== $trigger['once'],
				'start' => $scroll['start'] ?? 'top 85%',
				'end' => $scroll['end'] ?? 'bottom 20%',
				'mode' => false !== ( $scroll['scrub'] ?? false ) ? 'progress' : 'enter',
				'smoothing' => is_numeric( $scroll['scrub'] ?? null ) ? $scroll['scrub'] : 0,
				'pin' => array( 'enabled' => ! empty( $scroll['pin'] ), 'spacing' => ! array_key_exists( 'pinSpacing', $scroll ) || false !== $scroll['pinSpacing'] ),
			),
			'responsive' => array(
				'tablet' => is_array( $responsive['tablet'] ?? null ) ? $responsive['tablet'] : array(),
				'mobile' => is_array( $responsive['mobile'] ?? null ) ? $responsive['mobile'] : array(),
			),
			'a11y' => is_array( $config['a11y'] ?? null ) ? $config['a11y'] : array( 'reducedMotion' => 'disable', 'fallback' => 'final-state' ),
			'meta' => array( 'sourcePreset' => $preset, 'migratedFrom' => 1 ),
		);

		if ( str_starts_with( $preset, 'text-' ) ) {
			$result['effect']['split'] = array( 'unit' => $text['unit'] ?? 'words', 'mask' => $text['mask'] ?? 'none', 'responsive' => ! array_key_exists( 'autoSplit', $text ) || false !== $text['autoSplit'] );
		}
		return $result;
	}

	private function ease_token( mixed $ease ): string {
		return match ( is_string( $ease ) ? $ease : '' ) {
			'none' => 'linear',
			'power1.out', 'sine.out' => 'gentle',
			'power3.out', 'power4.out', 'expo.out', 'circ.out' => 'emphasized',
			'back.out', 'bounce.out', 'elastic.out' => 'expressive',
			default => 'standard',
		};
	}
}

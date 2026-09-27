<?php
/**
 * Engine-neutral schema validation and normalization.
 *
 * @package Kinetivo
 */

declare(strict_types=1);

namespace GutenbergMotion\Config;

final class Sanitizer {
	private const MAX_BYTES = 24576;
	private const EASINGS = array( 'linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out', 'gentle', 'standard', 'emphasized', 'expressive' );
	private const TARGETS = array( 'root', 'children', 'media', 'text' );
	private const TRIGGERS = array( 'viewport', 'load', 'hover', 'focus', 'pointer', 'click', 'timeline' );
	private const PRESETS = array(
		'fade-in', 'fade-up', 'fade-down', 'fade-left', 'fade-right', 'fade-scale',
		'slide-up', 'slide-down', 'slide-left', 'slide-right', 'zoom-in', 'zoom-out',
		'scale-up', 'scale-down', 'rotate-in-left', 'rotate-in-right', 'blur-in', 'flip-x', 'flip-y', 'reveal-top',
		'reveal-bottom', 'reveal-left', 'reveal-right', 'text-lines-up', 'text-lines-fade', 'text-lines-mask',
		'text-words-up', 'text-words-fade', 'text-words-scale', 'text-words-rotate', 'text-words-slide', 'text-words-mask',
		'text-chars-up', 'text-chars-fade', 'text-chars-rotate', 'text-chars-scale', 'text-chars-flip', 'text-chars-wave', 'text-chars-random', 'text-blur-in',
		'media-zoom-in', 'media-zoom-out', 'media-pan-left', 'media-pan-right', 'media-reveal-left', 'media-reveal-right',
		'cascade-up', 'cascade-fade', 'cascade-scale', 'grid-wave', 'grid-random', 'hover-lift', 'hover-grow', 'hover-tilt', 'hover-glow', 'pulse-soft',
	);

	public function __construct( private ?Migrator $migrator = null ) {
		$this->migrator ??= new Migrator();
	}

	/** @return array<string, mixed>|null */
	public function normalize( mixed $raw ): ?array {
		if ( ! is_array( $raw ) || array() === $raw || $this->has_dangerous_key( $raw ) ) return null;
		$encoded = function_exists( 'wp_json_encode' ) ? wp_json_encode( $raw ) : json_encode( $raw );
		if ( ! is_string( $encoded ) || strlen( $encoded ) > self::MAX_BYTES ) return null;

		$config = $this->migrator->migrate( $raw );
		if ( 2 !== (int) ( $config['v'] ?? 0 ) ) return null;
		$effect = is_array( $config['effect'] ?? null ) ? $config['effect'] : array();
		$from = is_array( $effect['from'] ?? null ) ? $effect['from'] : array();
		$to = is_array( $effect['to'] ?? null ) ? $effect['to'] : array();
		$target = is_array( $effect['target'] ?? null ) ? $effect['target'] : array();
		$timing = is_array( $config['timing'] ?? null ) ? $config['timing'] : array();
		$trigger = is_array( $config['trigger'] ?? null ) ? $config['trigger'] : array();
		$pin = is_array( $trigger['pin'] ?? null ) ? $trigger['pin'] : array();
		$responsive = is_array( $config['responsive'] ?? null ) ? $config['responsive'] : array();
		$a11y = is_array( $config['a11y'] ?? null ) ? $config['a11y'] : array();
		$preset = is_string( $effect['preset'] ?? null ) && in_array( $effect['preset'], self::PRESETS, true ) ? $effect['preset'] : 'fade-up';
		$target_mode = is_string( $target['mode'] ?? null ) && in_array( $target['mode'], self::TARGETS, true ) ? $target['mode'] : 'root';
		$stagger = is_array( $timing['stagger'] ?? null ) ? $timing['stagger'] : null;

		$normalized = array(
			'v' => 2,
			'enabled' => ! empty( $config['enabled'] ),
			'effect' => array(
				'preset' => $preset,
				'target' => array( 'mode' => $target_mode, 'selector' => '' ),
				'from' => $this->state( $from, $this->effect_defaults( $preset ) ),
				'to' => $this->state( $to, array( 'opacity' => 1, 'x' => 0, 'y' => 0, 'scale' => 1, 'rotation' => 0, 'blur' => 0 ) ),
			),
			'timing' => array(
				'duration' => $this->number( $timing['duration'] ?? 0.7, 0.1, 10, 0.7 ),
				'delay' => $this->number( $timing['delay'] ?? 0, 0, 10, 0 ),
				'easing' => is_string( $timing['easing'] ?? null ) && in_array( $timing['easing'], self::EASINGS, true ) ? $timing['easing'] : 'ease-out',
				'iterations' => (int) $this->number( $timing['iterations'] ?? 1, 1, 21, 1 ),
				'direction' => 'alternate' === ( $timing['direction'] ?? '' ) ? 'alternate' : 'normal',
				'stagger' => null === $stagger ? null : array(
					'each' => $this->number( $stagger['each'] ?? 0.1, 0, 2, 0.1 ),
					'from' => is_string( $stagger['from'] ?? null ) && in_array( $stagger['from'], array( 'start', 'end', 'center', 'edges', 'random' ), true ) ? $stagger['from'] : 'start',
				),
			),
			'trigger' => array(
				'type' => is_string( $trigger['type'] ?? null ) && in_array( $trigger['type'], self::TRIGGERS, true ) ? $trigger['type'] : 'viewport',
				'once' => ! array_key_exists( 'once', $trigger ) || false !== $trigger['once'],
				'start' => $this->position( $trigger['start'] ?? 'top 85%', 'top 85%' ),
				'end' => $this->position( $trigger['end'] ?? 'bottom 20%', 'bottom 20%' ),
				'mode' => 'progress' === ( $trigger['mode'] ?? '' ) ? 'progress' : 'enter',
				'smoothing' => $this->number( $trigger['smoothing'] ?? 0, 0, 5, 0 ),
				'pin' => array(
					'enabled' => ! empty( $pin['enabled'] ),
					'spacing' => ! isset( $pin['spacing'] ) || false !== $pin['spacing'],
				),
			),
			'responsive' => array(
				'tablet' => $this->responsive_override( $responsive['tablet'] ?? array() ),
				'mobile' => $this->responsive_override( $responsive['mobile'] ?? array() ),
			),
			'a11y' => array(
				'reducedMotion' => 'simplify' === ( $a11y['reducedMotion'] ?? '' ) ? 'simplify' : 'disable',
				'fallback' => 'final-state',
			),
			'requirements' => $this->requirements( $preset, $target_mode, $trigger ),
			'meta' => array( 'sourcePreset' => $preset, 'migratedFrom' => (int) ( $config['meta']['migratedFrom'] ?? 2 ) ),
		);

		if ( str_starts_with( $preset, 'text-' ) ) {
			$split = is_array( $effect['split'] ?? null ) ? $effect['split'] : array();
			$unit = str_starts_with( $preset, 'text-lines-' ) ? 'lines' : ( str_starts_with( $preset, 'text-chars-' ) ? 'chars' : 'words' );
			$normalized['effect']['split'] = array( 'unit' => $unit, 'mask' => str_ends_with( $preset, '-mask' ) ? $unit : 'none', 'responsive' => ! isset( $split['responsive'] ) || false !== $split['responsive'] );
		}

		if ( isset( $config['animations'] ) && is_array( $config['animations'] ) ) {
			$normalized['animations'] = array();
			foreach ( array_slice( $config['animations'], 0, 4 ) as $entry ) {
				if ( ! is_array( $entry ) || 2 !== ( $entry['v'] ?? null ) ) continue;
				unset( $entry['animations'] );
				$animation = $this->normalize( $entry );
				if ( null === $animation ) continue;
				$normalized['animations'][] = $animation;
				if ( $animation['enabled'] ) $normalized['requirements'] = array_values( array_unique( array_merge( $normalized['requirements'], $animation['requirements'] ) ) );
			}
		}

		return $normalized;
	}

	/** Starting values for sparse saved presets; explicit authored values still win. */
	private function effect_defaults( string $preset ): array {
		$state = array( 'opacity' => 0, 'x' => 0, 'y' => 0, 'scale' => 1, 'rotation' => 0, 'blur' => 0 );
		if ( preg_match( '/^(?:text-|media-pan-|cascade-up|grid-|hover-lift)/', $preset ) ) $state['y'] = 32;
		if ( in_array( $preset, array( 'fade-up', 'slide-up' ), true ) ) $state['y'] = 32;
		if ( in_array( $preset, array( 'fade-down', 'slide-down' ), true ) ) $state['y'] = -32;
		if ( in_array( $preset, array( 'fade-left', 'slide-left' ), true ) ) $state['x'] = -32;
		if ( in_array( $preset, array( 'fade-right', 'slide-right' ), true ) ) $state['x'] = 32;
		if ( str_starts_with( $preset, 'slide-' ) ) $state['opacity'] = 1;
		if ( in_array( $preset, array( 'fade-scale', 'zoom-in', 'scale-up', 'text-words-scale', 'text-chars-scale', 'cascade-scale', 'grid-random', 'media-zoom-in' ), true ) ) $state['scale'] = 0.82;
		if ( in_array( $preset, array( 'zoom-out', 'scale-down', 'media-zoom-out' ), true ) ) $state['scale'] = 1.18;
		if ( str_contains( $preset, 'rotate' ) || 'text-chars-random' === $preset ) $state['rotation'] = 'rotate-in-left' === $preset ? -12 : 12;
		if ( in_array( $preset, array( 'blur-in', 'text-blur-in' ), true ) ) $state['blur'] = 12;
		return $state;
	}

	/** @param array<string, mixed> $state @param array<string, int|float> $defaults @return array<string, int|float> */
	private function state( array $state, array $defaults ): array {
		return array(
			'opacity' => $this->number( $state['opacity'] ?? $defaults['opacity'], 0, 1, $defaults['opacity'] ),
			'x' => $this->number( $state['x'] ?? $defaults['x'], -300, 300, $defaults['x'] ),
			'y' => $this->number( $state['y'] ?? $defaults['y'], -300, 300, $defaults['y'] ),
			'scale' => $this->number( $state['scale'] ?? $defaults['scale'], 0.1, 3, $defaults['scale'] ),
			'rotation' => $this->number( $state['rotation'] ?? $defaults['rotation'], -180, 180, $defaults['rotation'] ),
			'blur' => $this->number( $state['blur'] ?? $defaults['blur'], 0, 50, $defaults['blur'] ),
		);
	}

	/** @return array<string, mixed> */
	private function responsive_override( mixed $value ): array {
		if ( ! is_array( $value ) ) return array();
		$out = array();
		if ( array_key_exists( 'enabled', $value ) ) $out['enabled'] = false !== $value['enabled'];
		if ( isset( $value['effect']['from'] ) && is_array( $value['effect']['from'] ) ) {
			$state = $this->sparse_state( $value['effect']['from'] );
			if ( array() !== $state ) $out['effect'] = array( 'from' => $state );
		}
		if ( isset( $value['timing'] ) && is_array( $value['timing'] ) ) {
			$timing = array();
			if ( isset( $value['timing']['duration'] ) && ( is_int( $value['timing']['duration'] ) || is_float( $value['timing']['duration'] ) ) && is_finite( (float) $value['timing']['duration'] ) ) $timing['duration'] = $this->number( $value['timing']['duration'], 0.1, 10, 0.7 );
			if ( isset( $value['timing']['delay'] ) && ( is_int( $value['timing']['delay'] ) || is_float( $value['timing']['delay'] ) ) && is_finite( (float) $value['timing']['delay'] ) ) $timing['delay'] = $this->number( $value['timing']['delay'], 0, 10, 0 );
			if ( isset( $value['timing']['easing'] ) && is_string( $value['timing']['easing'] ) && in_array( $value['timing']['easing'], self::EASINGS, true ) ) $timing['easing'] = $value['timing']['easing'];
			if ( array() !== $timing ) $out['timing'] = $timing;
		}
		return $out;
	}

	/** @param array<string, mixed> $state @return array<string, int|float> */
	private function sparse_state( array $state ): array {
		$limits = array( 'opacity' => array( 0, 1 ), 'x' => array( -300, 300 ), 'y' => array( -300, 300 ), 'scale' => array( 0.1, 3 ), 'rotation' => array( -180, 180 ), 'blur' => array( 0, 50 ) );
		$out = array();
		foreach ( $limits as $key => $range ) {
			if ( ! array_key_exists( $key, $state ) || ( ! is_int( $state[ $key ] ) && ! is_float( $state[ $key ] ) ) || ! is_finite( (float) $state[ $key ] ) ) continue;
			$out[ $key ] = max( $range[0], min( $range[1], (float) $state[ $key ] ) );
		}
		return $out;
	}

	/** @return list<string> */
	private function requirements( string $preset, string $target, array $trigger ): array {
		$requirements = array();
		if ( str_starts_with( $preset, 'text-' ) || 'text' === $target ) $requirements[] = 'text.split';
		if ( 'progress' === ( $trigger['mode'] ?? '' ) ) $requirements[] = 'scroll.progress';
		$pin = is_array( $trigger['pin'] ?? null ) ? $trigger['pin'] : array();
		if ( ! empty( $pin['enabled'] ) ) $requirements[] = 'scroll.pin';
		if ( in_array( $trigger['type'] ?? '', array( 'hover', 'focus', 'pointer', 'click', 'timeline' ), true ) ) $requirements[] = 'interaction.' . $trigger['type'];
		return array_values( array_unique( $requirements ) );
	}

	private function position( mixed $value, string $fallback ): string {
		return is_string( $value ) && preg_match( '/^(?:top|bottom) (?:[1-9]|[1-9][0-9]|100)%$/', $value ) ? $value : $fallback;
	}

	private function number( mixed $value, float $min, float $max, float $fallback ): float|int {
		if ( ! is_int( $value ) && ! is_float( $value ) ) return $fallback;
		$value = (float) $value;
		if ( ! is_finite( $value ) ) return $fallback;
		return max( $min, min( $max, $value ) );
	}

	/** @param array<mixed> $value */
	private function has_dangerous_key( array $value, int $depth = 0 ): bool {
		if ( $depth > 12 ) return true;
		foreach ( $value as $key => $child ) {
			if ( is_string( $key ) && in_array( strtolower( $key ), array( '__proto__', 'prototype', 'constructor' ), true ) ) return true;
			if ( is_array( $child ) && $this->has_dangerous_key( $child, $depth + 1 ) ) return true;
		}
		return false;
	}
}

<?php
/**
 * Safely annotates the verified root tag without wrappers.
 *
 * @package GutenbergMotion
 */

declare(strict_types=1);

namespace GutenbergMotion\Blocks;

use GutenbergMotion\Assets;
use GutenbergMotion\Capabilities\Registry;
use GutenbergMotion\Config\Sanitizer;
use GutenbergMotion\Settings\SettingsPage;

final class RenderAnnotator {
	private int $instance = 0;

	public function __construct(
		private CompatibilityRegistry $registry,
		private Sanitizer $sanitizer,
		private Assets $assets,
		private ?Registry $capabilities = null
	) {}

	public function register(): void {
		add_filter( 'render_block', array( $this, 'annotate' ), 10, 2 );
	}

	/**
	 * @param string               $html  Rendered block HTML.
	 * @param array<string, mixed> $block Parsed block.
	 */
	public function annotate( string $html, array $block ): string {
		// Syndication and API consumers need the original semantic content only.
		if ( is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( is_admin() && ! wp_doing_ajax() ) ) {
			return $html;
		}
		$name     = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';
		$settings = SettingsPage::get_settings();
		if ( ! $settings['enabled'] || ! $this->registry->supports( $name ) || '' === trim( $html ) ) {
			return $html;
		}

		$raw    = $block['attrs']['gmotion'] ?? null;
		$config = $this->sanitizer->normalize( $raw );
		if ( null === $config || empty( $config['enabled'] ) ) {
			return $html;
		}

		$config = apply_filters( 'gmotion_animation_config', $config, $block );
		$config = apply_filters( 'kinetivo_animation_config', $config, $block );
		$config = $this->sanitizer->normalize( $config );
		if ( null === $config || empty( $config['enabled'] ) ) {
			return $html;
		}
		$this->capabilities ??= new Registry();
		$preset = (string) $config['effect']['preset'];
		if ( ! $this->capabilities->is_available( $preset, $config ) ) {
			return $html;
		}
		$adapter = $this->registry->adapter( $name );
		$targets = is_array( $adapter ) && isset( $adapter['targets'] ) && is_array( $adapter['targets'] ) ? $adapter['targets'] : array( 'root' );
		if ( ! in_array( $config['effect']['target']['mode'], $targets, true ) ) {
			$config['effect']['target']['mode'] = 'root';
			$config['timing']['stagger'] = null;
		}

		foreach ( $config['animations'] ?? array() as $index => $animation ) {
			if ( ! in_array( $animation['effect']['target']['mode'], $targets, true ) ) {
				$config['animations'][ $index ]['effect']['target']['mode'] = 'root';
				$config['animations'][ $index ]['timing']['stagger'] = null;
			}
		}

		$processor = new \WP_HTML_Tag_Processor( $html );
		if ( ! $processor->next_tag() ) {
			return $html;
		}

		++$this->instance;
		$key = 'gm-' . $this->instance;
		do_action( 'gmotion_before_annotate_block', $block, $key );
		$processor->add_class( 'has-gmotion' );
		$processor->set_attribute( 'data-gmotion', (string) wp_json_encode( $config ) );
		$processor->set_attribute( 'data-gmotion-key', $key );
		$processor->set_attribute( 'data-gmotion-profile', is_array( $adapter ) ? (string) ( $adapter['profile'] ?? 'root' ) : 'root' );
		$annotated = $processor->get_updated_html();
		$this->assets->enqueue_runtime();
		do_action( 'kinetivo_enqueue_effect_assets', $preset, $config, $block );
		do_action( 'gmotion_after_annotate_block', $annotated, $block, $key );

		return (string) apply_filters( 'gmotion_render_attributes', $annotated, $block, $config );
	}
}

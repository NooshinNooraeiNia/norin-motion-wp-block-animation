<?php
/**
 * Safely annotates the verified root tag without wrappers.
 *
 * @package NorinMotion
 */

declare(strict_types=1);

namespace NorinMotion\Blocks;

use NorinMotion\Assets;
use NorinMotion\Config\Sanitizer;
use NorinMotion\Settings\SettingsPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RenderAnnotator {
	private int $instance = 0;

	public function __construct(
		private CompatibilityRegistry $registry,
		private Sanitizer $sanitizer,
		private Assets $assets
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

		$raw    = $block['attrs']['norinmotion'] ?? null;
		$config = $this->sanitizer->normalize( $raw );
		if ( null === $config || empty( $config['enabled'] ) ) {
			return $html;
		}

		$config = apply_filters( 'norinmotion_animation_config', $config, $block );
		$config = $this->sanitizer->normalize( $config );
		if ( null === $config || empty( $config['enabled'] ) ) {
			return $html;
		}

		$processor = new \WP_HTML_Tag_Processor( $html );
		if ( ! $processor->next_tag() ) {
			return $html;
		}

		++$this->instance;
		$key = 'norinmotion-' . $this->instance;
		$processor->add_class( 'has-norinmotion' );
		$processor->set_attribute( 'data-norinmotion', (string) wp_json_encode( $config ) );
		$processor->set_attribute( 'data-norinmotion-key', $key );
		$annotated = $processor->get_updated_html();
		$this->assets->enqueue_runtime();

		return $annotated;
	}
}

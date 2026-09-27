<?php
/**
 * Supported block capability registry.
 *
 * @package GutenbergMotion
 */

declare(strict_types=1);

namespace GutenbergMotion\Blocks;

final class CompatibilityRegistry {
	/** @var array<string, array<string, mixed>> */
	private array $blocks = array(
		'core/heading'    => array( 'profile' => 'text', 'targets' => array( 'root', 'text' ) ),
		'core/paragraph'  => array( 'profile' => 'text', 'targets' => array( 'root', 'text' ) ),
		'core/quote'      => array( 'profile' => 'text', 'targets' => array( 'root', 'text' ) ),
		'core/list'       => array( 'profile' => 'text-container', 'targets' => array( 'root', 'text', 'children' ) ),
		'core/image'      => array( 'profile' => 'media', 'targets' => array( 'root', 'media' ) ),
		'core/cover'      => array( 'profile' => 'media-container', 'targets' => array( 'root', 'media', 'children' ) ),
		'core/media-text' => array( 'profile' => 'media-container', 'targets' => array( 'root', 'media', 'children' ) ),
		'core/gallery'    => array( 'profile' => 'gallery', 'targets' => array( 'root', 'media', 'children' ) ),
		'core/group'      => array( 'profile' => 'container', 'targets' => array( 'root', 'children' ) ),
		'core/columns'    => array( 'profile' => 'container', 'targets' => array( 'root', 'children' ) ),
		'core/buttons'    => array( 'profile' => 'container', 'targets' => array( 'root', 'children' ) ),
		'core/button'     => array( 'profile' => 'interactive', 'targets' => array( 'root' ) ),
	);

	public function supports( string $block_name ): bool {
		$supported = isset( $this->blocks[ $block_name ] );
		return (bool) apply_filters( 'gmotion_supported_block', $supported, $block_name, $this->blocks[ $block_name ] ?? null );
	}

	/** @return array<string, array<string, mixed>> */
	public function all(): array {
		return $this->blocks;
	}

	/** @return array<string, mixed>|null */
	public function adapter( string $block_name ): ?array {
		$adapter = $this->blocks[ $block_name ] ?? null;
		return apply_filters( 'gmotion_block_adapter', $adapter, $block_name );
	}
}

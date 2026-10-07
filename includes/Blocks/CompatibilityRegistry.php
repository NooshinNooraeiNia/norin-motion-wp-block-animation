<?php
/**
 * Supported block types.
 *
 * @package NorinMotion
 */

declare(strict_types=1);

namespace NorinMotion\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CompatibilityRegistry {
	/** @var list<string> */
	private array $blocks = array(
		'core/heading',
		'core/paragraph',
		'core/quote',
		'core/list',
		'core/image',
		'core/cover',
		'core/media-text',
		'core/gallery',
		'core/group',
		'core/columns',
		'core/buttons',
		'core/button',
	);

	public function supports( string $block_name ): bool {
		return in_array( $block_name, $this->blocks, true );
	}

	/** @return list<string> */
	public function all(): array {
		return $this->blocks;
	}
}

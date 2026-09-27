<?php
/**
 * Registers the portable block attribute on both static and dynamic blocks.
 *
 * @package GutenbergMotion
 */

declare(strict_types=1);

namespace GutenbergMotion\Blocks;

final class AttributeRegistrar {
	public function __construct( private CompatibilityRegistry $registry ) {}

	public function register(): void {
		add_filter( 'register_block_type_args', array( $this, 'filter_args' ), 10, 2 );
	}

	/**
	 * @param array<string, mixed> $args Block type arguments.
	 * @return array<string, mixed>
	 */
	public function filter_args( array $args, string $block_name ): array {
		if ( ! $this->registry->supports( $block_name ) ) {
			return $args;
		}

		$args['attributes']              = isset( $args['attributes'] ) && is_array( $args['attributes'] ) ? $args['attributes'] : array();
		$args['attributes']['gmotion'] = array( 'type' => 'object' );
		return $args;
	}
}

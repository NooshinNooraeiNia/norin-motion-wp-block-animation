<?php
/**
 * Canonical schema defaults.
 *
 * @package GutenbergMotion
 */

declare(strict_types=1);

namespace GutenbergMotion\Config;

final class Schema {
	/** @return array<string, mixed> */
	public static function defaults(): array {
		return ( new Sanitizer() )->normalize( array( 'v' => 1, 'enabled' => true ) ) ?? array();
	}
}

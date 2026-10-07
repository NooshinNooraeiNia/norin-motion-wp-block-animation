<?php
/**
 * Plugin Name:       Norin Motion - Block Animation
 * Description:       Bring your pages to life with 20 animation effects across 12 Gutenberg block types. Create smooth fades, slides, zooms, flips and reveals with editor previews, responsive controls and built-in reduced-motion support. No coding required.
 * Version:           1.1.1
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            Nora Nia
 * Author URI:        https://profiles.wordpress.org/norania/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       norinmotion
 * Domain Path:       /languages
 *
 * @package NorinMotion
 */

declare(strict_types=1);

namespace NorinMotion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NORINMOTION_VERSION', '1.1.1' );
define( 'NORINMOTION_FILE', __FILE__ );
define( 'NORINMOTION_PATH', plugin_dir_path( __FILE__ ) );
define( 'NORINMOTION_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = __NAMESPACE__ . '\\';
		if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) {
			return;
		}

		$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
		$file     = NORINMOTION_PATH . 'includes/' . $relative . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

Plugin::instance()->boot();

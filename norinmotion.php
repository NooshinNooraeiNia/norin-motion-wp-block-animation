<?php
/**
 * Plugin Name:       Norin Motion - Block Animation
 * Description:       Bring your pages to life with 20 animation effects across 12 Gutenberg block types. Create smooth fades, slides, zooms, flips and reveals with editor previews, responsive controls and built-in reduced-motion support. No coding required.
 * Version:           1.1.0
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            Nora Nia
 * Author URI:        https://profiles.wordpress.org/norania/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       norinmotion
 * Domain Path:       /languages
 *
 * @package GutenbergMotion
 */

declare(strict_types=1);

namespace GutenbergMotion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GMOTION_VERSION', '1.1.0' );
define( 'GMOTION_FILE', __FILE__ );
define( 'GMOTION_PATH', plugin_dir_path( __FILE__ ) );
define( 'GMOTION_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = __NAMESPACE__ . '\\';
		if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) {
			return;
		}

		$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
		$file     = GMOTION_PATH . 'includes/' . $relative . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

Plugin::instance()->boot();

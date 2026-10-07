<?php
/**
 * Plugin uninstall cleanup. Block attributes remain in content by design.
 *
 * @package NorinMotion
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'norinmotion_settings' );

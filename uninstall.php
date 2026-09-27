<?php
/**
 * Plugin uninstall cleanup. Block attributes remain in content by design.
 *
 * @package GutenbergMotion
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'gmotion_settings' );

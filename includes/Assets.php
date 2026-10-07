<?php
/**
 * Selective asset registration and loading.
 *
 * @package NorinMotion
 */

declare(strict_types=1);

namespace NorinMotion;

use NorinMotion\Settings\SettingsPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {
	public const RUNTIME_HANDLE = 'norinmotion-runtime';
	public const EDITOR_HANDLE  = 'norinmotion-editor';
	public const STYLE_HANDLE   = 'norinmotion-runtime';

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_runtime' ), 5 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor' ) );
	}

	public function register_runtime(): void {
		$style_path = NORINMOTION_PATH . 'build/style.css';
		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			wp_register_style( self::STYLE_HANDLE, NORINMOTION_URL . 'build/style.css', array(), is_readable( $style_path ) ? (string) filemtime( $style_path ) : NORINMOTION_VERSION );
		}

		if ( ! wp_script_is( self::RUNTIME_HANDLE, 'registered' ) ) {
			$path = NORINMOTION_PATH . 'build/runtime.js';
			wp_register_script(
				self::RUNTIME_HANDLE,
				NORINMOTION_URL . 'build/runtime.js',
				array(),
				is_readable( $path ) ? (string) filemtime( $path ) : NORINMOTION_VERSION,
				true
			);
		}
	}

	public function enqueue_runtime(): void {
		$this->register_runtime();
		if ( wp_script_is( self::RUNTIME_HANDLE, 'enqueued' ) ) {
			return;
		}

		$settings = SettingsPage::get_settings();
		wp_add_inline_script(
			self::RUNTIME_HANDLE,
			'window.NorinMotionSettings=' . wp_json_encode(
				array(
					'mobileQuery'   => '(max-width: ' . (int) $settings['mobile_max'] . 'px)',
					'tabletQuery'   => '(max-width: ' . (int) $settings['tablet_max'] . 'px)',
					'reducedPolicy' => $settings['reduced_motion'],
					'debug'         => $settings['debug'],
				)
			) . ';',
			'before'
		);
		wp_enqueue_script( self::RUNTIME_HANDLE );
		wp_enqueue_style( self::STYLE_HANDLE );
	}

	public function enqueue_editor(): void {
		$style_path = NORINMOTION_PATH . 'build/editor.css';
		wp_enqueue_style(
			'norinmotion-editor',
			NORINMOTION_URL . 'build/editor.css',
			array( 'wp-components' ),
			is_readable( $style_path ) ? (string) filemtime( $style_path ) : NORINMOTION_VERSION
		);
		$path = NORINMOTION_PATH . 'build/editor.js';
		wp_enqueue_script(
			self::EDITOR_HANDLE,
			NORINMOTION_URL . 'build/editor.js',
			array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-element', 'wp-hooks', 'wp-i18n' ),
			is_readable( $path ) ? (string) filemtime( $path ) : NORINMOTION_VERSION,
			true
		);
		wp_set_script_translations( self::EDITOR_HANDLE, 'norinmotion', NORINMOTION_PATH . 'languages' );
	}
}

<?php
/**
 * Site-wide motion and accessibility settings.
 *
 * @package GutenbergMotion
 */

declare(strict_types=1);

namespace GutenbergMotion\Settings;

final class SettingsPage {
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
	}

	/** @return array{enabled: bool, reduced_motion: string, mobile_max: int, tablet_max: int, debug: bool} */
	public static function get_settings(): array {
		$value = get_option( 'gmotion_settings', array() );
		$value = is_array( $value ) ? $value : array();
		return array(
			'enabled' => ! array_key_exists( 'enabled', $value ) || ! empty( $value['enabled'] ),
			'reduced_motion' => isset( $value['reduced_motion'] ) && in_array( $value['reduced_motion'], array( 'inherit', 'disable', 'simplify' ), true ) ? $value['reduced_motion'] : 'inherit',
			'mobile_max' => isset( $value['mobile_max'] ) ? max( 320, min( 1200, (int) $value['mobile_max'] ) ) : 767,
			'tablet_max' => isset( $value['tablet_max'] ) ? max( 600, min( 1600, (int) $value['tablet_max'] ) ) : 1024,
			'debug' => ! empty( $value['debug'] ),
		);
	}

	public function menu(): void {
		add_options_page( __( 'Norin Motion - Block Animation', 'norinmotion' ), __( 'Norin Motion - Block Animation', 'norinmotion' ), 'manage_options', 'kinetivo', array( $this, 'render' ) );
	}

	public function settings(): void {
		register_setting( 'gmotion', 'gmotion_settings', array( 'sanitize_callback' => array( $this, 'sanitize' ) ) );
	}

	/** @param mixed $value @return array{enabled: bool, reduced_motion: string, mobile_max: int, tablet_max: int, debug: bool} */
	public function sanitize( mixed $value ): array {
		$value = is_array( $value ) ? $value : array();
		return array(
			'enabled' => ! empty( $value['enabled'] ),
			'reduced_motion' => isset( $value['reduced_motion'] ) && in_array( $value['reduced_motion'], array( 'inherit', 'disable', 'simplify' ), true ) ? $value['reduced_motion'] : 'inherit',
			'mobile_max' => max( 320, min( 1200, (int) ( $value['mobile_max'] ?? 767 ) ) ),
			'tablet_max' => max( 600, min( 1600, (int) ( $value['tablet_max'] ?? 1024 ) ) ),
			'debug' => ! empty( $value['debug'] ),
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = self::get_settings();
		?>
		<div class="wrap"><h1><?php esc_html_e( 'Norin Motion - Block Animation', 'norinmotion' ); ?></h1>
		<p><?php esc_html_e( 'Site-wide motion and accessibility policies.', 'norinmotion' ); ?></p>
		<form action="options.php" method="post"><?php settings_fields( 'gmotion' ); ?>
		<table class="form-table"><tbody>
		<tr><th scope="row"><?php esc_html_e( 'Frontend motion', 'norinmotion' ); ?></th><td><label><input type="checkbox" name="gmotion_settings[enabled]" value="1" <?php checked( $s['enabled'] ); ?>> <?php esc_html_e( 'Enable configured animations', 'norinmotion' ); ?></label></td></tr>
		<tr><th scope="row"><label for="gmotion-reduced"><?php esc_html_e( 'Reduced motion', 'norinmotion' ); ?></label></th><td><select id="gmotion-reduced" name="gmotion_settings[reduced_motion]"><option value="inherit" <?php selected( $s['reduced_motion'], 'inherit' ); ?>><?php esc_html_e( 'Use each block setting', 'norinmotion' ); ?></option><option value="disable" <?php selected( $s['reduced_motion'], 'disable' ); ?>><?php esc_html_e( 'Always disable motion', 'norinmotion' ); ?></option><option value="simplify" <?php selected( $s['reduced_motion'], 'simplify' ); ?>><?php esc_html_e( 'Always simplify', 'norinmotion' ); ?></option></select></td></tr>
		<tr><th scope="row"><label for="gmotion-mobile"><?php esc_html_e( 'Mobile maximum width', 'norinmotion' ); ?></label></th><td><input id="gmotion-mobile" type="number" min="320" max="1200" name="gmotion_settings[mobile_max]" value="<?php echo esc_attr( (string) $s['mobile_max'] ); ?>"> px</td></tr>
		<tr><th scope="row"><label for="gmotion-tablet"><?php esc_html_e( 'Tablet maximum width', 'norinmotion' ); ?></label></th><td><input id="gmotion-tablet" type="number" min="600" max="1600" name="gmotion_settings[tablet_max]" value="<?php echo esc_attr( (string) $s['tablet_max'] ); ?>"> px</td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Diagnostics', 'norinmotion' ); ?></th><td><label><input type="checkbox" name="gmotion_settings[debug]" value="1" <?php checked( $s['debug'] ); ?>> <?php esc_html_e( 'Log safe lifecycle warnings in the browser console', 'norinmotion' ); ?></label></td></tr>
		</tbody></table><?php submit_button(); ?></form></div>
		<?php
	}
}

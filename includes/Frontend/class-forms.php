<?php
/**
 * Form rendering helpers.
 */

namespace Adev_OTP\Frontend;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Plugin;

class Forms {

	/**
	 * @var bool
	 */
	private static bool $modal_printed = false;

	/**
	 * Hooks — footer modal for global openers.
	 */
	public function hooks(): void {
		add_action( 'wp_footer', array( $this, 'maybe_print_modal' ), 20 );
	}

	/**
	 * Print modal once if forced.
	 */
	public function maybe_print_modal(): void {
		if ( self::$modal_printed ) {
			return;
		}
		// Always available when assets forced or replace login.
		if ( Plugin::get_setting( 'replace_wp_login', 0 ) || did_action( 'adev_otp_need_modal' ) ) {
			self::render_modal();
		}
	}

	/**
	 * Render a form view.
	 *
	 * @param string $type login|register|forgot.
	 */
	public static function render( string $type ): void {
		$type = in_array( $type, array( 'login', 'register', 'forgot' ), true ) ? $type : 'login';
		$file = ADEV_OTP_PATH . 'templates/form-' . $type . '.php';
		if ( is_readable( $file ) ) {
			$settings = array(
				'btn_login'     => Plugin::get_translated_setting( 'btn_login' ),
				'btn_register'  => Plugin::get_translated_setting( 'btn_register' ),
				'btn_send_otp'  => Plugin::get_translated_setting( 'btn_send_otp' ),
				'btn_verify'    => Plugin::get_translated_setting( 'btn_verify' ),
				'require_name'  => (int) Plugin::get_setting( 'require_name', 1 ),
				'require_email' => (int) Plugin::get_setting( 'require_email', 0 ),
				'login_mode'    => (string) Plugin::get_setting( 'login_mode', 'otp' ),
			);
			include $file;
		}
	}

	/**
	 * Render modal shell once.
	 */
	public static function render_modal(): void {
		if ( self::$modal_printed ) {
			return;
		}
		self::$modal_printed = true;
		Assets::ensure();
		include ADEV_OTP_PATH . 'templates/modal.php';
	}

	/**
	 * Render inline tabs (no popup).
	 *
	 * @param string $active Active tab: login|register|forgot.
	 */
	public static function render_tabs( string $active = 'login' ): void {
		Assets::ensure();
		$active_tab = in_array( $active, array( 'login', 'register' ), true ) ? $active : 'login';
		include ADEV_OTP_PATH . 'templates/tabs.php';
	}
}

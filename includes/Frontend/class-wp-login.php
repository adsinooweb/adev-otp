<?php
/**
 * Optional wp-login.php override.
 */

namespace Adev_OTP\Frontend;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Plugin;

class Wp_Login {

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_action( 'login_init', array( $this, 'maybe_override' ), 1 );
		add_filter( 'login_url', array( $this, 'filter_login_url' ), 10, 3 );
	}

	/**
	 * Replace default login UI with OTP forms when enabled.
	 */
	public function maybe_override(): void {
		if ( ! Plugin::get_setting( 'enabled', 1 ) || ! Plugin::get_setting( 'replace_wp_login', 0 ) ) {
			return;
		}

		// Allow logout / lostpassword actions to fall through when needed.
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( in_array( $action, array( 'logout', 'postpass', 'confirmaction' ), true ) ) {
			return;
		}

		// Skip for interim login / customize.
		if ( isset( $_REQUEST['interim-login'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		nocache_headers();
		Assets::force();

		login_header(
			__( 'Login with mobile', 'adev-otp-login' ),
			'',
			null
		);

		echo '<div class="adev-otp-wp-login">';
		Forms::render( 'login' );
		echo '<p class="adev-otp-switch">';
		echo '<a href="#" data-adev-switch="register">' . esc_html__( 'Register', 'adev-otp-login' ) . '</a>';
		echo ' | ';
		echo '<a href="#" data-adev-switch="forgot">' . esc_html__( 'Forgot password', 'adev-otp-login' ) . '</a>';
		echo '</p>';
		echo '<div class="adev-otp-wp-login__register" hidden>';
		Forms::render( 'register' );
		echo '</div>';
		echo '<div class="adev-otp-wp-login__forgot" hidden>';
		Forms::render( 'forgot' );
		echo '</div>';
		echo '</div>';

		login_footer();
		exit;
	}

	/**
	 * Keep login_url pointing to wp-login when override is on.
	 *
	 * @param string $login_url    URL.
	 * @param string $redirect     Redirect.
	 * @param bool   $force_reauth Force.
	 */
	public function filter_login_url( string $login_url, string $redirect, bool $force_reauth ): string {
		return $login_url;
	}
}

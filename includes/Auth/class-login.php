<?php
/**
 * Login by phone OTP.
 */

namespace Adev_OTP\Auth;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Plugin;

class Login {

	/**
	 * Complete login after OTP verified.
	 *
	 * @param string $phone    Phone.
	 * @param string $password Optional password if mode requires.
	 * @return array{success:bool,message:string,redirect?:string}
	 */
	public static function complete( string $phone, string $password = '' ): array {
		$user = Phone::find_user( $phone );
		if ( ! $user ) {
			return array(
				'success' => false,
				'message' => __( 'No account found with this number. Please register first.', 'adev-otp-login' ),
			);
		}

		$mode = (string) Plugin::get_setting( 'login_mode', 'otp' );
		if ( 'otp_password' === $mode ) {
			if ( '' === $password || ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
				return array(
					'success' => false,
					'message' => __( 'Incorrect password.', 'adev-otp-login' ),
				);
			}
		}

		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );
		do_action( 'wp_login', $user->user_login, $user );

		$redirect = (string) Plugin::get_setting( 'redirect_login', '' );
		if ( '' === $redirect ) {
			$redirect = home_url( '/' );
		}

		return array(
			'success'  => true,
			'message'  => __( 'Logged in successfully.', 'adev-otp-login' ),
			'redirect' => esc_url_raw( $redirect ),
		);
	}
}

<?php
/**
 * Forgot password via OTP.
 */

namespace Adev_OTP\Auth;

defined( 'ABSPATH' ) || exit;

class Forgot_Password {

	/**
	 * Set new password after OTP verified.
	 *
	 * @param string $phone        Phone.
	 * @param string $new_password New password.
	 * @return array{success:bool,message:string,redirect?:string}
	 */
	public static function complete( string $phone, string $new_password ): array {
		$user = Phone::find_user( $phone );
		if ( ! $user ) {
			return array(
				'success' => false,
				'message' => __( 'No account found with this number.', 'adev-otp-login' ),
			);
		}

		$new_password = (string) $new_password;
		if ( strlen( $new_password ) < 6 ) {
			return array(
				'success' => false,
				'message' => __( 'Password must be at least 6 characters.', 'adev-otp-login' ),
			);
		}

		wp_set_password( $new_password, $user->ID );

		// Re-login after password change (wp_set_password clears cookies).
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );

		return array(
			'success'  => true,
			'message'  => __( 'Password changed successfully.', 'adev-otp-login' ),
			'redirect' => esc_url_raw( home_url( '/' ) ),
		);
	}
}

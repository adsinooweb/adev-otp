<?php
/**
 * Register by phone OTP.
 */

namespace Adev_OTP\Auth;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Plugin;

class Register {

	/**
	 * Create user after OTP verified (or log in if already exists).
	 *
	 * @param string               $phone Phone.
	 * @param array<string,string> $extra Extra fields: name, email, password.
	 * @return array{success:bool,message:string,redirect?:string}
	 */
	public static function complete( string $phone, array $extra = array() ): array {
		$existing = Phone::find_user( $phone );
		if ( $existing ) {
			wp_set_current_user( $existing->ID );
			wp_set_auth_cookie( $existing->ID, true );
			do_action( 'wp_login', $existing->user_login, $existing );

			$redirect = (string) Plugin::get_setting( 'redirect_login', '' );
			return array(
				'success'  => true,
				'message'  => __( 'Logged in successfully.', 'adev-otp-login' ),
				'redirect' => esc_url_raw( $redirect ? $redirect : home_url( '/' ) ),
			);
		}

		$name  = isset( $extra['name'] ) ? sanitize_text_field( $extra['name'] ) : '';
		$email = isset( $extra['email'] ) ? sanitize_email( $extra['email'] ) : '';
		$pass  = isset( $extra['password'] ) ? (string) $extra['password'] : '';

		if ( Plugin::get_setting( 'require_name', 1 ) && '' === $name ) {
			return array(
				'success' => false,
				'message' => __( 'Name is required.', 'adev-otp-login' ),
			);
		}

		if ( Plugin::get_setting( 'require_email', 0 ) ) {
			if ( ! is_email( $email ) ) {
				return array(
					'success' => false,
					'message' => __( 'Please enter a valid email.', 'adev-otp-login' ),
				);
			}
			if ( email_exists( $email ) ) {
				return array(
					'success' => false,
					'message' => __( 'This email is already registered.', 'adev-otp-login' ),
				);
			}
		}

		if ( $email && ! is_email( $email ) ) {
			$email = '';
		}

		if ( ! $email ) {
			$email = $phone . '@otp.local';
			// Ensure uniqueness.
			$i = 0;
			while ( email_exists( $email ) ) {
				++$i;
				$email = $phone . '+' . $i . '@otp.local';
			}
		}

		if ( '' === $pass ) {
			$pass = wp_generate_password( 16, true );
		}

		$login = self::unique_login( $phone );

		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_pass'    => $pass,
				'user_email'   => $email,
				'display_name' => $name ? $name : $phone,
				'nickname'     => $name ? $name : $phone,
				'first_name'   => $name,
				'role'         => get_option( 'default_role', 'subscriber' ),
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return array(
				'success' => false,
				'message' => $user_id->get_error_message(),
			);
		}

		Phone::save_for_user( (int) $user_id, $phone );

		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID, true );
			do_action( 'wp_login', $user->user_login, $user );
		}

		$redirect = (string) Plugin::get_setting( 'redirect_register', '' );
		if ( '' === $redirect ) {
			$redirect = (string) Plugin::get_setting( 'redirect_login', '' );
		}
		if ( '' === $redirect ) {
			$redirect = home_url( '/' );
		}

		return array(
			'success'  => true,
			'message'  => __( 'Registration completed successfully.', 'adev-otp-login' ),
			'redirect' => esc_url_raw( $redirect ),
		);
	}

	/**
	 * Unique username from phone.
	 *
	 * @param string $phone Phone.
	 */
	private static function unique_login( string $phone ): string {
		$base = 'u' . $phone;
		$login = $base;
		$i     = 0;
		while ( username_exists( $login ) ) {
			++$i;
			$login = $base . '_' . $i;
		}
		return $login;
	}
}

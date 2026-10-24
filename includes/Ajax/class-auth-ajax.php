<?php
/**
 * Fix Auth Ajax verify checkout helper and register purpose edge cases.
 */

namespace Adev_OTP\Ajax;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Auth\Forgot_Password;
use Adev_OTP\Auth\Login;
use Adev_OTP\Auth\Otp_Service;
use Adev_OTP\Auth\Phone;
use Adev_OTP\Auth\Rate_Limiter;
use Adev_OTP\Auth\Register;
use Adev_OTP\Plugin;

class Auth_Ajax {

	/**
	 * Register AJAX actions.
	 */
	public function hooks(): void {
		$actions = array(
			'adev_otp_send',
			'adev_otp_verify',
			'adev_otp_resend',
		);

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( $this, 'dispatch' ) );
			add_action( 'wp_ajax_nopriv_' . $action, array( $this, 'dispatch' ) );
		}
	}

	/**
	 * Route by action.
	 */
	public function dispatch(): void {
		if ( ! Plugin::get_setting( 'enabled', 1 ) ) {
			wp_send_json_error( array( 'message' => __( 'OTP login is disabled.', 'adev-otp-login' ) ) );
		}

		check_ajax_referer( 'adev_otp_front', 'nonce' );

		if ( Plugin::get_setting( 'honeypot', 1 ) ) {
			$hp = isset( $_POST['adev_hp'] ) ? (string) wp_unslash( $_POST['adev_hp'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( '' !== $hp ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request.', 'adev-otp-login' ) ) );
			}
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

		switch ( $action ) {
			case 'adev_otp_send':
			case 'adev_otp_resend':
				$this->send();
				break;
			case 'adev_otp_verify':
				$this->verify();
				break;
			default:
				wp_send_json_error( array( 'message' => __( 'Invalid action.', 'adev-otp-login' ) ) );
		}
	}

	/**
	 * Send / resend OTP.
	 */
	private function send(): void {
		$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$purpose = isset( $_POST['purpose'] ) ? sanitize_key( wp_unslash( $_POST['purpose'] ) ) : 'login';
		$allowed = array( 'login', 'register', 'forgot', 'checkout' );
		if ( ! in_array( $purpose, $allowed, true ) ) {
			$purpose = 'login';
		}

		$phone = Phone::normalize( $phone );
		if ( ! $phone ) {
			wp_send_json_error( array( 'message' => __( 'Invalid mobile number.', 'adev-otp-login' ) ) );
		}

		if ( 'login' === $purpose || 'forgot' === $purpose ) {
			if ( ! Phone::find_user( $phone ) ) {
				if ( 'login' === $purpose ) {
					wp_send_json_error( array( 'message' => __( 'No account found with this number. Please register first.', 'adev-otp-login' ) ) );
				}
				wp_send_json_error( array( 'message' => __( 'No account found with this number.', 'adev-otp-login' ) ) );
			}
		}

		// Existing user registering → treat as login OTP.
		if ( 'register' === $purpose && Phone::find_user( $phone ) ) {
			$purpose = 'login';
		}

		$result = Otp_Service::send( $phone, $purpose );
		if ( empty( $result['success'] ) ) {
			// Expose remaining cooldown for frontend timer when present.
			$data = $result;
			if ( isset( $result['message'] ) && preg_match( '/(\d+)/', (string) $result['message'], $m ) ) {
				$data['cooldown'] = (int) $m[1];
			}
			wp_send_json_error( $data );
		}

		$result['purpose'] = $purpose;
		wp_send_json_success( $result );
	}

	/**
	 * Verify OTP and complete action.
	 */
	private function verify(): void {
		$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$code     = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		$purpose  = isset( $_POST['purpose'] ) ? sanitize_key( wp_unslash( $_POST['purpose'] ) ) : 'login';
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		$allowed = array( 'login', 'register', 'forgot', 'checkout' );
		if ( ! in_array( $purpose, $allowed, true ) ) {
			$purpose = 'login';
		}

		$phone = Phone::normalize( $phone );
		$check = Otp_Service::verify( $phone, $code, $purpose );

		// Fallback: register flow may have stored under login.
		if ( is_wp_error( $check ) && 'register' === $purpose ) {
			$alt = Otp_Service::verify( $phone, $code, 'login' );
			if ( ! is_wp_error( $alt ) ) {
				$purpose = 'login';
				$check   = true;
			}
		}

		if ( is_wp_error( $check ) ) {
			wp_send_json_error( array( 'message' => $check->get_error_message() ) );
		}

		if ( 'checkout' === $purpose ) {
			if ( function_exists( 'WC' ) && WC()->session ) {
				WC()->session->set( 'adev_otp_phone_verified', $phone );
			}
			set_transient( 'adev_otp_chk_' . md5( Rate_Limiter::client_ip() . $phone ), 1, HOUR_IN_SECONDS );

			wp_send_json_success(
				array(
					'message' => __( 'Mobile number verified.', 'adev-otp-login' ),
					'phone'   => $phone,
				)
			);
		}

		if ( 'login' === $purpose ) {
			$result = Login::complete( $phone, $password );
		} elseif ( 'register' === $purpose ) {
			$result = Register::complete(
				$phone,
				array(
					'name'     => $name,
					'email'    => $email,
					'password' => $password,
				)
			);
		} else {
			$result = Forgot_Password::complete( $phone, $password );
		}

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( $result );
		}

		$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
		if ( $redirect_to ) {
			$safe = wp_validate_redirect( $redirect_to, false );
			if ( $safe ) {
				$result['redirect'] = $safe;
			}
		}

		wp_send_json_success( $result );
	}
}

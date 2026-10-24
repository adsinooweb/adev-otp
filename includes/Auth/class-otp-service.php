<?php
/**
 * OTP generate / store / verify.
 */

namespace Adev_OTP\Auth;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Gateways\Gateway_Registry;
use Adev_OTP\Plugin;

class Otp_Service {

	/**
	 * Send OTP for a purpose.
	 *
	 * @param string $phone   Normalized phone.
	 * @param string $purpose login|register|forgot|checkout|test
	 * @return array{success:bool,message:string,expires_in?:int,cooldown?:int}
	 */
	public static function send( string $phone, string $purpose = 'login' ): array {
		$phone = Phone::normalize( $phone );
		if ( ! $phone ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid mobile number.', 'adev-otp-login' ),
			);
		}

		if ( Phone::is_blacklisted( $phone ) ) {
			return array(
				'success' => false,
				'message' => __( 'This number cannot receive messages.', 'adev-otp-login' ),
			);
		}

		$allowed = Rate_Limiter::can_send( $phone );
		if ( is_wp_error( $allowed ) ) {
			$err = array(
				'success' => false,
				'message' => $allowed->get_error_message(),
			);
			$data = $allowed->get_error_data();
			if ( is_array( $data ) && isset( $data['cooldown'] ) ) {
				$err['cooldown'] = (int) $data['cooldown'];
			}
			return $err;
		}

		$gateway = Gateway_Registry::instance()->get_active();
		if ( ! $gateway ) {
			return array(
				'success' => false,
				'message' => __( 'SMS gateway is not active.', 'adev-otp-login' ),
			);
		}

		$length = (int) Plugin::get_setting( 'otp_length', 5 );
		$length = max( 4, min( 8, $length ) );
		$min    = (int) pow( 10, $length - 1 );
		$max    = (int) pow( 10, $length ) - 1;
		$code   = (string) wp_rand( $min, $max );

		$ttl = (int) Plugin::get_setting( 'otp_ttl', 120 );
		$key = self::storage_key( $phone );

		$data = array(
			'hash'     => self::hash_code( $code ),
			'attempts' => 0,
			'purpose'  => $purpose,
			'created'  => time(),
		);
		set_transient( $key, $data, $ttl );

		$result = $gateway->send_otp( $phone, $code, array( 'purpose' => $purpose ) );

		self::log_send(
			$phone,
			$gateway->get_id(),
			! empty( $result['success'] ),
			isset( $result['message'] ) ? (string) $result['message'] : ''
		);

		if ( empty( $result['success'] ) ) {
			delete_transient( $key );
			return array(
				'success' => false,
				'message' => isset( $result['message'] ) ? $result['message'] : __( 'SMS sending failed.', 'adev-otp-login' ),
			);
		}

		Rate_Limiter::record_send( $phone );

		return array(
			'success'    => true,
			'message'    => __( 'Verification code sent.', 'adev-otp-login' ),
			'expires_in' => $ttl,
			'cooldown'   => (int) Plugin::get_setting( 'resend_cooldown', 60 ),
		);
	}

	/**
	 * Verify OTP.
	 *
	 * @param string $phone   Phone.
	 * @param string $code    User-entered code.
	 * @param string $purpose Purpose (informational; OTP is keyed by phone).
	 * @return true|\WP_Error
	 */
	public static function verify( string $phone, string $code, string $purpose = 'login' ) {
		$phone = Phone::normalize( $phone );
		$code  = self::normalize_code( $code );
		if ( ! $phone || ! $code ) {
			return new \WP_Error( 'invalid', __( 'Invalid data.', 'adev-otp-login' ) );
		}

		$key  = self::storage_key( $phone );
		$data = get_transient( $key );

		// Backward-compat: old keys included purpose in the hash.
		if ( ( ! is_array( $data ) || empty( $data['hash'] ) ) && $purpose ) {
			$data = get_transient( 'adev_otp_' . md5( $phone . '|' . $purpose ) );
			if ( is_array( $data ) && ! empty( $data['hash'] ) ) {
				$key = 'adev_otp_' . md5( $phone . '|' . $purpose );
			}
		}

		if ( ! is_array( $data ) || empty( $data['hash'] ) ) {
			return new \WP_Error( 'expired', __( 'Code expired. Please request a new one.', 'adev-otp-login' ) );
		}

		$max = (int) Plugin::get_setting( 'max_attempts', 5 );
		if ( (int) $data['attempts'] >= $max ) {
			delete_transient( $key );
			return new \WP_Error( 'attempts', __( 'Too many attempts.', 'adev-otp-login' ) );
		}

		$valid = self::check_code( $code, (string) $data['hash'] );

		// Legacy hashes created with wp_hash_password before the HMAC fix.
		if ( ! $valid && 0 === strpos( (string) $data['hash'], '$' ) ) {
			$valid = wp_check_password( $code, (string) $data['hash'] );
		}

		if ( ! $valid ) {
			$data['attempts'] = (int) $data['attempts'] + 1;
			$remaining        = max( 1, (int) Plugin::get_setting( 'otp_ttl', 120 ) - ( time() - (int) $data['created'] ) );
			set_transient( $key, $data, $remaining );
			return new \WP_Error( 'mismatch', __( 'Incorrect code.', 'adev-otp-login' ) );
		}

		delete_transient( $key );
		// Clean legacy key too.
		delete_transient( 'adev_otp_' . md5( $phone . '|' . $purpose ) );

		return true;
	}

	/**
	 * Hash OTP for storage (no $ chars — safe in options/transients).
	 *
	 * @param string $code Plain OTP.
	 */
	private static function hash_code( string $code ): string {
		return hash_hmac( 'sha256', $code, wp_salt( 'auth' ) );
	}

	/**
	 * Constant-time compare.
	 *
	 * @param string $code Plain OTP.
	 * @param string $hash Stored hash.
	 */
	private static function check_code( string $code, string $hash ): bool {
		$expected = self::hash_code( $code );
		return hash_equals( $expected, $hash );
	}

	/**
	 * Normalize OTP digits (Latin + Persian/Arabic/Afghan).
	 *
	 * @param string $code Raw code.
	 */
	public static function normalize_code( string $code ): string {
		$code = Digits::to_latin( $code );
		$code = preg_replace( '/\D+/', '', $code );
		return is_string( $code ) ? $code : '';
	}

	/**
	 * Transient key — phone only so purpose mismatches cannot break verify.
	 *
	 * @param string $phone Phone.
	 */
	private static function storage_key( string $phone ): string {
		return 'adev_otp_' . md5( $phone );
	}

	/**
	 * Append send log (no raw OTP).
	 *
	 * @param string $phone   Phone.
	 * @param string $gateway Gateway id.
	 * @param bool   $success Success.
	 * @param string $message Message.
	 */
	public static function log_send( string $phone, string $gateway, bool $success, string $message ): void {
		$logs = get_option( 'adev_otp_logs', array() );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}

		$logs[] = array(
			'time'    => current_time( 'mysql' ),
			'phone'   => Phone::mask( $phone ),
			'gateway' => $gateway,
			'success' => $success ? 1 : 0,
			'message' => wp_strip_all_tags( mb_substr( $message, 0, 200 ) ),
		);

		if ( count( $logs ) > 200 ) {
			$logs = array_slice( $logs, -200 );
		}

		update_option( 'adev_otp_logs', $logs, false );
	}
}

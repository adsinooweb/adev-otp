<?php
/**
 * Admin settings storage and defaults.
 */

namespace Adev_OTP\Admin;

defined( 'ABSPATH' ) || exit;

class Settings {

	public const OPTION = 'adev_otp_settings';

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'enabled'              => 1,
			'replace_wp_login'     => 0,
			'otp_length'           => 5,
			'otp_ttl'              => 120,
			'max_attempts'         => 5,
			'resend_cooldown'      => 60,
			'daily_limit_phone'    => 100,
			'daily_limit_ip'       => 200,
			'login_mode'           => 'otp', // otp | otp_password
			'active_gateway'       => 'kavenegar',
			'gateways'             => array(
				'kavenegar'   => array(
					'api_key'     => '',
					'sender'      => '',
					'template'    => '',
					'send_mode'   => 'verify', // verify | simple
					'message'     => 'Your verification code: {OTP}',
				),
				'melipayamak' => array(
					'username'    => '',
					'password'    => '',
					'sender'      => '',
					'body_id'     => '',
					'send_mode'   => 'pattern', // pattern | simple
					'message'     => 'Your verification code: {OTP}',
				),
			),
			'require_name'         => 1,
			'require_email'        => 0,
			'btn_login'            => 'Login',
			'btn_register'         => 'Register',
			'btn_send_otp'         => 'Send code',
			'btn_verify'           => 'Verify',
			'btn_bg_color'         => '#0aa74b',
			'btn_text_color'       => '#ffffff',
			'redirect_login'       => '',
			'redirect_register'    => '',
			'wc_checkout_verify'      => 0,
			'wc_checkout_required'    => 0,
			'wc_checkout_force_login' => 0,
			'phone_blacklist'      => '',
			'honeypot'             => 1,
		);
	}

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'wp_ajax_adev_otp_test_sms', array( $this, 'ajax_test_sms' ) );
		add_action( 'wp_ajax_adev_otp_clear_limits', array( $this, 'ajax_clear_limits' ) );
	}

	/**
	 * Register setting.
	 */
	public function register(): void {
		register_setting(
			'adev_otp_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize settings on save.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$defaults = self::defaults();
		$current  = get_option( self::OPTION, $defaults );
		if ( ! is_array( $current ) ) {
			$current = $defaults;
		}
		if ( ! is_array( $input ) ) {
			return wp_parse_args( $current, $defaults );
		}

		$out = wp_parse_args( $current, $defaults );

		$bool_keys = array(
			'enabled',
			'replace_wp_login',
			'require_name',
			'require_email',
			'wc_checkout_verify',
			'wc_checkout_required',
			'wc_checkout_force_login',
			'honeypot',
		);
		foreach ( $bool_keys as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$out[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
			}
		}

		$int_keys = array(
			'otp_length',
			'otp_ttl',
			'max_attempts',
			'resend_cooldown',
			'daily_limit_phone',
			'daily_limit_ip',
		);
		foreach ( $int_keys as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$out[ $key ] = absint( $input[ $key ] );
			}
		}

		$out['otp_length'] = max( 4, min( 8, (int) $out['otp_length'] ) );
		$out['otp_ttl']    = max( 60, min( 600, (int) $out['otp_ttl'] ) );

		if ( isset( $input['login_mode'] ) && in_array( $input['login_mode'], array( 'otp', 'otp_password' ), true ) ) {
			$out['login_mode'] = $input['login_mode'];
		}

		if ( isset( $input['active_gateway'] ) ) {
			$out['active_gateway'] = sanitize_key( $input['active_gateway'] );
		}

		$text_keys = array(
			'btn_login',
			'btn_register',
			'btn_send_otp',
			'btn_verify',
			'redirect_login',
			'redirect_register',
			'phone_blacklist',
		);
		foreach ( $text_keys as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$val = sanitize_text_field( $input[ $key ] );
				if ( 'phone_blacklist' === $key ) {
					$val = \Adev_OTP\Auth\Digits::to_latin( $val );
				}
				$out[ $key ] = $val;
			}
		}

		if ( isset( $input['btn_bg_color'] ) ) {
			$out['btn_bg_color'] = sanitize_hex_color( $input['btn_bg_color'] ) ?: '#1a5f4a';
		}
		if ( isset( $input['btn_text_color'] ) ) {
			$out['btn_text_color'] = sanitize_hex_color( $input['btn_text_color'] ) ?: '#ffffff';
		}

		if ( isset( $input['gateways'] ) && is_array( $input['gateways'] ) ) {
			$secret_keys = array( 'password', 'api_key' );
			foreach ( $input['gateways'] as $gid => $fields ) {
				$gid = sanitize_key( $gid );
				if ( ! isset( $out['gateways'][ $gid ] ) || ! is_array( $fields ) ) {
					continue;
				}
				foreach ( $fields as $fk => $fv ) {
					$fk = sanitize_key( $fk );
					$fv = is_string( $fv ) ? trim( $fv ) : $fv;
					// Browsers often submit empty password fields — keep previous secret.
					if ( in_array( $fk, $secret_keys, true ) && '' === (string) $fv ) {
						continue;
					}
					$out['gateways'][ $gid ][ $fk ] = sanitize_text_field( (string) $fv );
				}
			}
		}

		return $out;
	}

	/**
	 * AJAX: send test SMS.
	 */
	public function ajax_test_sms(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'adev-otp-login' ) ), 403 );
		}
		check_ajax_referer( 'adev_otp_admin', 'nonce' );

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$phone = \Adev_OTP\Auth\Phone::normalize( $phone );
		if ( ! $phone ) {
			wp_send_json_error( array( 'message' => __( 'Invalid mobile number.', 'adev-otp-login' ) ) );
		}

		$gateway = \Adev_OTP\Gateways\Gateway_Registry::instance()->get_active();
		if ( ! $gateway ) {
			wp_send_json_error( array( 'message' => __( 'No active gateway found.', 'adev-otp-login' ) ) );
		}

		$code   = (string) wp_rand( 10000, 99999 );
		$result = $gateway->send_otp( $phone, $code, array( 'purpose' => 'test' ) );

		\Adev_OTP\Auth\Otp_Service::log_send(
			$phone,
			$gateway->get_id(),
			! empty( $result['success'] ),
			isset( $result['message'] ) ? (string) $result['message'] : ''
		);

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: 1: gateway name, 2: phone */
						__( 'Test SMS sent via %1$s to %2$s.', 'adev-otp-login' ),
						$gateway->get_name(),
						$phone
					),
				)
			);
		}

		$msg = isset( $result['message'] ) ? (string) $result['message'] : __( 'Sending failed.', 'adev-otp-login' );
		// Append raw hint for admins when available.
		if ( ! empty( $result['raw'] ) && is_array( $result['raw'] ) ) {
			$raw_json = wp_json_encode( $result['raw'], JSON_UNESCAPED_UNICODE );
			if ( $raw_json && strlen( $raw_json ) < 400 ) {
				$msg .= ' | ' . $raw_json;
			}
		}

		wp_send_json_error( array( 'message' => $msg ) );
	}

	/**
	 * AJAX: clear cooldown / daily rate limits.
	 */
	public function ajax_clear_limits(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'adev-otp-login' ) ), 403 );
		}
		check_ajax_referer( 'adev_otp_admin', 'nonce' );

		\Adev_OTP\Auth\Rate_Limiter::clear_all();
		wp_cache_flush();

		wp_send_json_success( array( 'message' => __( 'Rate limits and temporary codes were cleared.', 'adev-otp-login' ) ) );
	}
}

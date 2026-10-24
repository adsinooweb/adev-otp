<?php
/**
 * Kavenegar SMS gateway.
 */

namespace Adev_OTP\Gateways;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Plugin;

class Kavenegar implements Gateway_Interface {

	public function get_id(): string {
		return 'kavenegar';
	}

	public function get_name(): string {
		return __( 'Kavenegar', 'adev-otp-login' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_settings_fields(): array {
		return array(
			array(
				'key'   => 'api_key',
				'label' => __( 'API Key', 'adev-otp-login' ),
				'type'  => 'text',
				'desc'  => __( 'Kavenegar panel API key', 'adev-otp-login' ),
			),
			array(
				'key'   => 'sender',
				'label' => __( 'Sender number', 'adev-otp-login' ),
				'type'  => 'text',
				'desc'  => __( 'Required for simple send mode.', 'adev-otp-login' ),
			),
			array(
				'key'   => 'send_mode',
				'label' => __( 'Send mode', 'adev-otp-login' ),
				'type'  => 'select',
				'options' => array(
					'verify' => __( 'Verify Lookup (template)', 'adev-otp-login' ),
					'simple' => __( 'Simple send', 'adev-otp-login' ),
				),
			),
			array(
				'key'   => 'template',
				'label' => __( 'Verify template name', 'adev-otp-login' ),
				'type'  => 'text',
				'desc'  => __( 'Template name defined in the panel (token = OTP code)', 'adev-otp-login' ),
			),
			array(
				'key'   => 'message',
				'label' => __( 'Message text (simple send)', 'adev-otp-login' ),
				'type'  => 'textarea',
				'desc'  => __( 'Use {OTP} as a placeholder for the code.', 'adev-otp-login' ),
			),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function send_otp( string $phone, string $otp, array $context = array() ): array {
		$cfg = Plugin::get_setting( 'gateways', array() );
		$cfg = isset( $cfg['kavenegar'] ) && is_array( $cfg['kavenegar'] ) ? $cfg['kavenegar'] : array();

		$api_key = isset( $cfg['api_key'] ) ? trim( (string) $cfg['api_key'] ) : '';
		if ( '' === $api_key ) {
			return array(
				'success' => false,
				'message' => __( 'Kavenegar API key is not configured.', 'adev-otp-login' ),
			);
		}

		$mode = isset( $cfg['send_mode'] ) ? $cfg['send_mode'] : 'verify';
		$to   = $this->to_receptor( $phone );

		if ( 'verify' === $mode ) {
			$template = isset( $cfg['template'] ) ? trim( (string) $cfg['template'] ) : '';
			if ( '' === $template ) {
				return array(
					'success' => false,
					'message' => __( 'Verify template name is not configured.', 'adev-otp-login' ),
				);
			}
			$url  = sprintf(
				'https://api.kavenegar.com/v1/%s/verify/lookup.json',
				rawurlencode( $api_key )
			);
			$body = array(
				'receptor' => $to,
				'token'    => $otp,
				'template' => $template,
			);
		} else {
			$sender  = isset( $cfg['sender'] ) ? trim( (string) $cfg['sender'] ) : '';
			$message = isset( $cfg['message'] ) ? (string) $cfg['message'] : 'Your verification code: {OTP}';
			$message = str_replace( '{OTP}', $otp, $message );
			$url     = sprintf(
				'https://api.kavenegar.com/v1/%s/sms/send.json',
				rawurlencode( $api_key )
			);
			$body    = array(
				'receptor' => $to,
				'sender'   => $sender,
				'message'  => $message,
			);
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		$return_status = isset( $data['return']['status'] ) ? (int) $data['return']['status'] : 0;
		$return_msg    = isset( $data['return']['message'] ) ? (string) $data['return']['message'] : $raw;

		if ( 200 === $code && 200 === $return_status ) {
			return array(
				'success' => true,
				'message' => $return_msg,
				'raw'     => $data,
			);
		}

		return array(
			'success' => false,
			'message' => $return_msg ? $return_msg : __( 'Kavenegar error', 'adev-otp-login' ),
			'raw'     => $data,
		);
	}

	/**
	 * Convert 09… to 989…
	 *
	 * @param string $phone Phone.
	 */
	private function to_receptor( string $phone ): string {
		$digits = preg_replace( '/\D+/', '', $phone );
		if ( 0 === strpos( $digits, '09' ) && 11 === strlen( $digits ) ) {
			return '98' . substr( $digits, 1 );
		}
		if ( 0 === strpos( $digits, '9' ) && 10 === strlen( $digits ) ) {
			return '98' . $digits;
		}
		return $digits;
	}
}

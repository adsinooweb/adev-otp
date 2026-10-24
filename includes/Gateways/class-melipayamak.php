<?php
/**
 * Melipayamak SMS gateway.
 */

namespace Adev_OTP\Gateways;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Plugin;

class Melipayamak implements Gateway_Interface {

	public function get_id(): string {
		return 'melipayamak';
	}

	public function get_name(): string {
		return __( 'Melipayamak', 'adev-otp-login' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_settings_fields(): array {
		return array(
			array(
				'key'   => 'username',
				'label' => __( 'Username', 'adev-otp-login' ),
				'type'  => 'text',
			),
			array(
				'key'   => 'password',
				'label' => __( 'Password / API Key', 'adev-otp-login' ),
				'type'  => 'text',
				'desc'  => __( 'Melipayamak panel password or API key (shown as plain text)', 'adev-otp-login' ),
			),
			array(
				'key'   => 'sender',
				'label' => __( 'Sender number', 'adev-otp-login' ),
				'type'  => 'text',
				'desc'  => __( 'Required for simple send mode.', 'adev-otp-login' ),
			),
			array(
				'key'     => 'send_mode',
				'label'   => __( 'Send mode', 'adev-otp-login' ),
				'type'    => 'select',
				'options' => array(
					'pattern' => __( 'Pattern send', 'adev-otp-login' ),
					'simple'  => __( 'Simple send', 'adev-otp-login' ),
				),
			),
			array(
				'key'   => 'body_id',
				'label' => __( 'Pattern code (bodyId)', 'adev-otp-login' ),
				'type'  => 'text',
				'desc'  => __( 'Approved pattern ID in the Melipayamak panel', 'adev-otp-login' ),
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
		$cfg = isset( $cfg['melipayamak'] ) && is_array( $cfg['melipayamak'] ) ? $cfg['melipayamak'] : array();

		$username = isset( $cfg['username'] ) ? trim( (string) $cfg['username'] ) : '';
		$password = isset( $cfg['password'] ) ? trim( (string) $cfg['password'] ) : '';

		if ( '' === $username || '' === $password ) {
			return array(
				'success' => false,
				'message' => __( 'Melipayamak username or password is not set. Enter and save them again in the Gateway tab.', 'adev-otp-login' ),
			);
		}

		$mode = isset( $cfg['send_mode'] ) ? $cfg['send_mode'] : 'pattern';
		$to   = $this->to_local( $phone );

		if ( 'pattern' === $mode ) {
			$body_id = isset( $cfg['body_id'] ) ? trim( (string) $cfg['body_id'] ) : '';
			if ( '' === $body_id ) {
				return array(
					'success' => false,
					'message' => __( 'Pattern code (bodyId) is not configured.', 'adev-otp-login' ),
				);
			}

			$url  = 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber';
			$body = array(
				'username' => $username,
				'password' => $password,
				'text'     => $otp,
				'to'       => $to,
				'bodyId'   => (int) $body_id,
			);
		} else {
			$sender  = isset( $cfg['sender'] ) ? trim( (string) $cfg['sender'] ) : '';
			$message = isset( $cfg['message'] ) ? (string) $cfg['message'] : 'Your verification code: {OTP}';
			$message = str_replace( '{OTP}', $otp, $message );

			if ( '' === $sender ) {
				return array(
					'success' => false,
					'message' => __( 'Sender number is required for simple send.', 'adev-otp-login' ),
				);
			}

			$url  = 'https://rest.payamak-panel.com/api/SendSMS/SendSMS';
			$body = array(
				'username' => $username,
				'password' => $password,
				'from'     => $sender,
				'to'       => $to,
				'text'     => $message,
				'isflash'  => 'false',
			);
		}

		// Official Melipayamak samples use form-urlencoded, not JSON.
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$http = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: 1: HTTP code, 2: body */
					__( 'Invalid Melipayamak response (HTTP %1$d): %2$s', 'adev-otp-login' ),
					$http,
					$raw ? mb_substr( $raw, 0, 200 ) : '-'
				),
				'raw'     => $raw,
			);
		}

		$ret_status = isset( $data['RetStatus'] ) ? (int) $data['RetStatus'] : -1;
		$value      = isset( $data['Value'] ) ? (string) $data['Value'] : '';
		$str_ret    = isset( $data['StrRetStatus'] ) ? (string) $data['StrRetStatus'] : '';

		// Success: RetStatus=1, or long numeric recId in Value.
		if ( 1 === $ret_status || ( ctype_digit( $value ) && strlen( $value ) >= 15 ) ) {
			return array(
				'success' => true,
				'message' => $str_ret ? $str_ret : __( 'Sent successfully', 'adev-otp-login' ),
				'raw'     => $data,
			);
		}

		$error_map = array(
			'0'  => __( 'Incorrect username or password.', 'adev-otp-login' ),
			'2'  => __( 'Insufficient panel credit.', 'adev-otp-login' ),
			'6'  => __( 'Message is empty.', 'adev-otp-login' ),
			'7'  => __( 'Send time has passed.', 'adev-otp-login' ),
			'8'  => __( 'SMS contains a filtered word.', 'adev-otp-login' ),
			'9'  => __( 'Sender line is invalid.', 'adev-otp-login' ),
			'10' => __( 'User is inactive.', 'adev-otp-login' ),
			'11' => __( 'Sending was not completed.', 'adev-otp-login' ),
			'12' => __( 'Panel documents are incomplete.', 'adev-otp-login' ),
			'16' => __( 'Recipient number is invalid.', 'adev-otp-login' ),
		);
		$mapped = isset( $error_map[ $value ] ) ? $error_map[ $value ] : '';
		$msg    = $mapped ? $mapped : ( $str_ret ? $str_ret : ( $value !== '' ? $value : __( 'Melipayamak error', 'adev-otp-login' ) ) );

		return array(
			'success' => false,
			'message' => $msg,
			'raw'     => $data,
		);
	}

	/**
	 * Local 09… format for Melipayamak.
	 *
	 * @param string $phone Phone.
	 */
	private function to_local( string $phone ): string {
		$digits = preg_replace( '/\D+/', '', $phone );
		if ( ! is_string( $digits ) ) {
			return '';
		}
		if ( 0 === strpos( $digits, '98' ) && 12 === strlen( $digits ) ) {
			return '0' . substr( $digits, 2 );
		}
		if ( 0 === strpos( $digits, '9' ) && 10 === strlen( $digits ) ) {
			return '0' . $digits;
		}
		return $digits;
	}
}

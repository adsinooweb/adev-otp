<?php
/**
 * Iranian mobile phone helpers.
 */

namespace Adev_OTP\Auth;

defined( 'ABSPATH' ) || exit;

class Phone {

	/**
	 * Normalize to 09xxxxxxxxx or empty string if invalid.
	 *
	 * @param string $raw Raw input.
	 */
	public static function normalize( string $raw ): string {
		$raw = Digits::to_latin( trim( $raw ) );
		$raw = str_replace( array( ' ', '-', '_', '(', ')' ), '', $raw );

		if ( 0 === strpos( $raw, '+98' ) ) {
			$raw = '0' . substr( $raw, 3 );
		}

		$digits = preg_replace( '/\D+/', '', $raw );
		if ( ! is_string( $digits ) ) {
			return '';
		}

		if ( 0 === strpos( $digits, '98' ) && 12 === strlen( $digits ) ) {
			$digits = '0' . substr( $digits, 2 );
		} elseif ( 0 === strpos( $digits, '9' ) && 10 === strlen( $digits ) ) {
			$digits = '0' . $digits;
		}

		if ( ! preg_match( '/^09\d{9}$/', $digits ) ) {
			return '';
		}

		return $digits;
	}

	/**
	 * Mask phone for display/logs: 0912***6789
	 *
	 * @param string $phone Normalized phone.
	 */
	public static function mask( string $phone ): string {
		if ( strlen( $phone ) < 11 ) {
			return '***';
		}
		return substr( $phone, 0, 4 ) . '***' . substr( $phone, -4 );
	}

	/**
	 * Find WP user by phone meta.
	 *
	 * @param string $phone Normalized phone.
	 * @return \WP_User|null
	 */
	public static function find_user( string $phone ): ?\WP_User {
		$phone = self::normalize( $phone );
		if ( ! $phone ) {
			return null;
		}

		$users = get_users(
			array(
				'meta_key'   => '_adev_otp_phone',
				'meta_value' => $phone,
				'number'     => 1,
				'count_total'=> false,
			)
		);

		if ( ! empty( $users ) && $users[0] instanceof \WP_User ) {
			return $users[0];
		}

		// Fallback: WooCommerce billing_phone.
		$users = get_users(
			array(
				'meta_key'   => 'billing_phone',
				'meta_value' => $phone,
				'number'     => 1,
				'count_total'=> false,
			)
		);

		if ( ! empty( $users ) && $users[0] instanceof \WP_User ) {
			update_user_meta( $users[0]->ID, '_adev_otp_phone', $phone );
			return $users[0];
		}

		return null;
	}

	/**
	 * Save phone on user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $phone   Normalized phone.
	 */
	public static function save_for_user( int $user_id, string $phone ): void {
		$phone = self::normalize( $phone );
		if ( ! $phone || $user_id <= 0 ) {
			return;
		}
		update_user_meta( $user_id, '_adev_otp_phone', $phone );
		update_user_meta( $user_id, 'billing_phone', $phone );
	}

	/**
	 * Check blacklist (comma/newline separated).
	 *
	 * @param string $phone Normalized phone.
	 */
	public static function is_blacklisted( string $phone ): bool {
		$list = (string) \Adev_OTP\Plugin::get_setting( 'phone_blacklist', '' );
		if ( '' === trim( $list ) ) {
			return false;
		}
		$parts = preg_split( '/[\s,;]+/', $list );
		if ( ! is_array( $parts ) ) {
			return false;
		}
		foreach ( $parts as $part ) {
			$n = self::normalize( $part );
			if ( $n && $n === $phone ) {
				return true;
			}
		}
		return false;
	}
}

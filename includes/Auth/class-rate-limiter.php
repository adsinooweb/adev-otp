<?php
/**
 * Rate limiting for OTP sends.
 */

namespace Adev_OTP\Auth;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Plugin;

class Rate_Limiter {

	/**
	 * Check whether send is allowed.
	 *
	 * @param string $phone Normalized phone.
	 * @return true|\WP_Error
	 */
	public static function can_send( string $phone ) {
		$ip = self::client_ip();

		$cooldown = (int) Plugin::get_setting( 'resend_cooldown', 60 );
		$cd_key   = 'adev_otp_cd_' . md5( $phone );
		$cd_until = get_transient( $cd_key );
		if ( false !== $cd_until && '' !== $cd_until && null !== $cd_until ) {
			$left = is_numeric( $cd_until ) ? ( (int) $cd_until - time() ) : $cooldown;
			if ( $left > 0 ) {
				return new \WP_Error(
					'cooldown',
					sprintf(
						/* translators: %d: seconds */
						__( 'Please wait %d seconds and try again.', 'adev-otp-login' ),
						$left
					),
					array( 'cooldown' => $left )
				);
			}
			delete_transient( $cd_key );
		}

		$daily_phone = (int) Plugin::get_setting( 'daily_limit_phone', 10 );
		$phone_count = (int) get_transient( 'adev_otp_dp_' . md5( $phone ) );
		if ( $phone_count >= $daily_phone ) {
			return new \WP_Error( 'daily_phone', __( 'Daily send limit for this number has been reached.', 'adev-otp-login' ) );
		}

		$daily_ip = (int) Plugin::get_setting( 'daily_limit_ip', 20 );
		$ip_count = (int) get_transient( 'adev_otp_di_' . md5( $ip ) );
		if ( $ip_count >= $daily_ip ) {
			return new \WP_Error( 'daily_ip', __( 'Daily send limit for this IP has been reached.', 'adev-otp-login' ) );
		}

		return true;
	}

	/**
	 * Record a successful send for limits.
	 *
	 * @param string $phone Normalized phone.
	 */
	public static function record_send( string $phone ): void {
		$ip       = self::client_ip();
		$cooldown = (int) Plugin::get_setting( 'resend_cooldown', 60 );

		set_transient( 'adev_otp_cd_' . md5( $phone ), time() + $cooldown, $cooldown );

		$pk = 'adev_otp_dp_' . md5( $phone );
		set_transient( $pk, (int) get_transient( $pk ) + 1, DAY_IN_SECONDS );

		$ik = 'adev_otp_di_' . md5( $ip );
		set_transient( $ik, (int) get_transient( $ik ) + 1, DAY_IN_SECONDS );
	}

	/**
	 * Clear cooldown + daily counters for one phone.
	 *
	 * @param string $phone Raw or normalized phone.
	 */
	public static function clear_phone( string $phone ): void {
		$phone = Phone::normalize( $phone );
		if ( ! $phone ) {
			return;
		}
		$hash = md5( $phone );
		delete_transient( 'adev_otp_cd_' . $hash );
		delete_transient( 'adev_otp_dp_' . $hash );
		delete_transient( 'adev_otp_' . $hash );
	}

	/**
	 * Clear all OTP-related rate limits and pending codes.
	 */
	public static function clear_all(): void {
		global $wpdb;

		$patterns = array(
			$wpdb->esc_like( '_transient_adev_otp_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_adev_otp_' ) . '%',
			$wpdb->esc_like( '_site_transient_adev_otp_' ) . '%',
			$wpdb->esc_like( '_site_transient_timeout_adev_otp_' ) . '%',
		);

		foreach ( $patterns as $like ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$like
				)
			);
		}
	}

	/**
	 * Client IP.
	 */
	public static function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
		/**
		 * Filter client IP used for rate limiting.
		 *
		 * @param string $ip IP address.
		 */
		return (string) apply_filters( 'adev_otp_client_ip', $ip );
	}
}

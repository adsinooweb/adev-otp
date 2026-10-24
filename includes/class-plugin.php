<?php
/**
 * Main plugin bootstrap.
 */

namespace Adev_OTP;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Admin\Menu;
use Adev_OTP\Admin\Settings;
use Adev_OTP\Ajax\Auth_Ajax;
use Adev_OTP\Frontend\Assets;
use Adev_OTP\Frontend\Forms;
use Adev_OTP\Frontend\Shortcodes;
use Adev_OTP\Frontend\Wp_Login;
use Adev_OTP\Gateways\Gateway_Registry;
use Adev_OTP\I18n\Polylang as Polylang_I18n;
use Adev_OTP\I18n\Textdomain;
use Adev_OTP\WooCommerce\Account as WC_Account;
use Adev_OTP\WooCommerce\Checkout as WC_Checkout;

class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Activation callback.
	 */
	public static function activate(): void {
		$defaults = Settings::defaults();
		if ( false === get_option( 'adev_otp_settings', false ) ) {
			add_option( 'adev_otp_settings', $defaults );
		}
		if ( false === get_option( 'adev_otp_logs', false ) ) {
			add_option( 'adev_otp_logs', array() );
		}
		flush_rewrite_rules();
	}

	/**
	 * Deactivation callback.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	/**
	 * Boot hooks.
	 */
	public function init(): void {
		Polylang_I18n::migrate_legacy_defaults();
		// Polylang AJAX locale must register before textdomain load (init -1 vs 0).
		( new Polylang_I18n() )->hooks();
		( new Textdomain() )->hooks();

		Gateway_Registry::instance()->register_defaults();

		if ( is_admin() ) {
			( new Menu() )->hooks();
			( new Settings() )->hooks();
		}

		( new Auth_Ajax() )->hooks();
		( new Assets() )->hooks();
		( new Shortcodes() )->hooks();
		( new Forms() )->hooks();
		( new Wp_Login() )->hooks();

		if ( class_exists( 'WooCommerce' ) ) {
			( new WC_Checkout() )->hooks();
			( new WC_Account() )->hooks();
		}
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public static function get_setting( string $key, $default = null ) {
		$settings = get_option( 'adev_otp_settings', Settings::defaults() );
		if ( ! is_array( $settings ) ) {
			$settings = Settings::defaults();
		}
		$merged = wp_parse_args( $settings, Settings::defaults() );
		return array_key_exists( $key, $merged ) ? $merged[ $key ] : $default;
	}

	/**
	 * Get a UI string setting, translated for the current language.
	 *
	 * Uses Polylang String Translations when available; otherwise falls back
	 * to gettext when the stored value matches the English default.
	 *
	 * @param string $key Setting key (btn_login, btn_register, …).
	 */
	public static function get_translated_setting( string $key ): string {
		$defaults = Settings::defaults();
		$default  = isset( $defaults[ $key ] ) ? (string) $defaults[ $key ] : '';
		$value    = (string) self::get_setting( $key, $default );

		/**
		 * Filter a translated setting value (Polylang hooks here).
		 *
		 * @param string $value Setting value.
		 * @param string $key   Setting key.
		 */
		$value = (string) apply_filters( 'adev_otp_translate_setting', $value, $key );

		if ( $value === $default || '' === $value ) {
			$gettext_defaults = array(
				'btn_login'    => __( 'Login', 'adev-otp-login' ),
				'btn_register' => __( 'Register', 'adev-otp-login' ),
				'btn_send_otp' => __( 'Send code', 'adev-otp-login' ),
				'btn_verify'   => __( 'Verify', 'adev-otp-login' ),
			);
			if ( isset( $gettext_defaults[ $key ] ) ) {
				return $gettext_defaults[ $key ];
			}
		}

		return $value;
	}

	/**
	 * Update settings partially.
	 *
	 * @param array $patch Key/value pairs.
	 */
	public static function update_settings( array $patch ): void {
		$settings = get_option( 'adev_otp_settings', Settings::defaults() );
		if ( ! is_array( $settings ) ) {
			$settings = Settings::defaults();
		}
		$settings = array_merge( $settings, $patch );
		update_option( 'adev_otp_settings', $settings );
	}
}

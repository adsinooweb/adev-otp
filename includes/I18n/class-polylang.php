<?php
/**
 * Polylang integration: string registration and locale-aware settings.
 */

namespace Adev_OTP\I18n;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Admin\Settings;
use Adev_OTP\Plugin;

class Polylang {

	/**
	 * Setting keys exposed to Polylang String Translations.
	 *
	 * @var string[]
	 */
	private const STRING_KEYS = array(
		'btn_login',
		'btn_register',
		'btn_send_otp',
		'btn_verify',
	);

	/**
	 * Legacy Persian defaults → English (post i18n migration).
	 *
	 * @var array<string, string>
	 */
	private const LEGACY_BUTTONS = array(
		"\u{0648}\u{0631}\u{0648}\u{062F}"                         => 'Login',      // ورود
		"\u{062B}\u{0628}\u{062A}\u{200C}\u{0646}\u{0627}\u{0645}" => 'Register',   // ثبت‌نام
		"\u{0627}\u{0631}\u{0633}\u{0627}\u{0644} \u{06A9}\u{062F}" => 'Send code', // ارسال کد
		"\u{062A}\u{0623}\u{06CC}\u{06CC}\u{062F}"                 => 'Verify',     // تأیید
	);

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		// Before textdomain load so AJAX responses use the correct catalog.
		add_action( 'init', array( $this, 'bootstrap_ajax_language' ), -1 );
		add_action( 'init', array( $this, 'register_strings' ), 20 );
		add_action( 'update_option_' . Settings::OPTION, array( $this, 'on_settings_updated' ), 10, 2 );
		add_filter( 'adev_otp_translate_setting', array( $this, 'translate_value' ), 10, 2 );
	}

	/**
	 * Honor `lang` from frontend AJAX so OTP messages match Polylang language.
	 */
	public function bootstrap_ajax_language(): void {
		if ( ! function_exists( 'PLL' ) || ! wp_doing_ajax() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Language switch only; actions verify nonce.
		$lang = isset( $_REQUEST['lang'] ) ? sanitize_key( wp_unslash( $_REQUEST['lang'] ) ) : '';
		if ( '' === $lang ) {
			return;
		}

		$pll = PLL();
		if ( ! is_object( $pll ) || ! isset( $pll->model ) || ! method_exists( $pll->model, 'get_language' ) ) {
			return;
		}

		$language = $pll->model->get_language( $lang );
		if ( $language ) {
			$pll->curlang = $language;
			if ( isset( $language->locale ) && is_string( $language->locale ) && '' !== $language->locale ) {
				switch_to_locale( $language->locale );
			}
		}
	}

	/**
	 * Whether Polylang string API is available.
	 */
	public static function is_active(): bool {
		return function_exists( 'pll_register_string' ) && function_exists( 'pll__' );
	}

	/**
	 * Register configurable UI strings with Polylang.
	 */
	public function register_strings(): void {
		if ( ! self::is_active() ) {
			return;
		}

		$settings = get_option( Settings::OPTION, Settings::defaults() );
		if ( ! is_array( $settings ) ) {
			$settings = Settings::defaults();
		}

		foreach ( self::STRING_KEYS as $key ) {
			$value = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
			if ( '' === $value ) {
				continue;
			}
			pll_register_string(
				'adev_otp_' . $key,
				$value,
				'Adev OTP Login',
				false
			);
		}

		// Default SMS template text (optional per-language override).
		foreach ( array( 'kavenegar', 'melipayamak' ) as $gw ) {
			$msg = isset( $settings['gateways'][ $gw ]['message'] )
				? (string) $settings['gateways'][ $gw ]['message']
				: '';
			if ( '' === $msg ) {
				continue;
			}
			pll_register_string(
				'adev_otp_gw_msg_' . $gw,
				$msg,
				'Adev OTP Login',
				true
			);
		}
	}

	/**
	 * Re-register after settings save (new string values).
	 *
	 * @param mixed $old_value Previous option.
	 * @param mixed $value     New option.
	 */
	public function on_settings_updated( $old_value, $value ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		$this->register_strings();
	}

	/**
	 * Translate a setting value via Polylang when available.
	 *
	 * @param string $value Raw setting value.
	 * @param string $key   Setting key.
	 */
	public function translate_value( string $value, string $key ): string {
		unset( $key );
		if ( '' === $value || ! self::is_active() ) {
			return $value;
		}
		return pll__( $value );
	}

	/**
	 * Migrate stored Persian button defaults to English so gettext works.
	 */
	public static function migrate_legacy_defaults(): void {
		$settings = get_option( Settings::OPTION, false );
		if ( ! is_array( $settings ) ) {
			return;
		}

		$changed = false;
		foreach ( self::STRING_KEYS as $key ) {
			if ( empty( $settings[ $key ] ) || ! is_string( $settings[ $key ] ) ) {
				continue;
			}
			$current = $settings[ $key ];
			if ( isset( self::LEGACY_BUTTONS[ $current ] ) ) {
				$settings[ $key ] = self::LEGACY_BUTTONS[ $current ];
				$changed          = true;
			}
		}

		// Legacy Persian default SMS body.
		$legacy_sms  = "\u{06A9}\u{062F} \u{062A}\u{0623}\u{06CC}\u{06CC}\u{062F} \u{0634}\u{0645}\u{0627}: {OTP}"; // کد تأیید شما: {OTP}
		$english_sms = 'Your verification code: {OTP}';
		foreach ( array( 'kavenegar', 'melipayamak' ) as $gw ) {
			if (
				isset( $settings['gateways'][ $gw ]['message'] )
				&& $legacy_sms === $settings['gateways'][ $gw ]['message']
			) {
				$settings['gateways'][ $gw ]['message'] = $english_sms;
				$changed = true;
			}
		}

		if ( $changed ) {
			update_option( Settings::OPTION, $settings );
		}
	}
}

<?php
/**
 * Text domain loading and locale helpers.
 */

namespace Adev_OTP\I18n;

defined( 'ABSPATH' ) || exit;

class Textdomain {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'load' ), 0 );
		add_filter( 'plugin_locale', array( $this, 'filter_plugin_locale' ), 10, 2 );
	}

	/**
	 * Load plugin translations (bundled + WP.org language packs).
	 */
	public function load(): void {
		load_plugin_textdomain(
			'adev-otp-login',
			false,
			dirname( ADEV_OTP_BASENAME ) . '/languages'
		);
	}

	/**
	 * Align plugin locale with Polylang current language when present.
	 *
	 * Polylang already filters `locale` / `determine_locale`; this ensures
	 * language packs for fa_IR / en_US / ar resolve when slug differs.
	 *
	 * @param string $locale      Current locale.
	 * @param string $domain Text domain.
	 */
	public function filter_plugin_locale( string $locale, string $domain ): string {
		if ( 'adev-otp-login' !== $domain ) {
			return $locale;
		}

		if ( ! function_exists( 'pll_current_language' ) ) {
			return $locale;
		}

		$slug = pll_current_language( 'slug' );
		if ( ! is_string( $slug ) || '' === $slug ) {
			return $locale;
		}

		$map = array(
			'fa'    => 'fa_IR',
			'en'    => 'en_US',
			'ar'    => 'ar',
			'fa_IR' => 'fa_IR',
			'en_US' => 'en_US',
		);

		return isset( $map[ $slug ] ) ? $map[ $slug ] : $locale;
	}
}

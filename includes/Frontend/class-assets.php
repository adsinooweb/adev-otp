<?php
/**
 * Frontend / admin-adjacent asset loader for public forms.
 */

namespace Adev_OTP\Frontend;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Plugin;

class Assets {

	/**
	 * Whether assets should load on this request.
	 *
	 * @var bool
	 */
	private static bool $force = false;

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Force enqueue (shortcodes / modal).
	 */
	public static function force(): void {
		self::$force = true;
	}

	/**
	 * Enqueue CSS/JS.
	 */
	public function enqueue(): void {
		if ( ! Plugin::get_setting( 'enabled', 1 ) ) {
			return;
		}

		$load = self::$force
			|| ( function_exists( 'is_account_page' ) && is_account_page() )
			|| ( function_exists( 'is_checkout' ) && is_checkout() )
			|| (bool) Plugin::get_setting( 'replace_wp_login', 0 );

		/**
		 * Filter whether to load frontend OTP assets.
		 *
		 * @param bool $load Load assets.
		 */
		$load = (bool) apply_filters( 'adev_otp_enqueue_assets', $load );

		if ( ! $load && ! self::$force ) {
			// Still register for late shortcode enqueue.
			$this->register();
			return;
		}

		$this->register();
		wp_enqueue_style( 'adev-otp-frontend' );
		wp_enqueue_script( 'adev-otp-frontend' );
		self::add_button_color_css();
	}

	/**
	 * Register handles.
	 */
	private function register(): void {
		wp_register_style(
			'adev-otp-frontend',
			ADEV_OTP_URL . 'assets/css/frontend.css',
			array(),
			ADEV_OTP_VERSION
		);

		wp_register_script(
			'adev-otp-frontend',
			ADEV_OTP_URL . 'assets/js/frontend.js',
			array(),
			ADEV_OTP_VERSION,
			true
		);

		$lang = '';
		if ( function_exists( 'pll_current_language' ) ) {
			$current = pll_current_language( 'slug' );
			$lang    = is_string( $current ) ? $current : '';
		}

		wp_localize_script(
			'adev-otp-frontend',
			'adevOtp',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'adev_otp_front' ),
				'lang'         => $lang,
				'otpLength'    => (int) Plugin::get_setting( 'otp_length', 5 ),
				'cooldown'     => (int) Plugin::get_setting( 'resend_cooldown', 60 ),
				'loginMode'    => (string) Plugin::get_setting( 'login_mode', 'otp' ),
				'requireName'  => (int) Plugin::get_setting( 'require_name', 1 ),
				'requireEmail' => (int) Plugin::get_setting( 'require_email', 0 ),
				'honeypot'     => (int) Plugin::get_setting( 'honeypot', 1 ),
				'i18n'         => array(
					'sending'   => __( 'Sending…', 'adev-otp-login' ),
					'verifying' => __( 'Verifying…', 'adev-otp-login' ),
					/* translators: %d: seconds remaining until resend is allowed */
					'resendIn'  => __( 'Resend in %d seconds', 'adev-otp-login' ),
					'resend'    => __( 'Resend code', 'adev-otp-login' ),
					'error'     => __( 'An error occurred.', 'adev-otp-login' ),
					'phoneReq'  => __( 'Please enter your mobile number.', 'adev-otp-login' ),
					'codeReq'   => __( 'Please enter the verification code.', 'adev-otp-login' ),
				),
			)
		);
	}

	/**
	 * Inline CSS for configurable button colors.
	 */
	private static function add_button_color_css(): void {
		$bg   = (string) Plugin::get_setting( 'btn_bg_color', '#0aa74b' );
		$text = (string) Plugin::get_setting( 'btn_text_color', '#ffffff' );
		$bg   = sanitize_hex_color( $bg ) ?: '#0aa74b';
		$text = sanitize_hex_color( $text ) ?: '#ffffff';

		$css = sprintf(
			':root{--adev-otp-btn-bg:%1$s;--adev-otp-btn-text:%2$s;}' .
			'.adev-otp-form__btn,.adev-otp-open-modal{background:%1$s!important;color:%2$s!important;}' .
			'.adev-otp-form__btn:hover,.adev-otp-open-modal:hover{filter:brightness(0.92);}' .
			'.adev-otp-form__link{color:%1$s!important;}' .
			'.adev-otp-tabs__tab.is-active,.adev-otp-modal__tab.is-active{background:%1$s!important;border-color:%1$s!important;color:%2$s!important;box-shadow:0 2px 8px color-mix(in srgb,%1$s 30%%,transparent);}' .
			'.adev-otp-form__input:focus{outline-color:%1$s;border-color:%1$s;}',
			$bg,
			$text
		);

		wp_add_inline_style( 'adev-otp-frontend', $css );
	}

	/**
	 * Ensure assets printed when shortcode renders.
	 */
	public static function ensure(): void {
		self::$force = true;
		$instance    = new self();
		$instance->register();
		wp_enqueue_style( 'adev-otp-frontend' );
		wp_enqueue_script( 'adev-otp-frontend' );
		self::add_button_color_css();
	}
}

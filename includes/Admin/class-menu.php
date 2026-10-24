<?php
/**
 * Admin menu and settings pages.
 */

namespace Adev_OTP\Admin;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Gateways\Gateway_Registry;
use Adev_OTP\Plugin;

class Menu {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Add top-level menu.
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'OTP Login', 'adev-otp-login' ),
			__( 'OTP Login', 'adev-otp-login' ),
			'manage_options',
			'adev-otp-login',
			array( $this, 'render_settings' ),
			'dashicons-smartphone',
			58
		);

		add_submenu_page(
			'adev-otp-login',
			__( 'Settings', 'adev-otp-login' ),
			__( 'Settings', 'adev-otp-login' ),
			'manage_options',
			'adev-otp-login',
			array( $this, 'render_settings' )
		);

		add_submenu_page(
			'adev-otp-login',
			__( 'Logs', 'adev-otp-login' ),
			__( 'Logs', 'adev-otp-login' ),
			'manage_options',
			'adev-otp-logs',
			array( $this, 'render_logs' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current hook.
	 */
	public function enqueue( string $hook ): void {
		if ( false === strpos( $hook, 'adev-otp' ) ) {
			return;
		}

		wp_enqueue_style(
			'adev-otp-admin',
			ADEV_OTP_URL . 'assets/css/admin.css',
			array(),
			ADEV_OTP_VERSION
		);

		wp_enqueue_script(
			'adev-otp-admin',
			ADEV_OTP_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			ADEV_OTP_VERSION,
			true
		);

		wp_localize_script(
			'adev-otp-admin',
			'adevOtpAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'adev_otp_admin' ),
				'i18n'    => array(
					'sending'    => __( 'Sending…', 'adev-otp-login' ),
					'success'    => __( 'Sent successfully.', 'adev-otp-login' ),
					'error'      => __( 'Sending failed.', 'adev-otp-login' ),
					'copied'     => __( 'Copied', 'adev-otp-login' ),
					'testSend'   => __( 'Send test SMS', 'adev-otp-login' ),
					'done'       => __( 'Done.', 'adev-otp-login' ),
					'copyPrompt' => __( 'Copy shortcode:', 'adev-otp-login' ),
				),
			)
		);
	}

	/**
	 * Render settings page with tabs.
	 */
	public function render_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab      = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs     = array(
			'general'     => __( 'General', 'adev-otp-login' ),
			'gateway'     => __( 'SMS Gateway', 'adev-otp-login' ),
			'forms'       => __( 'Forms', 'adev-otp-login' ),
			'shortcodes'  => __( 'Shortcodes', 'adev-otp-login' ),
			'woocommerce' => __( 'WooCommerce', 'adev-otp-login' ),
			'security'    => __( 'Security', 'adev-otp-login' ),
		);
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'general';
		}

		$settings = get_option( Settings::OPTION, Settings::defaults() );
		$settings = wp_parse_args( is_array( $settings ) ? $settings : array(), Settings::defaults() );
		$gateways = Gateway_Registry::instance()->all();

		include ADEV_OTP_PATH . 'templates/admin-settings.php';
	}

	/**
	 * Render logs page.
	 */
	public function render_logs(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		( new Logs_Page() )->render();
	}
}

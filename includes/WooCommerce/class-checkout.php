<?php
/**
 * WooCommerce checkout phone verification & force login.
 */

namespace Adev_OTP\WooCommerce;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Auth\Phone;
use Adev_OTP\Auth\Rate_Limiter;
use Adev_OTP\Frontend\Assets;
use Adev_OTP\Plugin;

class Checkout {

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		if ( Plugin::get_setting( 'wc_checkout_force_login', 0 ) ) {
			add_action( 'woocommerce_before_checkout_form', array( $this, 'render_force_login' ), 5 );
			add_filter( 'woocommerce_checkout_registration_required', array( $this, 'force_registration_required' ), 99 );
			add_filter( 'woocommerce_checkout_registration_enabled', array( $this, 'disable_guest_registration_form' ), 99 );
			add_filter( 'woocommerce_checkout_must_be_logged_in_message', '__return_empty_string' );
		}

		if ( ! Plugin::get_setting( 'wc_checkout_verify', 0 ) ) {
			return;
		}

		add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'render_verify_ui' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_phone' ) );
	}

	/**
	 * Whether guest must authenticate on checkout.
	 */
	private function should_force_login(): bool {
		return (bool) Plugin::get_setting( 'wc_checkout_force_login', 0 )
			&& (bool) Plugin::get_setting( 'enabled', 1 )
			&& ! is_user_logged_in()
			&& function_exists( 'is_checkout' )
			&& is_checkout()
			&& ! is_wc_endpoint_url( 'order-pay' )
			&& ! is_wc_endpoint_url( 'order-received' );
	}

	/**
	 * Force WooCommerce to block checkout for guests.
	 *
	 * @param bool $required Registration required.
	 */
	public function force_registration_required( bool $required ): bool {
		return $this->should_force_login() ? true : $required;
	}

	/**
	 * Hide WooCommerce account-creation fields; OTP form handles auth.
	 *
	 * @param bool $enabled Registration enabled.
	 */
	public function disable_guest_registration_form( bool $enabled ): bool {
		return $this->should_force_login() ? false : $enabled;
	}

	/**
	 * Show OTP login/register tabs instead of checkout for guests.
	 */
	public function render_force_login(): void {
		if ( ! $this->should_force_login() ) {
			return;
		}

		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );

		Assets::ensure();
		$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
		$active_tab   = 'login';
		?>
		<div
			class="adev-otp-checkout-login"
			data-adev-otp-checkout-login
			data-redirect="<?php echo esc_url( $checkout_url ); ?>"
			dir="rtl"
		>
			<h2 class="adev-otp-checkout-login__title">
				<?php esc_html_e( 'Login / Register to continue checkout', 'adev-otp-login' ); ?>
			</h2>
			<p class="adev-otp-checkout-login__desc">
				<?php esc_html_e( 'To pay for your order, log in or register with your mobile number first. After verifying the code, you will return to checkout.', 'adev-otp-login' ); ?>
			</p>
			<?php include ADEV_OTP_PATH . 'templates/tabs.php'; ?>
		</div>
		<?php
	}

	/**
	 * OTP UI under billing phone.
	 */
	public function render_verify_ui(): void {
		Assets::ensure();
		$required = (int) Plugin::get_setting( 'wc_checkout_required', 0 );
		?>
		<div class="adev-otp-checkout" data-adev-otp-checkout data-required="<?php echo esc_attr( (string) $required ); ?>">
			<p class="form-row form-row-wide">
				<label><?php esc_html_e( 'Verify mobile via SMS', 'adev-otp-login' ); ?>
					<?php if ( $required ) : ?><abbr class="required" title="required">*</abbr><?php endif; ?>
				</label>
				<button type="button" class="button adev-otp-checkout__send" data-adev-checkout-send>
					<?php echo esc_html( Plugin::get_translated_setting( 'btn_send_otp' ) ); ?>
				</button>
			</p>
			<p class="form-row form-row-wide adev-otp-checkout__code-row" hidden>
				<input type="text" class="input-text" inputmode="numeric" autocomplete="one-time-code" data-adev-checkout-code placeholder="<?php esc_attr_e( 'Verification code', 'adev-otp-login' ); ?>" />
				<button type="button" class="button" data-adev-checkout-verify>
					<?php echo esc_html( Plugin::get_translated_setting( 'btn_verify' ) ); ?>
				</button>
			</p>
			<p class="adev-otp-msg" data-adev-checkout-msg hidden></p>
			<input type="hidden" name="adev_otp_checkout_verified" value="0" data-adev-checkout-flag />
		</div>
		<?php
	}

	/**
	 * Validate verified phone when required.
	 */
	public function validate(): void {
		if ( ! Plugin::get_setting( 'wc_checkout_required', 0 ) ) {
			return;
		}

		$billing = isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$phone   = Phone::normalize( $billing );
		$flag    = isset( $_POST['adev_otp_checkout_verified'] ) ? sanitize_text_field( wp_unslash( $_POST['adev_otp_checkout_verified'] ) ) : '0'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$session_ok = false;
		if ( function_exists( 'WC' ) && WC()->session ) {
			$verified = WC()->session->get( 'adev_otp_phone_verified' );
			$session_ok = $verified && Phone::normalize( (string) $verified ) === $phone;
		}

		$transient_ok = (bool) get_transient( 'adev_otp_chk_' . md5( Rate_Limiter::client_ip() . $phone ) );

		if ( '1' !== $flag && ! $session_ok && ! $transient_ok ) {
			wc_add_notice( __( 'Please verify your mobile number with the SMS code.', 'adev-otp-login' ), 'error' );
		}
	}

	/**
	 * Persist phone meta on order/user.
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_phone( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$phone = Phone::normalize( (string) $order->get_billing_phone() );
		if ( ! $phone ) {
			return;
		}
		$user_id = $order->get_user_id();
		if ( $user_id ) {
			Phone::save_for_user( $user_id, $phone );
		}
	}
}

<?php
/**
 * WooCommerce My Account integration.
 */

namespace Adev_OTP\WooCommerce;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Auth\Phone;
use Adev_OTP\Frontend\Assets;
use Adev_OTP\Plugin;

class Account {

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		if ( ! Plugin::get_setting( 'enabled', 1 ) ) {
			return;
		}

		add_action( 'woocommerce_before_customer_login_form', array( $this, 'render_otp_login' ) );
		add_action( 'woocommerce_edit_account_form', array( $this, 'account_phone_field' ) );
		add_action( 'woocommerce_save_account_details', array( $this, 'save_account_phone' ) );
	}

	/**
	 * Inline OTP tabs (login / register / forgot) — no popup.
	 */
	public function render_otp_login(): void {
		Assets::ensure();
		$active_tab = 'login';
		echo '<div class="adev-otp-wc-account ajand-account-otp">';
		echo '<h2>' . esc_html__( 'Login with mobile', 'adev-otp-login' ) . '</h2>';
		include ADEV_OTP_PATH . 'templates/tabs.php';
		echo '</div>';
	}

	/**
	 * Phone field on edit account.
	 */
	public function account_phone_field(): void {
		$user_id = get_current_user_id();
		$phone   = (string) get_user_meta( $user_id, '_adev_otp_phone', true );
		?>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="adev_otp_phone"><?php esc_html_e( 'Mobile number', 'adev-otp-login' ); ?></label>
			<input type="tel" class="woocommerce-Input input-text" name="adev_otp_phone" id="adev_otp_phone" value="<?php echo esc_attr( $phone ); ?>" dir="ltr" />
		</p>
		<?php
	}

	/**
	 * Save phone from account form.
	 *
	 * @param int $user_id User ID.
	 */
	public function save_account_phone( int $user_id ): void {
		if ( ! isset( $_POST['adev_otp_phone'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		$phone = Phone::normalize( sanitize_text_field( wp_unslash( $_POST['adev_otp_phone'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $phone ) {
			$other = Phone::find_user( $phone );
			if ( $other && (int) $other->ID !== $user_id ) {
				return;
			}
			Phone::save_for_user( $user_id, $phone );
		}
	}
}

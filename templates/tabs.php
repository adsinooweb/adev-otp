<?php
/**
 * Inline auth tabs (login / register) — no modal, no forgot.
 */

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Frontend\Forms;
use Adev_OTP\Plugin;

$btn_login    = Plugin::get_translated_setting( 'btn_login' );
$btn_register = Plugin::get_translated_setting( 'btn_register' );
$active       = isset( $active_tab ) && in_array( $active_tab, array( 'login', 'register' ), true )
	? $active_tab
	: 'login';
?>
<div class="adev-otp-tabs" data-adev-otp-tabs dir="rtl">
	<div class="adev-otp-tabs__nav" role="tablist">
		<button type="button" class="adev-otp-tabs__tab<?php echo 'login' === $active ? ' is-active' : ''; ?>" data-tab="login" role="tab" aria-selected="<?php echo 'login' === $active ? 'true' : 'false'; ?>">
			<?php echo esc_html( $btn_login ); ?>
		</button>
		<button type="button" class="adev-otp-tabs__tab<?php echo 'register' === $active ? ' is-active' : ''; ?>" data-tab="register" role="tab" aria-selected="<?php echo 'register' === $active ? 'true' : 'false'; ?>">
			<?php echo esc_html( $btn_register ); ?>
		</button>
	</div>
	<div class="adev-otp-tabs__panels">
		<div class="adev-otp-tabs__panel<?php echo 'login' === $active ? ' is-active' : ''; ?>" data-panel="login" role="tabpanel" <?php echo 'login' === $active ? '' : 'hidden'; ?>>
			<p class="adev-otp-tabs__hint"><?php esc_html_e( 'Enter your mobile number to receive a verification SMS.', 'adev-otp-login' ); ?></p>
			<?php Forms::render( 'login' ); ?>
		</div>
		<div class="adev-otp-tabs__panel<?php echo 'register' === $active ? ' is-active' : ''; ?>" data-panel="register" role="tabpanel" <?php echo 'register' === $active ? '' : 'hidden'; ?>>
			<p class="adev-otp-tabs__hint"><?php esc_html_e( 'Enter your mobile number to create a new account.', 'adev-otp-login' ); ?></p>
			<?php Forms::render( 'register' ); ?>
		</div>
	</div>
</div>

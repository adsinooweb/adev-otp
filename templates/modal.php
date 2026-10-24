<?php
/**
 * Auth modal shell (login / register only).
 */

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Frontend\Forms;
use Adev_OTP\Plugin;

$btn_login    = Plugin::get_translated_setting( 'btn_login' );
$btn_register = Plugin::get_translated_setting( 'btn_register' );
?>
<div class="adev-otp-modal" data-adev-otp-modal hidden dir="rtl">
	<div class="adev-otp-modal__backdrop" data-adev-otp-close></div>
	<div class="adev-otp-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Login and register', 'adev-otp-login' ); ?>">
		<button type="button" class="adev-otp-modal__close" data-adev-otp-close aria-label="<?php esc_attr_e( 'Close', 'adev-otp-login' ); ?>">&times;</button>
		<div class="adev-otp-modal__tabs adev-otp-tabs__nav">
			<button type="button" class="adev-otp-modal__tab adev-otp-tabs__tab is-active" data-tab="login"><?php echo esc_html( $btn_login ); ?></button>
			<button type="button" class="adev-otp-modal__tab adev-otp-tabs__tab" data-tab="register"><?php echo esc_html( $btn_register ); ?></button>
		</div>
		<div class="adev-otp-modal__panels">
			<div class="adev-otp-modal__panel is-active" data-panel="login">
				<?php Forms::render( 'login' ); ?>
			</div>
			<div class="adev-otp-modal__panel" data-panel="register" hidden>
				<?php Forms::render( 'register' ); ?>
			</div>
		</div>
	</div>
</div>

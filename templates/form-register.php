<?php
/**
 * Register form template.
 *
 * @var array $settings
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="adev-otp-form" data-adev-otp-form data-purpose="register" dir="rtl">
	<div class="adev-otp-form__step" data-step="phone">
		<?php if ( ! empty( $settings['require_name'] ) ) : ?>
			<label class="adev-otp-form__label"><?php esc_html_e( 'Name', 'adev-otp-login' ); ?></label>
			<input type="text" class="adev-otp-form__input" data-field="name" autocomplete="name" />
		<?php endif; ?>
		<?php if ( ! empty( $settings['require_email'] ) ) : ?>
			<label class="adev-otp-form__label"><?php esc_html_e( 'Email', 'adev-otp-login' ); ?></label>
			<input type="email" class="adev-otp-form__input" data-field="email" dir="ltr" autocomplete="email" />
		<?php endif; ?>
		<label class="adev-otp-form__label"><?php esc_html_e( 'Mobile number', 'adev-otp-login' ); ?></label>
		<input type="tel" class="adev-otp-form__input" data-field="phone" dir="ltr" inputmode="tel" placeholder="0912xxxxxxx" autocomplete="tel" />
		<button type="button" class="adev-otp-form__btn" data-action="send"><?php echo esc_html( (string) $settings['btn_send_otp'] ); ?></button>
	</div>
	<div class="adev-otp-form__step" data-step="code" hidden>
		<label class="adev-otp-form__label"><?php esc_html_e( 'Verification code', 'adev-otp-login' ); ?></label>
		<input type="text" class="adev-otp-form__input" data-field="code" dir="ltr" inputmode="numeric" autocomplete="one-time-code" maxlength="8" />
		<button type="button" class="adev-otp-form__btn" data-action="verify"><?php echo esc_html( (string) $settings['btn_verify'] ); ?></button>
		<button type="button" class="adev-otp-form__link" data-action="resend" disabled><?php esc_html_e( 'Resend code', 'adev-otp-login' ); ?></button>
	</div>
	<input type="text" name="adev_hp" value="" class="adev-otp-hp" tabindex="-1" autocomplete="off" aria-hidden="true" />
	<p class="adev-otp-form__msg" data-msg hidden></p>
</div>

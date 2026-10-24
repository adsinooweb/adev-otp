<?php
/**
 * Admin settings template.
 *
 * @var string $tab
 * @var array  $tabs
 * @var array  $settings
 * @var array  $gateways
 */

defined( 'ABSPATH' ) || exit;

$option = \Adev_OTP\Admin\Settings::OPTION;
?>
<div class="wrap adev-otp-admin" dir="rtl">
	<h1><?php esc_html_e( 'OTP Login Settings', 'adev-otp-login' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=adev-otp-login&tab=' . $slug ) ); ?>"
				class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<form method="post" action="options.php" class="adev-otp-admin__form">
		<?php settings_fields( 'adev_otp_settings_group' ); ?>

		<?php if ( 'general' === $tab ) : ?>
			<table class="form-table" role="presentation">
				<tr>
					<th><?php esc_html_e( 'Enable', 'adev-otp-login' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="<?php echo esc_attr( $option ); ?>[enabled]" value="0" />
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
							<?php esc_html_e( 'Plugin is enabled', 'adev-otp-login' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Replace wp-login', 'adev-otp-login' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="<?php echo esc_attr( $option ); ?>[replace_wp_login]" value="0" />
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[replace_wp_login]" value="1" <?php checked( ! empty( $settings['replace_wp_login'] ) ); ?> />
							<?php esc_html_e( 'Replace the WordPress login page with the OTP form', 'adev-otp-login' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><label for="otp_length"><?php esc_html_e( 'OTP length', 'adev-otp-login' ); ?></label></th>
					<td>
						<input type="number" min="4" max="8" id="otp_length" name="<?php echo esc_attr( $option ); ?>[otp_length]" value="<?php echo esc_attr( (string) $settings['otp_length'] ); ?>" class="small-text" />
					</td>
				</tr>
				<tr>
					<th><label for="otp_ttl"><?php esc_html_e( 'Code TTL (seconds)', 'adev-otp-login' ); ?></label></th>
					<td>
						<input type="number" min="60" max="600" id="otp_ttl" name="<?php echo esc_attr( $option ); ?>[otp_ttl]" value="<?php echo esc_attr( (string) $settings['otp_ttl'] ); ?>" class="small-text" />
					</td>
				</tr>
				<tr>
					<th><label for="login_mode"><?php esc_html_e( 'Login mode', 'adev-otp-login' ); ?></label></th>
					<td>
						<select id="login_mode" name="<?php echo esc_attr( $option ); ?>[login_mode]">
							<option value="otp" <?php selected( $settings['login_mode'], 'otp' ); ?>><?php esc_html_e( 'OTP only', 'adev-otp-login' ); ?></option>
							<option value="otp_password" <?php selected( $settings['login_mode'], 'otp_password' ); ?>><?php esc_html_e( 'OTP + password', 'adev-otp-login' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
		<?php endif; ?>

		<?php if ( 'gateway' === $tab ) : ?>
			<table class="form-table" role="presentation">
				<tr>
					<th><label for="active_gateway"><?php esc_html_e( 'Active gateway', 'adev-otp-login' ); ?></label></th>
					<td>
						<select id="active_gateway" name="<?php echo esc_attr( $option ); ?>[active_gateway]" data-adev-gateway-select>
							<?php foreach ( $gateways as $gw ) : ?>
								<option value="<?php echo esc_attr( $gw->get_id() ); ?>" <?php selected( $settings['active_gateway'], $gw->get_id() ); ?>>
									<?php echo esc_html( $gw->get_name() ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>

			<?php foreach ( $gateways as $gw ) : ?>
				<?php
				$gid    = $gw->get_id();
				$gcfg   = isset( $settings['gateways'][ $gid ] ) && is_array( $settings['gateways'][ $gid ] )
					? $settings['gateways'][ $gid ]
					: array();
				$fields = $gw->get_settings_fields();
				?>
				<div class="adev-otp-gateway-panel" data-gateway="<?php echo esc_attr( $gid ); ?>" <?php echo $settings['active_gateway'] === $gid ? '' : 'hidden'; ?>>
					<h2><?php echo esc_html( $gw->get_name() ); ?></h2>
					<table class="form-table" role="presentation">
						<?php foreach ( $fields as $field ) : ?>
							<?php
							$key  = $field['key'];
							$type = isset( $field['type'] ) ? $field['type'] : 'text';
							$val  = isset( $gcfg[ $key ] ) ? $gcfg[ $key ] : '';
							$name = $option . '[gateways][' . $gid . '][' . $key . ']';
							?>
							<tr>
								<th><label><?php echo esc_html( $field['label'] ); ?></label></th>
								<td>
									<?php if ( 'select' === $type && ! empty( $field['options'] ) ) : ?>
										<select name="<?php echo esc_attr( $name ); ?>">
											<?php foreach ( $field['options'] as $ok => $ol ) : ?>
												<option value="<?php echo esc_attr( $ok ); ?>" <?php selected( $val, $ok ); ?>><?php echo esc_html( $ol ); ?></option>
											<?php endforeach; ?>
										</select>
									<?php elseif ( 'textarea' === $type ) : ?>
										<textarea name="<?php echo esc_attr( $name ); ?>" rows="3" class="large-text"><?php echo esc_textarea( (string) $val ); ?></textarea>
									<?php else : ?>
										<input type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $val ); ?>" class="regular-text" dir="ltr" autocomplete="off" />
									<?php endif; ?>
									<?php if ( ! empty( $field['desc'] ) ) : ?>
										<p class="description"><?php echo esc_html( $field['desc'] ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
			<?php endforeach; ?>

			<hr />
			<h2><?php esc_html_e( 'Send test', 'adev-otp-login' ); ?></h2>
			<p>
				<input type="tel" id="adev-otp-test-phone" class="regular-text" dir="ltr" placeholder="0912xxxxxxx" />
				<button type="button" class="button button-secondary" id="adev-otp-test-send"><?php esc_html_e( 'Send test SMS', 'adev-otp-login' ); ?></button>
				<button type="button" class="button" id="adev-otp-clear-limits"><?php esc_html_e( 'Clear send limits', 'adev-otp-login' ); ?></button>
			</p>
			<p id="adev-otp-test-result" class="adev-otp-test-result" hidden></p>
			<p class="description"><?php esc_html_e( 'Save settings first, then run a test. If the daily limit is reached, click Clear send limits.', 'adev-otp-login' ); ?></p>
		<?php endif; ?>

		<?php if ( 'forms' === $tab ) : ?>
			<table class="form-table" role="presentation">
				<tr>
					<th><?php esc_html_e( 'Name on registration', 'adev-otp-login' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="<?php echo esc_attr( $option ); ?>[require_name]" value="0" />
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[require_name]" value="1" <?php checked( ! empty( $settings['require_name'] ) ); ?> />
							<?php esc_html_e( 'Required', 'adev-otp-login' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Email on registration', 'adev-otp-login' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="<?php echo esc_attr( $option ); ?>[require_email]" value="0" />
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[require_email]" value="1" <?php checked( ! empty( $settings['require_email'] ) ); ?> />
							<?php esc_html_e( 'Required', 'adev-otp-login' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Login button text', 'adev-otp-login' ); ?></label></th>
					<td><input type="text" name="<?php echo esc_attr( $option ); ?>[btn_login]" value="<?php echo esc_attr( (string) $settings['btn_login'] ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Register button text', 'adev-otp-login' ); ?></label></th>
					<td><input type="text" name="<?php echo esc_attr( $option ); ?>[btn_register]" value="<?php echo esc_attr( (string) $settings['btn_register'] ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Send code button text', 'adev-otp-login' ); ?></label></th>
					<td><input type="text" name="<?php echo esc_attr( $option ); ?>[btn_send_otp]" value="<?php echo esc_attr( (string) $settings['btn_send_otp'] ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Verify button text', 'adev-otp-login' ); ?></label></th>
					<td><input type="text" name="<?php echo esc_attr( $option ); ?>[btn_verify]" value="<?php echo esc_attr( (string) $settings['btn_verify'] ); ?>" class="regular-text" /></td>
				</tr>
				<?php if ( function_exists( 'pll_register_string' ) ) : ?>
				<tr>
					<th><?php esc_html_e( 'Polylang', 'adev-otp-login' ); ?></th>
					<td>
						<p class="description">
							<?php esc_html_e( 'Button labels are registered for Polylang String Translations (Languages → String translations → Adev OTP Login). Built-in UI strings follow the current language (fa_IR, en_US, ar).', 'adev-otp-login' ); ?>
						</p>
					</td>
				</tr>
				<?php endif; ?>
				<tr>
					<th><label for="btn_bg_color"><?php esc_html_e( 'Button background color', 'adev-otp-login' ); ?></label></th>
					<td>
						<input type="color" id="btn_bg_color" name="<?php echo esc_attr( $option ); ?>[btn_bg_color]" value="<?php echo esc_attr( (string) ( $settings['btn_bg_color'] ?? '#1a5f4a' ) ); ?>" />
						<code dir="ltr"><?php echo esc_html( (string) ( $settings['btn_bg_color'] ?? '#1a5f4a' ) ); ?></code>
					</td>
				</tr>
				<tr>
					<th><label for="btn_text_color"><?php esc_html_e( 'Button text color', 'adev-otp-login' ); ?></label></th>
					<td>
						<input type="color" id="btn_text_color" name="<?php echo esc_attr( $option ); ?>[btn_text_color]" value="<?php echo esc_attr( (string) ( $settings['btn_text_color'] ?? '#ffffff' ) ); ?>" />
						<code dir="ltr"><?php echo esc_html( (string) ( $settings['btn_text_color'] ?? '#ffffff' ) ); ?></code>
					</td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Redirect after login', 'adev-otp-login' ); ?></label></th>
					<td><input type="url" name="<?php echo esc_attr( $option ); ?>[redirect_login]" value="<?php echo esc_attr( (string) $settings['redirect_login'] ); ?>" class="regular-text" dir="ltr" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Redirect after registration', 'adev-otp-login' ); ?></label></th>
					<td><input type="url" name="<?php echo esc_attr( $option ); ?>[redirect_register]" value="<?php echo esc_attr( (string) $settings['redirect_register'] ); ?>" class="regular-text" dir="ltr" /></td>
				</tr>
			</table>
		<?php endif; ?>

		<?php if ( 'shortcodes' === $tab ) : ?>
			<?php
			$shortcodes = array(
				array(
					'code' => '[adev_otp_login]',
					'desc' => __( 'Login form with one-time SMS code', 'adev-otp-login' ),
				),
				array(
					'code' => '[adev_otp_register]',
					'desc' => __( 'Registration form with mobile verification', 'adev-otp-login' ),
				),
				array(
					'code' => '[adev_otp_forgot]',
					'desc' => __( 'Forgot / reset password form with OTP', 'adev-otp-login' ),
				),
				array(
					'code' => '[adev_otp_modal]',
					'desc' => __( 'Button that opens the login / register / forgot password modal', 'adev-otp-login' ),
				),
				array(
					'code' => '[adev_otp_modal label="Login"]',
					'desc' => __( 'Same modal with custom button text', 'adev-otp-login' ),
				),
				array(
					'code' => '[adev_otp_tabs]',
					'desc' => __( 'Tabbed login / register / forgot form (no popup)', 'adev-otp-login' ),
				),
			);
			?>
			<p class="description"><?php esc_html_e( 'Copy the shortcode and place it in a page, post, or widget.', 'adev-otp-login' ); ?></p>
			<table class="widefat striped adev-otp-shortcodes-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Shortcode', 'adev-otp-login' ); ?></th>
						<th><?php esc_html_e( 'Description', 'adev-otp-login' ); ?></th>
						<th style="width:100px"><?php esc_html_e( 'Action', 'adev-otp-login' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $shortcodes as $item ) : ?>
						<tr>
							<td><code class="adev-otp-shortcode-code" dir="ltr"><?php echo esc_html( $item['code'] ); ?></code></td>
							<td><?php echo esc_html( $item['desc'] ); ?></td>
							<td>
								<button type="button" class="button button-small adev-otp-copy-shortcode" data-code="<?php echo esc_attr( $item['code'] ); ?>">
									<?php esc_html_e( 'Copy', 'adev-otp-login' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( 'woocommerce' === $tab ) : ?>
			<table class="form-table" role="presentation">
				<tr>
					<th><?php esc_html_e( 'Verify mobile at checkout', 'adev-otp-login' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="<?php echo esc_attr( $option ); ?>[wc_checkout_verify]" value="0" />
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[wc_checkout_verify]" value="1" <?php checked( ! empty( $settings['wc_checkout_verify'] ) ); ?> />
							<?php esc_html_e( 'Show OTP verification on the checkout page', 'adev-otp-login' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Require verification', 'adev-otp-login' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="<?php echo esc_attr( $option ); ?>[wc_checkout_required]" value="0" />
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[wc_checkout_required]" value="1" <?php checked( ! empty( $settings['wc_checkout_required'] ) ); ?> />
							<?php esc_html_e( 'Do not allow placing an order without OTP verification', 'adev-otp-login' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Force login / register at checkout', 'adev-otp-login' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="<?php echo esc_attr( $option ); ?>[wc_checkout_force_login]" value="0" />
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[wc_checkout_force_login]" value="1" <?php checked( ! empty( $settings['wc_checkout_force_login'] ) ); ?> />
							<?php esc_html_e( 'Guests must log in or register with OTP before paying', 'adev-otp-login' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'On checkout, if the user is not logged in, the login/register form is shown and the page refreshes after code verification.', 'adev-otp-login' ); ?>
						</p>
					</td>
				</tr>
			</table>
		<?php endif; ?>

		<?php if ( 'security' === $tab ) : ?>
			<table class="form-table" role="presentation">
				<tr>
					<th><label><?php esc_html_e( 'Max code attempts', 'adev-otp-login' ); ?></label></th>
					<td><input type="number" min="1" max="20" name="<?php echo esc_attr( $option ); ?>[max_attempts]" value="<?php echo esc_attr( (string) $settings['max_attempts'] ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Resend cooldown (seconds)', 'adev-otp-login' ); ?></label></th>
					<td><input type="number" min="30" max="600" name="<?php echo esc_attr( $option ); ?>[resend_cooldown]" value="<?php echo esc_attr( (string) $settings['resend_cooldown'] ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Daily limit per number', 'adev-otp-login' ); ?></label></th>
					<td><input type="number" min="1" max="100" name="<?php echo esc_attr( $option ); ?>[daily_limit_phone]" value="<?php echo esc_attr( (string) $settings['daily_limit_phone'] ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Daily limit per IP', 'adev-otp-login' ); ?></label></th>
					<td><input type="number" min="1" max="200" name="<?php echo esc_attr( $option ); ?>[daily_limit_ip]" value="<?php echo esc_attr( (string) $settings['daily_limit_ip'] ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Phone blacklist', 'adev-otp-login' ); ?></label></th>
					<td>
						<textarea name="<?php echo esc_attr( $option ); ?>[phone_blacklist]" rows="4" class="large-text" dir="ltr"><?php echo esc_textarea( (string) $settings['phone_blacklist'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Separate numbers with commas or new lines.', 'adev-otp-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Honeypot', 'adev-otp-login' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="<?php echo esc_attr( $option ); ?>[honeypot]" value="0" />
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[honeypot]" value="1" <?php checked( ! empty( $settings['honeypot'] ) ); ?> />
							<?php esc_html_e( 'Hidden anti-bot field', 'adev-otp-login' ); ?>
						</label>
					</td>
				</tr>
			</table>
		<?php endif; ?>

		<?php if ( 'shortcodes' !== $tab ) : ?>
			<?php submit_button( __( 'Save settings', 'adev-otp-login' ) ); ?>
		<?php endif; ?>
	</form>
</div>

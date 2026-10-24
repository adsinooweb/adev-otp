<?php
/**
 * Shortcodes.
 */

namespace Adev_OTP\Frontend;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_shortcode( 'adev_otp_login', array( $this, 'login' ) );
		add_shortcode( 'adev_otp_register', array( $this, 'register' ) );
		add_shortcode( 'adev_otp_modal', array( $this, 'modal' ) );
		add_shortcode( 'adev_otp_forgot', array( $this, 'forgot' ) );
		add_shortcode( 'adev_otp_tabs', array( $this, 'tabs' ) );
	}

	/**
	 * Login form shortcode.
	 *
	 * @param array|string $atts Attributes.
	 */
	public function login( $atts = array() ): string {
		Assets::ensure();
		ob_start();
		Forms::render( 'login' );
		return (string) ob_get_clean();
	}

	/**
	 * Register form shortcode.
	 *
	 * @param array|string $atts Attributes.
	 */
	public function register( $atts = array() ): string {
		Assets::ensure();
		ob_start();
		Forms::render( 'register' );
		return (string) ob_get_clean();
	}

	/**
	 * Forgot form shortcode.
	 *
	 * @param array|string $atts Attributes.
	 */
	public function forgot( $atts = array() ): string {
		Assets::ensure();
		ob_start();
		Forms::render( 'forgot' );
		return (string) ob_get_clean();
	}

	/**
	 * Modal trigger + markup.
	 *
	 * @param array|string $atts Attributes.
	 */
	public function modal( $atts = array() ): string {
		Assets::ensure();
		$atts = shortcode_atts(
			array(
				'label' => __( 'Login / Register', 'adev-otp-login' ),
			),
			is_array( $atts ) ? $atts : array(),
			'adev_otp_modal'
		);

		ob_start();
		?>
		<button type="button" class="adev-otp-open-modal" data-adev-otp-open="login">
			<?php echo esc_html( $atts['label'] ); ?>
		</button>
		<?php
		Forms::render_modal();
		return (string) ob_get_clean();
	}

	/**
	 * Inline tabs shortcode (no popup).
	 *
	 * @param array|string $atts Attributes.
	 */
	public function tabs( $atts = array() ): string {
		Assets::ensure();
		$atts = shortcode_atts(
			array(
				'active' => 'login',
			),
			is_array( $atts ) ? $atts : array(),
			'adev_otp_tabs'
		);

		ob_start();
		Forms::render_tabs( (string) $atts['active'] );
		return (string) ob_get_clean();
	}
}

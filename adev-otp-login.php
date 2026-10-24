<?php
/**
 * Plugin Name:       Adev OTP Login
 * Plugin URI:        https://github.com/adsinooweb/adev-otp
 * Description:       Login and registration with one-time SMS codes. Supports Kavenegar and Melipayamak gateways, WooCommerce checkout, and Polylang.
 * Version:           1.1.0
 * Requires at least: 5.8
 * Requires PHP:      8.0
 * Author:            adsinooweb
 * Author URI:        https://adsinoo.com
 * Text Domain:       adev-otp-login
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'ADEV_OTP_VERSION', '1.1.0' );
define( 'ADEV_OTP_FILE', __FILE__ );
define( 'ADEV_OTP_PATH', plugin_dir_path( __FILE__ ) );
define( 'ADEV_OTP_URL', plugin_dir_url( __FILE__ ) );
define( 'ADEV_OTP_BASENAME', plugin_basename( __FILE__ ) );

require_once ADEV_OTP_PATH . 'includes/class-autoloader.php';
Adev_OTP\Autoloader::register();

register_activation_hook( __FILE__, array( 'Adev_OTP\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Adev_OTP\\Plugin', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		Adev_OTP\Plugin::instance()->init();
	}
);

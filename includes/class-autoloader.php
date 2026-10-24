<?php
/**
 * PSR-4 style autoloader for Adev_OTP namespace.
 */

namespace Adev_OTP;

defined( 'ABSPATH' ) || exit;

class Autoloader {

	/**
	 * Register the autoloader.
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Load a class file.
	 *
	 * @param string $class Fully-qualified class name.
	 */
	public static function load( string $class ): void {
		$prefix = __NAMESPACE__ . '\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$parts    = explode( '\\', $relative );
		$class_name = array_pop( $parts );

		// Convert Class_Name to class-class-name.php
		$file = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

		// Interface_Name → interface-name.php
		if ( 0 === strpos( $class_name, 'Interface_' ) || 'Gateway_Interface' === $class_name ) {
			$file = 'interface-' . str_replace( array( 'Interface_', '_' ), array( '', '-' ), strtolower( preg_replace( '/^Interface_/', '', $class_name ) ) ) . '.php';
		}

		if ( 'Gateway_Interface' === $class_name ) {
			$file = 'interface-gateway.php';
		}

		$subdir = '';
		if ( ! empty( $parts ) ) {
			$subdir = implode( '/', $parts ) . '/';
		}

		$path = ADEV_OTP_PATH . 'includes/' . $subdir . $file;

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}

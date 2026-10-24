<?php
/**
 * Gateway registry.
 */

namespace Adev_OTP\Gateways;

defined( 'ABSPATH' ) || exit;

use Adev_OTP\Plugin;

class Gateway_Registry {

	/**
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * @var array<string, Gateway_Interface>
	 */
	private array $gateways = array();

	/**
	 * Singleton.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register built-in gateways.
	 */
	public function register_defaults(): void {
		$this->register( new Kavenegar() );
		$this->register( new Melipayamak() );
	}

	/**
	 * Register a gateway.
	 *
	 * @param Gateway_Interface $gateway Gateway.
	 */
	public function register( Gateway_Interface $gateway ): void {
		$this->gateways[ $gateway->get_id() ] = $gateway;
	}

	/**
	 * All gateways.
	 *
	 * @return array<string, Gateway_Interface>
	 */
	public function all(): array {
		return $this->gateways;
	}

	/**
	 * Get by id.
	 *
	 * @param string $id Gateway id.
	 */
	public function get( string $id ): ?Gateway_Interface {
		return $this->gateways[ $id ] ?? null;
	}

	/**
	 * Active gateway from settings.
	 */
	public function get_active(): ?Gateway_Interface {
		$id = (string) Plugin::get_setting( 'active_gateway', 'kavenegar' );
		return $this->get( $id );
	}
}

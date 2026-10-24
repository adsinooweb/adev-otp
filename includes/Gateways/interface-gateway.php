<?php
/**
 * SMS Gateway interface.
 */

namespace Adev_OTP\Gateways;

defined( 'ABSPATH' ) || exit;

interface Gateway_Interface {

	/**
	 * Gateway slug.
	 */
	public function get_id(): string;

	/**
	 * Human-readable name.
	 */
	public function get_name(): string;

	/**
	 * Settings field definitions for admin UI.
	 *
	 * @return array<int, array{key:string,label:string,type:string,desc?:string}>
	 */
	public function get_settings_fields(): array;

	/**
	 * Send OTP SMS.
	 *
	 * @param string               $phone   Normalized phone (09…).
	 * @param string               $otp     Plain OTP code.
	 * @param array<string, mixed> $context Extra context.
	 * @return array{success:bool,message:string,raw?:mixed}
	 */
	public function send_otp( string $phone, string $otp, array $context = array() ): array;
}

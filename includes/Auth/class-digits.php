<?php
/**
 * Convert Eastern (Persian/Arabic/Afghan) digits to Latin.
 */

namespace Adev_OTP\Auth;

defined( 'ABSPATH' ) || exit;

class Digits {

	/**
	 * Map of non-Latin digit characters → ASCII 0-9.
	 *
	 * Covers Persian/Farsi, Dari/Afghan (Eastern Arabic-Indic),
	 * and Arabic-Indic digits.
	 *
	 * @return array<string, string>
	 */
	public static function map(): array {
		return array(
			// Persian / Dari / Pashto / Urdu (U+06F0–U+06F9).
			'۰' => '0',
			'۱' => '1',
			'۲' => '2',
			'۳' => '3',
			'۴' => '4',
			'۵' => '5',
			'۶' => '6',
			'۷' => '7',
			'۸' => '8',
			'۹' => '9',
			// Arabic-Indic (U+0660–U+0669).
			'٠' => '0',
			'١' => '1',
			'٢' => '2',
			'٣' => '3',
			'٤' => '4',
			'٥' => '5',
			'٦' => '6',
			'٧' => '7',
			'٨' => '8',
			'٩' => '9',
		);
	}

	/**
	 * Convert all known Eastern digits in a string to Latin digits.
	 *
	 * @param string $value Raw input.
	 */
	public static function to_latin( string $value ): string {
		if ( '' === $value ) {
			return '';
		}
		return strtr( $value, self::map() );
	}
}

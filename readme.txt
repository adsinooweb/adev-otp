=== Adev OTP Login ===
Contributors: adsinooweb
Tags: otp, sms, login, woocommerce, authentication
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Login and registration with one-time SMS codes. Supports Kavenegar, Melipayamak, WooCommerce, and Polylang (Persian, English, Arabic).

== Description ==

Adev OTP Login lets visitors sign in and register using a one-time password (OTP) sent by SMS.

**Features**

* SMS OTP login and registration
* Forgot / reset password via OTP
* Shortcodes and modal / tabbed forms
* Optional replacement of the default `wp-login.php` screen
* WooCommerce checkout mobile verification and forced login
* Gateways: [Kavenegar](https://kavenegar.com/) and [Melipayamak](https://www.melipayamak.com/)
* Rate limiting, honeypot, and phone blacklist
* Full translation support (English source) with bundled `fa_IR`, `en_US`, and `ar` language files
* Polylang integration: locale sync and String Translations for button labels

**Shortcodes**

* `[adev_otp_login]` — login form
* `[adev_otp_register]` — registration form
* `[adev_otp_forgot]` — forgot password form
* `[adev_otp_modal]` — button that opens the auth modal
* `[adev_otp_button]` — modal opener with custom label
* `[adev_otp_tabs]` — tabbed inline forms (no popup)

**Polylang**

1. Add Persian (`fa`), English (`en`), and Arabic (`ar`) in Polylang.
2. Plugin UI strings follow the current language via WordPress locale + bundled `.mo` files.
3. Customizable button texts appear under **Languages → String translations** (group: *Adev OTP Login*).

== Installation ==

1. Upload the `adev-otp-login` folder to `/wp-content/plugins/`, or install the ZIP via **Plugins → Add New**.
2. Activate the plugin through the **Plugins** screen.
3. Go to **OTP Login → Settings**, choose a gateway, and enter API credentials.
4. Place a shortcode on a page, or enable WooCommerce / wp-login options as needed.

== Frequently Asked Questions ==

= Does this plugin work without WooCommerce? =

Yes. WooCommerce features are optional and load only when WooCommerce is active.

= Which SMS providers are supported? =

Kavenegar and Melipayamak (pattern / verify and simple send modes).

= How do translations work with Polylang? =

The plugin ships English as the source language and includes Persian and Arabic catalogs. Polylang switches the locale per language; button labels from settings can also be translated in Polylang String Translations.

= Are OTP codes stored? =

OTP codes are stored temporarily (transients) with a short TTL for verification, then removed. Send logs mask phone numbers and do not store OTP codes.

== Screenshots ==

1. Admin settings — general and gateway
2. Frontend OTP login form
3. WooCommerce checkout verification

== Changelog ==

= 1.1.0 =
* English source strings for WordPress.org i18n
* Complete `fa_IR`, `en_US`, and `ar` language packs
* Polylang locale sync and String Translations for button labels
* `readme.txt`, license header, and packaging readiness

= 1.0.8 =
* WooCommerce checkout force login / register
* Gateway and rate-limit improvements

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.1.0 =
Source language is now English. Existing Persian button defaults are migrated automatically. Re-save Polylang string translations if you customize button labels.

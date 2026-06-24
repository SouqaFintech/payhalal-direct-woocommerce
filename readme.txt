=== Atozpay Direct for WooCommerce ===
Contributors: souqafintech
Tags: woocommerce, payment gateway, card, FPX and TNG payments, checkout blocks, hpos
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: Proprietary

Accept secure card, FPX and TNG payments through Atozpay Direct directly from your WooCommerce store.

== Description ==

Atozpay Direct for WooCommerce enables merchants to accept online card, FPX and TNG payments securely through the Atozpay Direct payment platform.

The plugin supports Classic WooCommerce Checkout, the latest WooCommerce Checkout Block, and WooCommerce High-Performance Order Storage.

= Key Features =

* Secure card, FPX and TNG payment processing
* WooCommerce Classic Checkout support
* WooCommerce Checkout Block support
* Automatic payment status updates
* Secure callback handling
* Transaction reconciliation support
* WooCommerce HPOS compatibility
* Sandbox and Production configuration

== Requirements ==

* WordPress 6.5 or later
* WooCommerce 8.5 or later
* PHP 8.1 or later recommended
* Active Atozpay Direct merchant account
* Valid App ID and App Secret issued by Souqa Fintech

== Installation ==

1. Upload the plugin ZIP file from Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Go to WooCommerce > Settings > Payments.
4. Enable Atozpay Direct.
5. Enter your App ID, App Secret, Merchant ID, and API Base URL.
6. Save changes.

== Supported Payment Methods ==

= Current Release =

* Credit Cards
* Debit Cards
* FPX Online Banking
* Touch 'n Go eWallet
* DuitNow QR

== Security ==

Atozpay Direct does not store full card numbers, CVV values, or card expiry details in WooCommerce order records.

== Changelog ==

= 1.1.1 =
* Added FPX Online Banking support.
* Added Touch 'n Go eWallet support.
* Added checkout payment method selection for Card, FPX and TNG.

= 1.0.1 =
* Added enhanced safe debug logging.
* Added admin-only detailed checkout error option for testing.

= 1.0.0 =

* Initial release
* Card payment support
* Classic Checkout support
* Checkout Block support
* Callback handling
* Transaction reconciliation support
* WooCommerce HPOS compatibility

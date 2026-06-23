=== PayHalal Direct for WooCommerce ===
Contributors: souqafintech
Tags: woocommerce, payment gateway, card payment, payhalal, acquiring
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Accept direct card payments through PayHalal Direct in WooCommerce.

== Description ==

PayHalal Direct for WooCommerce allows WooCommerce stores to accept direct card payments through PayHalal Direct acquiring APIs.

Version 1.0.0 supports Card payments first. FPX and TNG checkout options are prepared as Coming Soon UI options and can be enabled in future releases.

== Features ==

* WooCommerce payment gateway integration
* Modern checkout card payment UI
* PayHalal Direct authentication token caching
* Direct card payment request to /acquiring/cards
* Transaction ID saved to WooCommerce order meta
* Callback endpoint support: /?wc-api=payhalal_direct_callback
* Backward-friendly callback alias: /?wc-api=payhalal_callback
* WooCommerce order status update from callback payload
* Safe debug logging with sensitive fields redacted
* WooCommerce HPOS compatibility declaration

== Installation ==

1. Upload the plugin ZIP from WordPress Admin > Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Go to WooCommerce > Settings > Payments.
4. Enable PayHalal Direct.
5. Add API Base URL, App ID, App Secret, and Merchant ID.
6. Copy the Callback URL into the PayHalal merchant configuration if needed.

== Security Notes ==

This direct card integration collects card details on WooCommerce checkout and forwards them to PayHalal Direct. The plugin does not store card numbers or CVV in order meta or logs. Merchants should confirm PCI requirements before production usage.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added Card payment support.
* Added PayHalal Direct auth, card payment, callback, transaction meta and safe logging.

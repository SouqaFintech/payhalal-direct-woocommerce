# PayHalal Direct for WooCommerce

Accept secure card payments through PayHalal Direct directly from your WooCommerce store.

## Overview

PayHalal Direct for WooCommerce enables merchants to accept online card payments securely through the PayHalal Direct payment platform while maintaining a seamless WooCommerce checkout experience.

The plugin supports both the classic WooCommerce checkout and the latest WooCommerce Checkout Block.

## Key Features

- Secure card payment processing
- WooCommerce Classic Checkout support
- WooCommerce Checkout Block support
- Automatic order status updates
- Transaction reconciliation support
- Secure callback handling
- WooCommerce HPOS compatibility
- Sandbox and Production environment support
- Merchant-friendly configuration

## Requirements

- WordPress 6.5 or later
- WooCommerce 8.5 or later
- PHP 8.1 or later recommended
- Active PayHalal Direct merchant account
- Valid App ID and App Secret issued by Souqa Fintech

## Installation

1. Download the latest plugin ZIP package.
2. Log in to your WordPress Administration Panel.
3. Go to **Plugins → Add New**.
4. Click **Upload Plugin**.
5. Upload the PayHalal Direct plugin ZIP file.
6. Activate the plugin.

## Configuration

1. Go to **WooCommerce → Settings → Payments**.
2. Locate **PayHalal Direct**.
3. Click **Manage**.
4. Enter your merchant credentials:
   - App ID
   - App Secret
   - Merchant ID
   - API Base URL
5. Enable the payment method.
6. Save your settings.

## Checkout Support

PayHalal Direct supports:

- Classic WooCommerce checkout using `[woocommerce_checkout]`
- Modern WooCommerce Checkout Block
- WooCommerce High-Performance Order Storage

## Customer Checkout Experience

1. Customer adds products to cart.
2. Customer proceeds to checkout.
3. Customer selects **PayHalal Direct**.
4. Customer enters card details.
5. Payment is processed securely through PayHalal Direct.
6. Customer is redirected back to the merchant website.
7. WooCommerce order status is updated automatically.

## Supported Payment Methods

### Current Release

- Credit Cards
- Debit Cards

### Upcoming Releases

- FPX Online Banking
- Touch 'n Go eWallet
- DuitNow QR
- Additional regional payment methods

## Order Status Synchronization

| Payment Result | WooCommerce Status |
| --- | --- |
| Successful | Processing / Completed |
| Pending | On Hold |
| Failed | Failed |
| Cancelled | Cancelled |

## Security

PayHalal Direct does not store full card numbers, CVV values, or card expiry details in WooCommerce order records.

Sensitive payment information is transmitted securely to the PayHalal Direct payment platform.

## Troubleshooting

### Payment Method Does Not Appear on Checkout

Please verify:

- WooCommerce is active.
- PayHalal Direct is enabled under **WooCommerce → Settings → Payments**.
- Card payment is enabled in the plugin settings.
- The checkout page is using either Classic Checkout or Checkout Block.

### Payment Not Updating

If a payment appears successful but the WooCommerce order status has not updated:

1. Verify your callback URL is accessible.
2. Ensure your merchant credentials are correct.
3. Confirm your hosting environment allows outbound API requests.
4. Review WooCommerce logs for additional information.

## Support

**Souqa Fintech Sdn Bhd**

Website: https://payhalal.my

Email: support@payhalal.my

## Changelog

### 1.0.0

- Initial release
- Card payment support
- WooCommerce Classic Checkout support
- WooCommerce Checkout Block support
- Payment callback support
- Transaction reconciliation support
- WooCommerce HPOS compatibility

## License

This plugin is proprietary software owned by Souqa Fintech Sdn Bhd.

Unauthorized distribution, modification, or resale is prohibited unless explicitly authorized by Souqa Fintech Sdn Bhd.

# Atozpay Direct Developer Documentation

This document provides technical implementation details for Atozpay Direct for WooCommerce.

## Plugin Architecture

The plugin is structured for both classic and modern WooCommerce checkout compatibility.

```txt
atozpay-direct/
├── atozpay-direct.php
├── includes/
│   ├── class-atozpay-direct-api.php
│   ├── class-atozpay-direct-blocks.php
│   ├── class-atozpay-direct-callback.php
│   ├── class-atozpay-direct-gateway.php
│   └── class-atozpay-direct-logger.php
├── assets/
│   ├── css/checkout.css
│   └── js/
│       ├── blocks.js
│       ├── blocks.asset.php
│       └── checkout.js
└── docs/DEVELOPER.md
```

## WooCommerce Compatibility

Supported checkout implementations:

- Classic checkout through `WC_Payment_Gateway`
- Checkout Block through `AbstractPaymentMethodType`
- HPOS through WooCommerce feature compatibility declaration

The payment gateway ID is:

```txt
atozpay_direct
```

## Authentication

All API requests require a JWT access token.

### Endpoint

```http
POST /auth
```

### Headers

```http
Content-Type: application/json
X-Version: v1
```

### Request

```json
{
  "app_id": "YOUR_APP_ID",
  "app_secret": "YOUR_APP_SECRET"
}
```

### Response

```json
{
  "status": 200,
  "status_text": "Authenticated",
  "token": "JWT_TOKEN"
}
```

The plugin caches the token for 30 minutes using WordPress transients.

## Card Payment Request

### Endpoint

```http
POST /acquiring/cards
```

### Headers

```http
Authorization: Bearer {token}
Content-Type: application/json
X-Version: v1
```

### Request Payload

```json
{
  "amount": 100.0,
  "currency": "MYR",
  "product_description": "Order #1001",
  "order_id": "1001",
  "customer_email": "customer@example.com",
  "customer_phone": "60123456789",
  "customer_name": "John Doe",
  "merchant_id": "MERCHANT_ID",
  "card_holder_name": "JOHN DOE",
  "card_number": "4111111111111111",
  "card_exp_mn": "12",
  "card_exp_yy": "28",
  "card_cvv": "123",
  "success_url": "https://merchant.com/checkout/order-received/1001/",
  "return_url": "https://merchant.com/checkout/order-pay/1001/",
  "callback_url": "https://merchant.com/?wc-api=atozpay_direct_callback"
}
```

## Payment Response

```json
{
  "status": 200,
  "status_text": "Created payment link",
  "link": "/acquiring/redirect?token=xxxx",
  "transaction_id": "CR-XXXXXXXX"
}
```

## Customer Redirect

Redirect customers to:

```txt
https://agents.atozpay.net/{payment_link}
```

## Callback URL

WooCommerce callback endpoint:

```txt
https://merchant-site.com/?wc-api=atozpay_direct_callback
```

The callback handler should:

1. Identify the WooCommerce order.
2. Verify transaction status through reconciliation when needed.
3. Update order status.
4. Save safe transaction metadata.

## Transaction Reconciliation

### Query By Transaction ID

```http
GET /acquiring/transaction/{transaction_id}
```

### Query By Order ID

```http
GET /acquiring/order/{order_id}
```

## WooCommerce Order Meta

Allowed metadata:

```php
_atozpay_direct_transaction_id
_atozpay_direct_payment_method
_atozpay_direct_last_status
_atozpay_direct_last_recon
```

Never store:

```php
card_number
card_cvv
card_expiry
app_secret
JWT token
```

## Checkout Blocks Notes

The Checkout Block integration registers Atozpay Direct using:

```php
Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType
```

Frontend card data is passed through `paymentMethodData` and read by the gateway during payment processing.

## Logging Guidelines

Allowed:

- Transaction ID
- Order ID
- API response status
- Non-sensitive error messages

Not allowed:

- Full card number
- CVV
- Expiry date
- Authentication credentials
- JWT token

## Version History

### v1.0.0

- Authentication
- Card payments
- Classic Checkout support
- Checkout Block support
- Redirect flow
- Callback handling
- Transaction reconciliation
- WooCommerce HPOS compatibility

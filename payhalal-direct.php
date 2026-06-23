<?php
/**
 * Plugin Name: PayHalal Direct for WooCommerce
 * Plugin URI: https://payhalal.my/
 * Description: Accept direct card payments through PayHalal Direct in WooCommerce, including Classic Checkout and Checkout Blocks support.
 * Version: 1.0.0
 * Author: Souqa Fintech Sdn Bhd
 * Author URI: https://payhalal.my/
 * Text Domain: payhalal-direct
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.5
 * WC tested up to: 9.9
 * License: GPL-2.0-or-later
 */

defined('ABSPATH') || exit;

define('PAYHALAL_DIRECT_VERSION', '1.0.0');
define('PAYHALAL_DIRECT_FILE', __FILE__);
define('PAYHALAL_DIRECT_PATH', plugin_dir_path(__FILE__));
define('PAYHALAL_DIRECT_URL', plugin_dir_url(__FILE__));
define('PAYHALAL_DIRECT_MIN_PHP', '7.4');
define('PAYHALAL_DIRECT_MIN_WC', '8.5');

add_action('before_woocommerce_init', function () {
    if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

add_action('plugins_loaded', 'payhalal_direct_init', 11);

function payhalal_direct_init(): void
{
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'payhalal_direct_missing_wc_notice');
        return;
    }

    require_once PAYHALAL_DIRECT_PATH . 'includes/class-payhalal-direct-logger.php';
    require_once PAYHALAL_DIRECT_PATH . 'includes/class-payhalal-direct-api.php';
    require_once PAYHALAL_DIRECT_PATH . 'includes/class-payhalal-direct-callback.php';
    require_once PAYHALAL_DIRECT_PATH . 'includes/class-payhalal-direct-gateway.php';

    if (class_exists('Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType')) {
        require_once PAYHALAL_DIRECT_PATH . 'includes/class-payhalal-direct-blocks.php';
    }

    add_filter('woocommerce_payment_gateways', function (array $gateways): array {
        $gateways[] = 'WC_Gateway_PayHalal_Direct';
        return $gateways;
    });

    PayHalal_Direct_Callback::init();
}

function payhalal_direct_missing_wc_notice(): void
{
    if (!current_user_can('activate_plugins')) {
        return;
    }

    echo '<div class="notice notice-error"><p><strong>PayHalal Direct for WooCommerce</strong> requires WooCommerce to be installed and active.</p></div>';
}


add_action('woocommerce_blocks_loaded', function (): void {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once PAYHALAL_DIRECT_PATH . 'includes/class-payhalal-direct-blocks.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry): void {
            $payment_method_registry->register(new PayHalal_Direct_Blocks());
        }
    );
});

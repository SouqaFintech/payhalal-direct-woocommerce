<?php

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class PayHalal_Direct_Blocks extends AbstractPaymentMethodType
{
    protected $name = 'payhalal_direct';

    private ?WC_Gateway_PayHalal_Direct $gateway = null;

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_payhalal_direct_settings', []);

        $gateways = WC()->payment_gateways()->payment_gateways();
        if (isset($gateways[$this->name]) && $gateways[$this->name] instanceof WC_Gateway_PayHalal_Direct) {
            $this->gateway = $gateways[$this->name];
        }
    }

    public function is_active(): bool
    {
        return isset($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    public function get_payment_method_script_handles(): array
    {
        $script_path = PAYHALAL_DIRECT_PATH . 'assets/js/blocks.js';
        $script_url = PAYHALAL_DIRECT_URL . 'assets/js/blocks.js';
        $asset_path = PAYHALAL_DIRECT_PATH . 'assets/js/blocks.asset.php';

        $asset = file_exists($asset_path)
            ? require $asset_path
            : [
                'dependencies' => [
                    'wc-blocks-registry',
                    'wc-settings',
                    'wp-element',
                    'wp-html-entities',
                    'wp-i18n',
                ],
                'version' => file_exists($script_path) ? filemtime($script_path) : PAYHALAL_DIRECT_VERSION,
            ];

        wp_register_script(
            'payhalal-direct-blocks',
            $script_url,
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_register_style(
            'payhalal-direct-checkout',
            PAYHALAL_DIRECT_URL . 'assets/css/checkout.css',
            [],
            PAYHALAL_DIRECT_VERSION
        );
        wp_enqueue_style('payhalal-direct-checkout');

        return ['payhalal-direct-blocks'];
    }

    public function get_payment_method_data(): array
    {
        $title = $this->gateway ? $this->gateway->get_title() : ($this->settings['title'] ?? 'PayHalal Direct');
        $description = $this->gateway ? $this->gateway->get_description() : ($this->settings['description'] ?? 'Accept secure card payments through PayHalal Direct.');

        return [
            'title' => wp_strip_all_tags((string) $title),
            'description' => wp_kses_post((string) $description),
            'supports' => $this->gateway ? array_filter($this->gateway->supports, [$this->gateway, 'supports']) : ['products'],
            'showFutureMethods' => isset($this->settings['show_future_methods']) && 'yes' === $this->settings['show_future_methods'],
            'cardEnabled' => !isset($this->settings['card_enabled']) || 'yes' === $this->settings['card_enabled'],
        ];
    }
}

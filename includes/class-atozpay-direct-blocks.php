<?php

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Atozpay_Direct_Blocks extends AbstractPaymentMethodType
{
    protected $name = 'atozpay_direct';

    private ?WC_Gateway_Atozpay_Direct $gateway = null;

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_atozpay_direct_settings', []);

        $gateways = WC()->payment_gateways()->payment_gateways();
        if (isset($gateways[$this->name]) && $gateways[$this->name] instanceof WC_Gateway_Atozpay_Direct) {
            $this->gateway = $gateways[$this->name];
        }
    }

    public function is_active(): bool
    {
        return isset($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    public function get_payment_method_script_handles(): array
    {
        $script_path = ATOZPAY_DIRECT_PATH . 'assets/js/blocks.js';
        $script_url = ATOZPAY_DIRECT_URL . 'assets/js/blocks.js';
        $asset_path = ATOZPAY_DIRECT_PATH . 'assets/js/blocks.asset.php';

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
                'version' => file_exists($script_path) ? filemtime($script_path) : ATOZPAY_DIRECT_VERSION,
            ];

        wp_register_script(
            'atozpay-direct-blocks',
            $script_url,
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_register_style(
            'atozpay-direct-checkout',
            ATOZPAY_DIRECT_URL . 'assets/css/checkout.css',
            [],
            ATOZPAY_DIRECT_VERSION
        );
        wp_enqueue_style('atozpay-direct-checkout');

        return ['atozpay-direct-blocks'];
    }

    public function get_payment_method_data(): array
    {
        $title = $this->gateway ? $this->gateway->get_title() : ($this->settings['title'] ?? 'Atozpay Direct');
        $description = $this->gateway ? $this->gateway->get_description() : ($this->settings['description'] ?? 'Accept secure card payments through Atozpay Direct.');

        return [
            'title' => wp_strip_all_tags((string) $title),
            'description' => wp_kses_post((string) $description),
            'supports' => $this->gateway ? array_filter($this->gateway->supports, [$this->gateway, 'supports']) : ['products'],
            'cardEnabled' => !isset($this->settings['card_enabled']) || 'yes' === $this->settings['card_enabled'],
            'fpxEnabled' => !isset($this->settings['fpx_enabled']) || 'yes' === $this->settings['fpx_enabled'],
            'tngEnabled' => !isset($this->settings['tng_enabled']) || 'yes' === $this->settings['tng_enabled'],
            'fpxBanks' => [
                ['code' => 'TEST0021', 'name' => 'FPX Test Bank'],
                ['code' => 'RHB0218', 'name' => 'RHB Bank'],
                ['code' => 'MB2U0227', 'name' => 'Maybank2u'],
                ['code' => 'MBB0228', 'name' => 'Maybank2E'],
                ['code' => 'CIMB0229', 'name' => 'CIMB Clicks'],
                ['code' => 'PBB0233', 'name' => 'Public Bank'],
                ['code' => 'HLB0224', 'name' => 'Hong Leong Bank'],
                ['code' => 'BIMB0340', 'name' => 'Bank Islam'],
                ['code' => 'AMBANK0226', 'name' => 'AmBank'],
                ['code' => 'BMMB0341', 'name' => 'Bank Muamalat'],
                ['code' => 'BSN0601', 'name' => 'BSN'],
                ['code' => 'OCBC0229', 'name' => 'OCBC Bank'],
                ['code' => 'SCB0216', 'name' => 'Standard Chartered'],
                ['code' => 'UOB0226', 'name' => 'UOB Bank'],
                ['code' => 'HSBC0223', 'name' => 'HSBC Bank'],
                ['code' => 'KFH0346', 'name' => 'KFH Malaysia'],
            ],
        ];
    }
}

<?php

defined('ABSPATH') || exit;

class WC_Gateway_Atozpay_Direct extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'atozpay_direct';
        $this->icon = '';
        $this->has_fields = true;
        $this->method_title = __('Atozpay Direct', 'atozpay-direct');
        $this->method_description = __('Accept card, FPX and TNG eWallet payments directly through AtozPay.', 'atozpay-direct');
        $this->supports = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', 'Atozpay Direct');
        $this->description = $this->get_option('description', 'Accept card, FPX and TNG eWallet payments securely through AtozPay.');
        $this->enabled = $this->get_option('enabled', 'no');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function init_form_fields(): void
    {
        $callback_url = home_url('/?wc-api=atozpay_direct_callback');
        $return_url = home_url('/?wc-api=atozpay_direct_return');

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'atozpay-direct'),
                'type' => 'checkbox',
                'label' => __('Enable Atozpay Direct', 'atozpay-direct'),
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Checkout Title', 'atozpay-direct'),
                'type' => 'text',
                'default' => __('Atozpay Direct', 'atozpay-direct'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Checkout Description', 'atozpay-direct'),
                'type' => 'textarea',
                'default' => __('Accept card, FPX and TNG eWallet payments securely through AtozPay.', 'atozpay-direct'),
            ],
            'base_url' => [
                'title' => __('API Base URL', 'atozpay-direct'),
                'type' => 'text',
                'default' => 'https://agents.atozpay.net',
            ],
            'app_id' => [
                'title' => __('App ID', 'atozpay-direct'),
                'type' => 'text',
                'default' => '',
            ],
            'app_secret' => [
                'title' => __('App Secret', 'atozpay-direct'),
                'type' => 'password',
                'default' => '',
            ],
            'merchant_id' => [
                'title' => __('Merchant ID', 'atozpay-direct'),
                'type' => 'text',
                'default' => '',
            ],
            'card_enabled' => [
                'title' => __('Card Payment', 'atozpay-direct'),
                'type' => 'checkbox',
                'label' => __('Enable card payment option on checkout', 'atozpay-direct'),
                'default' => 'yes',
            ],
            'fpx_enabled' => [
                'title' => __('FPX Online Banking', 'atozpay-direct'),
                'type' => 'checkbox',
                'label' => __('Enable FPX online banking option on checkout', 'atozpay-direct'),
                'default' => 'yes',
            ],
            'tng_enabled' => [
                'title' => __('TNG eWallet', 'atozpay-direct'),
                'type' => 'checkbox',
                'label' => __("Enable Touch 'n Go eWallet option on checkout", 'atozpay-direct'),
                'default' => 'yes',
            ],
            'debug' => [
                'title' => __('Debug Log', 'atozpay-direct'),
                'type' => 'checkbox',
                'label' => __('Enable safe debug logging', 'atozpay-direct'),
                'default' => 'no',
                'description' => __('Logs API requests, responses and WooCommerce gateway events to WooCommerce → Status → Logs. Sensitive card data and secrets are redacted.', 'atozpay-direct'),
            ],
            'debug_checkout_errors' => [
                'title' => __('Debug Checkout Errors', 'atozpay-direct'),
                'type' => 'checkbox',
                'label' => __('Show detailed Atozpay error messages on checkout while debugging', 'atozpay-direct'),
                'default' => 'no',
                'description' => __('Only enable on staging or during internal testing. Keep disabled for live customers.', 'atozpay-direct'),
            ],
            'return_url' => [
                'title' => __('Return URL', 'atozpay-direct'),
                'type' => 'title',
                'description' => '<code>' . esc_html($return_url) . '</code><p class="description">Atozpay Direct uses this URL to return customers back to WooCommerce after payment.</p>',
            ],
            'callback_url' => [
                'title' => __('Callback URL', 'atozpay-direct'),
                'type' => 'title',
                'description' => '<code>' . esc_html($callback_url) . '</code><p class="description">Use this URL in the Atozpay merchant configuration if callback URL is required.</p>',
            ],
        ];
    }

    public function enqueue_assets(): void
    {
        if (!is_checkout()) {
            return;
        }

        wp_enqueue_style('atozpay-direct-checkout', ATOZPAY_DIRECT_URL . 'assets/css/checkout.css', [], ATOZPAY_DIRECT_VERSION);
        wp_enqueue_script('atozpay-direct-checkout', ATOZPAY_DIRECT_URL . 'assets/js/checkout.js', [], ATOZPAY_DIRECT_VERSION, true);
    }

    public function payment_fields(): void
    {
        $enabled_methods = $this->get_enabled_payment_methods();
        $default_method = $this->get_default_payment_method($enabled_methods);
        ?>
        <div class="atozpay-direct-box">
            <?php if ($this->description) : ?>
                <div class="atozpay-direct-description"><?php echo wp_kses_post(wpautop($this->description)); ?></div>
            <?php endif; ?>

            <div class="atozpay-direct-header">
                <div class="atozpay-direct-title">
                    <span class="atozpay-direct-mark" aria-hidden="true">⌁</span>
                    <div>
                        <strong><?php esc_html_e('AtozPay', 'atozpay-direct'); ?></strong>
                        <span><?php esc_html_e('Secure payments powered by AtozPay', 'atozpay-direct'); ?></span>
                    </div>
                </div>
                <em class="atozpay-direct-badge"><?php esc_html_e('Secure', 'atozpay-direct'); ?></em>
            </div>

            <div class="atozpay-direct-methods" role="radiogroup" aria-label="<?php esc_attr_e('AtozPay payment method', 'atozpay-direct'); ?>">
                <?php if (in_array('card', $enabled_methods, true)) : ?>
                    <label class="atozpay-direct-method <?php echo $default_method === 'card' ? 'is-active' : ''; ?>">
                        <input type="radio" name="atozpay_direct_method" value="card" <?php checked($default_method, 'card'); ?>>
                        <span class="atozpay-direct-method-icon" aria-hidden="true">💳</span>
                        <span><strong><?php esc_html_e('Card', 'atozpay-direct'); ?></strong><small><?php esc_html_e('Visa, Mastercard and debit cards', 'atozpay-direct'); ?></small></span>
                    </label>
                <?php endif; ?>

                <?php if (in_array('fpx', $enabled_methods, true)) : ?>
                    <label class="atozpay-direct-method <?php echo $default_method === 'fpx' ? 'is-active' : ''; ?>">
                        <input type="radio" name="atozpay_direct_method" value="fpx" <?php checked($default_method, 'fpx'); ?>>
                        <span class="atozpay-direct-method-icon" aria-hidden="true">🏦</span>
                        <span><strong><?php esc_html_e('FPX Online Banking', 'atozpay-direct'); ?></strong><small><?php esc_html_e('Pay with Malaysian online banking', 'atozpay-direct'); ?></small></span>
                    </label>
                <?php endif; ?>

                <?php if (in_array('tng', $enabled_methods, true)) : ?>
                    <label class="atozpay-direct-method <?php echo $default_method === 'tng' ? 'is-active' : ''; ?>">
                        <input type="radio" name="atozpay_direct_method" value="tng" <?php checked($default_method, 'tng'); ?>>
                        <span class="atozpay-direct-method-icon" aria-hidden="true">📱</span>
                        <span><strong><?php esc_html_e('TNG eWallet', 'atozpay-direct'); ?></strong><small><?php esc_html_e('Pay using Touch \'n Go eWallet', 'atozpay-direct'); ?></small></span>
                    </label>
                <?php endif; ?>
            </div>

            <?php if (in_array('card', $enabled_methods, true)) : ?>
                <div class="atozpay-direct-card-panel atozpay-direct-payment-panel" data-atozpay-panel="card" <?php echo $default_method === 'card' ? '' : 'style="display:none"'; ?>>
                    <div class="atozpay-direct-card-preview" aria-hidden="true">
                        <div class="atozpay-direct-card-preview-top">
                            <span class="atozpay-direct-card-chip"></span>
                            <span class="atozpay-direct-card-brand-preview">Card</span>
                        </div>
                        <div class="atozpay-direct-card-number-preview">•••• •••• •••• ••••</div>
                        <div class="atozpay-direct-card-preview-bottom">
                            <span>
                                <span class="atozpay-direct-card-label"><?php esc_html_e('Cardholder', 'atozpay-direct'); ?></span>
                                <span class="atozpay-direct-card-holder-preview"><?php esc_html_e('Name on card', 'atozpay-direct'); ?></span>
                            </span>
                            <span>
                                <span class="atozpay-direct-card-label"><?php esc_html_e('Expires', 'atozpay-direct'); ?></span>
                                <span class="atozpay-direct-card-exp-preview">MM/YY</span>
                            </span>
                        </div>
                    </div>

                    <div class="atozpay-direct-grid">
                        <p class="atozpay-direct-field full">
                            <label for="atozpay_direct_card_holder_name"><?php esc_html_e('Cardholder Name', 'atozpay-direct'); ?></label>
                            <input id="atozpay_direct_card_holder_name" name="atozpay_direct_card_holder_name" type="text" autocomplete="cc-name" placeholder="Name on card">
                        </p>
                        <p class="atozpay-direct-field full has-card-brand">
                            <label for="atozpay_direct_card_number"><?php esc_html_e('Card Number', 'atozpay-direct'); ?></label>
                            <input id="atozpay_direct_card_number" name="atozpay_direct_card_number" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="1234 1234 1234 1234" maxlength="23">
                            <span class="atozpay-direct-brand" aria-live="polite"></span>
                        </p>
                        <p class="atozpay-direct-field">
                            <label for="atozpay_direct_card_exp_mn"><?php esc_html_e('Month', 'atozpay-direct'); ?></label>
                            <input id="atozpay_direct_card_exp_mn" name="atozpay_direct_card_exp_mn" type="text" inputmode="numeric" autocomplete="cc-exp-month" placeholder="MM" maxlength="2">
                        </p>
                        <p class="atozpay-direct-field">
                            <label for="atozpay_direct_card_exp_yy"><?php esc_html_e('Year', 'atozpay-direct'); ?></label>
                            <input id="atozpay_direct_card_exp_yy" name="atozpay_direct_card_exp_yy" type="text" inputmode="numeric" autocomplete="cc-exp-year" placeholder="YY" maxlength="4">
                        </p>
                        <p class="atozpay-direct-field">
                            <label for="atozpay_direct_card_cvv"><?php esc_html_e('CVV', 'atozpay-direct'); ?></label>
                            <input id="atozpay_direct_card_cvv" name="atozpay_direct_card_cvv" type="password" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4">
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (in_array('fpx', $enabled_methods, true)) : ?>
                <div class="atozpay-direct-alt-panel atozpay-direct-payment-panel" data-atozpay-panel="fpx" <?php echo $default_method === 'fpx' ? '' : 'style="display:none"'; ?>>
                    <div class="atozpay-direct-alt-header">
                        <span class="atozpay-direct-alt-icon" aria-hidden="true">🏦</span>
                        <div>
                            <strong><?php esc_html_e('Choose your bank', 'atozpay-direct'); ?></strong>
                            <span><?php esc_html_e('You will be redirected to complete payment securely via FPX.', 'atozpay-direct'); ?></span>
                        </div>
                    </div>
                    <p class="atozpay-direct-field full">
                        <label for="atozpay_direct_bank_code"><?php esc_html_e('Bank', 'atozpay-direct'); ?></label>
                        <select id="atozpay_direct_bank_code" name="atozpay_direct_bank_code">
                            <option value=""><?php esc_html_e('Select bank', 'atozpay-direct'); ?></option>
                            <?php foreach ($this->get_fpx_banks() as $code => $bank_name) : ?>
                                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($bank_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                </div>
            <?php endif; ?>

            <?php if (in_array('tng', $enabled_methods, true)) : ?>
                <div class="atozpay-direct-alt-panel atozpay-direct-payment-panel" data-atozpay-panel="tng" <?php echo $default_method === 'tng' ? '' : 'style="display:none"'; ?>>
                    <div class="atozpay-direct-alt-header">
                        <span class="atozpay-direct-alt-icon" aria-hidden="true">📱</span>
                        <div>
                            <strong><?php esc_html_e('Pay with TNG eWallet', 'atozpay-direct'); ?></strong>
                            <span><?php esc_html_e('After placing your order, you will be redirected to complete your eWallet payment.', 'atozpay-direct'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function validate_fields(): bool
    {
        $posted = $this->get_posted_payment_data();
        $method = isset($posted['atozpay_direct_method']) ? sanitize_text_field($posted['atozpay_direct_method']) : $this->get_default_payment_method();
        $enabled_methods = $this->get_enabled_payment_methods();

        if (!in_array($method, $enabled_methods, true)) {
            wc_add_notice(__('Selected AtozPay payment method is not available.', 'atozpay-direct'), 'error');
            return false;
        }

        if ($method === 'card') {
            $required = [
                'atozpay_direct_card_holder_name' => __('Cardholder name is required.', 'atozpay-direct'),
                'atozpay_direct_card_number' => __('Card number is required.', 'atozpay-direct'),
                'atozpay_direct_card_exp_mn' => __('Expiry month is required.', 'atozpay-direct'),
                'atozpay_direct_card_exp_yy' => __('Expiry year is required.', 'atozpay-direct'),
                'atozpay_direct_card_cvv' => __('CVV is required.', 'atozpay-direct'),
            ];

            foreach ($required as $field => $message) {
                if (empty($posted[$field])) {
                    wc_add_notice($message, 'error');
                    return false;
                }
            }

            $card_number = preg_replace('/\D+/', '', sanitize_text_field($posted['atozpay_direct_card_number'] ?? ''));
            $month = preg_replace('/\D+/', '', sanitize_text_field($posted['atozpay_direct_card_exp_mn'] ?? ''));
            $year = preg_replace('/\D+/', '', sanitize_text_field($posted['atozpay_direct_card_exp_yy'] ?? ''));
            $cvv = preg_replace('/\D+/', '', sanitize_text_field($posted['atozpay_direct_card_cvv'] ?? ''));

            if (strlen($card_number) < 12 || strlen($card_number) > 19) {
                wc_add_notice(__('Please enter a valid card number.', 'atozpay-direct'), 'error');
                return false;
            }

            if ((int) $month < 1 || (int) $month > 12) {
                wc_add_notice(__('Please enter a valid expiry month.', 'atozpay-direct'), 'error');
                return false;
            }

            if (!in_array(strlen($year), [2, 4], true)) {
                wc_add_notice(__('Please enter a valid expiry year.', 'atozpay-direct'), 'error');
                return false;
            }

            if (strlen($cvv) < 3 || strlen($cvv) > 4) {
                wc_add_notice(__('Please enter a valid CVV.', 'atozpay-direct'), 'error');
                return false;
            }
        }

        if ($method === 'fpx' && empty($posted['atozpay_direct_bank_code'])) {
            wc_add_notice(__('Please select your FPX bank.', 'atozpay-direct'), 'error');
            return false;
        }

        return true;
    }

    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            wc_add_notice(__('Unable to locate WooCommerce order.', 'atozpay-direct'), 'error');
            return ['result' => 'failure'];
        }

        $debug_enabled = $this->get_option('debug', 'no') === 'yes';
        $logger = new AtozPay_Direct_Logger($debug_enabled);

        try {
            $logger->debug('Starting AtozPay Direct payment for WooCommerce order #' . $order->get_id(), [
                'order_id' => $order->get_id(),
                'amount' => $order->get_total(),
                'currency' => $order->get_currency(),
            ]);

            $api = new AtozPay_Direct_API(
                $this->get_option('base_url', 'https://agents.atozpay.net'),
                $this->get_option('app_id'),
                $this->get_option('app_secret'),
                $debug_enabled
            );

            $posted = $this->get_posted_payment_data();
            $method = isset($posted['atozpay_direct_method']) ? sanitize_text_field($posted['atozpay_direct_method']) : $this->get_default_payment_method();

            switch ($method) {
                case 'card':
                    $payload = $this->build_card_payload($order);
                    $response = $api->create_card_payment($payload);
                    break;
                case 'fpx':
                    $payload = $this->build_fpx_payload($order);
                    $response = $api->create_fpx_payment($payload);
                    break;
                case 'tng':
                    $payload = $this->build_tng_payload($order);
                    $response = $api->create_tng_payment($payload);
                    break;
                default:
                    throw new Exception(__('Invalid AtozPay payment method selected.', 'atozpay-direct'));
            }

            $logger->debug('Built AtozPay ' . strtoupper($method) . ' payload for order #' . $order->get_id(), $payload);
            $logger->debug('AtozPay ' . strtoupper($method) . ' payment response received for order #' . $order->get_id(), $response);

            $transaction_id = isset($response['transaction_id']) ? sanitize_text_field($response['transaction_id']) : '';
            $link = isset($response['link']) ? (string) $response['link'] : '';

            if (!$link) {
                throw new Exception(__('Payment link missing from AtozPay Direct response.', 'atozpay-direct'));
            }

            $redirect_url = $this->build_redirect_url($link);

            $logger->debug('AtozPay Direct redirect URL prepared for order #' . $order->get_id(), [
                'transaction_id' => $transaction_id,
                'redirect' => $redirect_url,
            ]);

            if ($transaction_id) {
                $order->update_meta_data('_atozpay_direct_transaction_id', $transaction_id);
            }

            $order->update_meta_data('_atozpay_direct_payment_method', $method);
            $order->add_order_note('AtozPay ' . strtoupper($method) . ' payment initiated' . ($transaction_id ? '. Transaction ID: ' . $transaction_id : '.'));
            $order->save();

            WC()->cart->empty_cart();

            return [
                'result' => 'success',
                'redirect' => $redirect_url,
            ];
        } catch (Exception $e) {
            $logger->debug('AtozPay Direct payment failed for WooCommerce order #' . $order->get_id(), [
                'error' => $e->getMessage(),
            ]);

            $order->add_order_note('AtozPay Direct payment error: ' . $e->getMessage());

            if ($this->get_option('debug_checkout_errors', 'no') === 'yes' && current_user_can('manage_woocommerce')) {
                wc_add_notice(sprintf(__('AtozPay Debug: %s', 'atozpay-direct'), esc_html($e->getMessage())), 'error');
            } else {
                wc_add_notice(__('Payment could not be started. Please try again or use another payment method.', 'atozpay-direct'), 'error');
            }

            return ['result' => 'failure'];
        }
    }

    private function build_card_payload(WC_Order $order): array
    {
        $posted = $this->get_posted_payment_data();

        $holder = sanitize_text_field($posted['atozpay_direct_card_holder_name'] ?? '');
        $number = preg_replace('/\D+/', '', sanitize_text_field($posted['atozpay_direct_card_number'] ?? ''));
        $month = preg_replace('/\D+/', '', sanitize_text_field($posted['atozpay_direct_card_exp_mn'] ?? ''));
        $year = preg_replace('/\D+/', '', sanitize_text_field($posted['atozpay_direct_card_exp_yy'] ?? ''));
        $cvv = preg_replace('/\D+/', '', sanitize_text_field($posted['atozpay_direct_card_cvv'] ?? ''));

        if (strlen($year) === 4) {
            $year = substr($year, -2);
        }

        $atozpay_return_url = $this->get_atozpay_return_url($order);

        return [
            'amount' => (float) $order->get_total(),
            'currency' => $order->get_currency(),
            'product_description' => $this->get_product_description($order),
            'order_id' => (string) $order->get_id(),
            'customer_email' => $order->get_billing_email(),
            'customer_phone' => $order->get_billing_phone(),
            'customer_name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'merchant_id' => $this->get_option('merchant_id'),
            'card_holder_name' => $holder,
            'card_number' => $number,
            'card_exp_mn' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'card_exp_yy' => $year,
            'card_cvv' => $cvv,
            'success_url' => $atozpay_return_url,
            'return_url' => $atozpay_return_url,
            'callback_url' => home_url('/?wc-api=atozpay_direct_callback'),
        ];
    }

    private function build_fpx_payload(WC_Order $order): array
    {
        $posted = $this->get_posted_payment_data();

        return array_merge($this->build_common_redirect_payload($order), [
            'bank_code' => sanitize_text_field($posted['atozpay_direct_bank_code'] ?? ''),
        ]);
    }

    private function build_tng_payload(WC_Order $order): array
    {
        return $this->build_common_redirect_payload($order);
    }

    private function build_common_redirect_payload(WC_Order $order): array
    {
        return [
            'amount' => (float) $order->get_total(),
            'currency' => $order->get_currency(),
            'customer_email' => $order->get_billing_email(),
            'customer_phone' => $order->get_billing_phone(),
            'customer_name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'merchant_id' => $this->get_option('merchant_id'),
            'product_description' => $this->get_product_description($order),
            'order_id' => (string) $order->get_id(),
            'success_url' => $this->get_atozpay_return_url($order),
            'return_url' => $this->get_atozpay_return_url($order),
            'callback_url' => home_url('/?wc-api=atozpay_direct_callback'),
        ];
    }

    private function get_atozpay_return_url(WC_Order $order): string
    {
        return add_query_arg(
            [
                'order_id' => $order->get_id(),
            ],
            home_url('/?wc-api=atozpay_direct_return')
        );
    }

    private function get_enabled_payment_methods(): array
    {
        $methods = [];

        if ($this->get_option('card_enabled', 'yes') === 'yes') {
            $methods[] = 'card';
        }

        if ($this->get_option('fpx_enabled', 'yes') === 'yes') {
            $methods[] = 'fpx';
        }

        if ($this->get_option('tng_enabled', 'yes') === 'yes') {
            $methods[] = 'tng';
        }

        return $methods ?: ['card'];
    }

    private function get_default_payment_method(array $methods = []): string
    {
        $methods = $methods ?: $this->get_enabled_payment_methods();
        return $methods[0] ?? 'card';
    }

    private function get_fpx_banks(): array
    {
        return [
            'TEST0021' => __('FPX Test Bank', 'atozpay-direct'),
            'RHB0218' => __('RHB Bank', 'atozpay-direct'),
            'MB2U0227' => __('Maybank2u', 'atozpay-direct'),
            'MBB0228' => __('Maybank2E', 'atozpay-direct'),
            'CIMB0229' => __('CIMB Clicks', 'atozpay-direct'),
            'PBB0233' => __('Public Bank', 'atozpay-direct'),
            'HLB0224' => __('Hong Leong Bank', 'atozpay-direct'),
            'BIMB0340' => __('Bank Islam', 'atozpay-direct'),
            'AMBANK0226' => __('AmBank', 'atozpay-direct'),
            'BMMB0341' => __('Bank Muamalat', 'atozpay-direct'),
            'BSN0601' => __('BSN', 'atozpay-direct'),
            'OCBC0229' => __('OCBC Bank', 'atozpay-direct'),
            'SCB0216' => __('Standard Chartered', 'atozpay-direct'),
            'UOB0226' => __('UOB Bank', 'atozpay-direct'),
            'HSBC0223' => __('HSBC Bank', 'atozpay-direct'),
            'KFH0346' => __('KFH Malaysia', 'atozpay-direct'),
        ];
    }

    private function get_posted_payment_data(): array
    {
        $posted = [];

        foreach ($_POST as $key => $value) {
            if (strpos((string) $key, 'atozpay_direct_') !== 0) {
                continue;
            }

            $posted[$key] = is_scalar($value)
                ? sanitize_text_field(wp_unslash((string) $value))
                : '';
        }

        if (isset($_POST['payment_data'])) {
            $payment_data = wp_unslash($_POST['payment_data']);

            if (is_string($payment_data)) {
                $decoded = json_decode($payment_data, true);
                if (is_array($decoded)) {
                    $payment_data = $decoded;
                }
            }

            if (is_array($payment_data)) {
                foreach ($payment_data as $key => $item) {
                    if (is_string($key) && strpos($key, 'atozpay_direct_') === 0) {
                        $posted[$key] = is_scalar($item) ? sanitize_text_field((string) $item) : '';
                        continue;
                    }

                    if (is_array($item) && isset($item['key'], $item['value'])) {
                        $posted[(string) $item['key']] = is_scalar($item['value']) ? sanitize_text_field((string) $item['value']) : '';
                    } elseif (is_array($item)) {
                        foreach ($item as $nested_key => $value) {
                            if (strpos((string) $nested_key, 'atozpay_direct_') === 0) {
                                $posted[(string) $nested_key] = is_scalar($value) ? sanitize_text_field((string) $value) : '';
                            }
                        }
                    }
                }
            }
        }

        return $posted;
    }

    private function get_product_description(WC_Order $order): string
    {
        $names = [];

        foreach ($order->get_items() as $item) {
            $names[] = $item->get_name();
        }

        $description = implode(', ', array_slice($names, 0, 5));

        if ($description === '') {
            $description = 'WooCommerce Order #' . $order->get_id();
        }

        return wp_strip_all_tags(wp_trim_words($description, 24, '...'));
    }

    private function build_redirect_url(string $link): string
    {
        if (preg_match('#^https?://#i', $link)) {
            return esc_url_raw($link);
        }

        $base = untrailingslashit($this->get_option('base_url', 'https://agents.atozpay.net'));
        return esc_url_raw($base . '/' . ltrim($link, '/'));
    }
}
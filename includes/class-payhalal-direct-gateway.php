<?php

defined('ABSPATH') || exit;

class WC_Gateway_PayHalal_Direct extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'payhalal_direct';
        $this->icon = '';
        $this->has_fields = true;
        $this->method_title = __('PayHalal Direct', 'payhalal-direct');
        $this->method_description = __('Accept card payments directly through PayHalal Direct. FPX and eWallet options can be added in future versions.', 'payhalal-direct');
        $this->supports = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', 'PayHalal Direct');
        $this->description = $this->get_option('description', 'Accept card, FPX and eWallet payments securely through PayHalal Direct.');
        $this->enabled = $this->get_option('enabled', 'no');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function init_form_fields(): void
    {
        $callback_url = home_url('/?wc-api=payhalal_direct_callback');
        $return_url = home_url('/?wc-api=payhalal_direct_return');

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'payhalal-direct'),
                'type' => 'checkbox',
                'label' => __('Enable PayHalal Direct', 'payhalal-direct'),
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Checkout Title', 'payhalal-direct'),
                'type' => 'text',
                'default' => __('PayHalal Direct', 'payhalal-direct'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Checkout Description', 'payhalal-direct'),
                'type' => 'textarea',
                'default' => __('Accept card, FPX and eWallet payments securely through PayHalal Direct.', 'payhalal-direct'),
            ],
            'base_url' => [
                'title' => __('API Base URL', 'payhalal-direct'),
                'type' => 'text',
                'default' => 'https://agents.souqafintech.com',
            ],
            'app_id' => [
                'title' => __('App ID', 'payhalal-direct'),
                'type' => 'text',
                'default' => '',
            ],
            'app_secret' => [
                'title' => __('App Secret', 'payhalal-direct'),
                'type' => 'password',
                'default' => '',
            ],
            'merchant_id' => [
                'title' => __('Merchant ID', 'payhalal-direct'),
                'type' => 'text',
                'default' => '',
            ],
            'card_enabled' => [
                'title' => __('Card Payment', 'payhalal-direct'),
                'type' => 'checkbox',
                'label' => __('Enable Card payment option on checkout', 'payhalal-direct'),
                'default' => 'yes',
            ],
            'show_future_methods' => [
                'title' => __('Future Methods', 'payhalal-direct'),
                'type' => 'checkbox',
                'label' => __('Show FPX and TNG as Coming Soon options', 'payhalal-direct'),
                'default' => 'yes',
            ],
            'debug' => [
                'title' => __('Debug Log', 'payhalal-direct'),
                'type' => 'checkbox',
                'label' => __('Enable safe debug logging', 'payhalal-direct'),
                'default' => 'no',
                'description' => __('Logs API requests, responses and WooCommerce gateway events to WooCommerce → Status → Logs. Sensitive card data and secrets are redacted.', 'payhalal-direct'),
            ],
            'debug_checkout_errors' => [
                'title' => __('Debug Checkout Errors', 'payhalal-direct'),
                'type' => 'checkbox',
                'label' => __('Show detailed PayHalal error messages on checkout while debugging', 'payhalal-direct'),
                'default' => 'no',
                'description' => __('Only enable on staging or during internal testing. Keep disabled for live customers.', 'payhalal-direct'),
            ],
            'return_url' => [
                'title' => __('Return URL', 'payhalal-direct'),
                'type' => 'title',
                'description' => '<code>' . esc_html($return_url) . '</code><p class="description">PayHalal Direct uses this URL to return customers back to WooCommerce after payment.</p>',
            ],
            'callback_url' => [
                'title' => __('Callback URL', 'payhalal-direct'),
                'type' => 'title',
                'description' => '<code>' . esc_html($callback_url) . '</code><p class="description">Use this URL in the PayHalal merchant configuration if callback URL is required.</p>',
            ],
        ];
    }

    public function enqueue_assets(): void
    {
        if (!is_checkout()) {
            return;
        }

        wp_enqueue_style('payhalal-direct-checkout', PAYHALAL_DIRECT_URL . 'assets/css/checkout.css', [], PAYHALAL_DIRECT_VERSION);
        wp_enqueue_script('payhalal-direct-checkout', PAYHALAL_DIRECT_URL . 'assets/js/checkout.js', [], PAYHALAL_DIRECT_VERSION, true);
    }

    public function payment_fields(): void
    {
        $show_future = $this->get_option('show_future_methods', 'yes') === 'yes';
        ?>
        <div class="payhalal-direct-box">
            <?php if ($this->description) : ?>
                <div class="payhalal-direct-description"><?php echo wp_kses_post(wpautop($this->description)); ?></div>
            <?php endif; ?>

            <div class="payhalal-direct-header">
                <div class="payhalal-direct-title">
                    <span class="payhalal-direct-mark" aria-hidden="true">⌁</span>
                    <div>
                        <strong><?php esc_html_e('PayHalal Direct', 'payhalal-direct'); ?></strong>
                        <span><?php esc_html_e('Secure card payment powered by PayHalal', 'payhalal-direct'); ?></span>
                    </div>
                </div>
                <em class="payhalal-direct-badge"><?php esc_html_e('Secure', 'payhalal-direct'); ?></em>
            </div>

            <div class="payhalal-direct-methods" role="radiogroup" aria-label="<?php esc_attr_e('PayHalal Direct payment method', 'payhalal-direct'); ?>">
                <label class="payhalal-direct-method is-active">
                    <input type="radio" name="payhalal_direct_method" value="card" checked>
                    <span class="payhalal-direct-method-icon" aria-hidden="true">💳</span>
                    <span><strong><?php esc_html_e('Card', 'payhalal-direct'); ?></strong><small><?php esc_html_e('Visa, Mastercard and supported debit cards', 'payhalal-direct'); ?></small></span>
                </label>

                <?php if ($show_future) : ?>
                    <label class="payhalal-direct-method is-disabled">
                        <input type="radio" name="payhalal_direct_method" value="fpx" disabled>
                        <span class="payhalal-direct-method-icon" aria-hidden="true">🏦</span>
                        <span><strong><?php esc_html_e('FPX Online Banking', 'payhalal-direct'); ?></strong><small><?php esc_html_e('Coming soon', 'payhalal-direct'); ?></small></span>
                    </label>
                    <label class="payhalal-direct-method is-disabled">
                        <input type="radio" name="payhalal_direct_method" value="tng" disabled>
                        <span class="payhalal-direct-method-icon" aria-hidden="true">📱</span>
                        <span><strong><?php esc_html_e('TNG eWallet', 'payhalal-direct'); ?></strong><small><?php esc_html_e('Coming soon', 'payhalal-direct'); ?></small></span>
                    </label>
                <?php endif; ?>
            </div>

            <div class="payhalal-direct-card-panel payhalal-direct-card-panel-modern">
                <div class="payhalal-direct-card-hero">
                    <div class="payhalal-direct-card-preview" aria-hidden="true">
                        <div class="payhalal-direct-card-glow"></div>

                        <div class="payhalal-direct-card-preview-top">
                            <span class="payhalal-direct-card-chip"></span>
                            <span class="payhalal-direct-card-brand-preview">CARD</span>
                        </div>

                        <div class="payhalal-direct-card-number-preview">•••• •••• •••• ••••</div>

                        <div class="payhalal-direct-card-preview-bottom">
                            <span>
                                <span class="payhalal-direct-card-label"><?php esc_html_e('Cardholder', 'payhalal-direct'); ?></span>
                                <span class="payhalal-direct-card-holder-preview"><?php esc_html_e('Name on card', 'payhalal-direct'); ?></span>
                            </span>
                            <span>
                                <span class="payhalal-direct-card-label"><?php esc_html_e('Expires', 'payhalal-direct'); ?></span>
                                <span class="payhalal-direct-card-exp-preview">MM/YY</span>
                            </span>
                        </div>
                    </div>

                    <div class="payhalal-direct-card-copy">
                        <span class="payhalal-direct-secure-label"><?php esc_html_e('Secure Checkout', 'payhalal-direct'); ?></span>
                        <h4><?php esc_html_e('Enter your card details', 'payhalal-direct'); ?></h4>
                        <p><?php esc_html_e('Your payment is processed securely through PayHalal Direct. Card details are never stored on this store.', 'payhalal-direct'); ?></p>
                    </div>
                </div>

                <div class="payhalal-direct-grid payhalal-direct-grid-modern">
                    <p class="payhalal-direct-field full">
                        <label for="payhalal_direct_card_holder_name"><?php esc_html_e('Cardholder Name', 'payhalal-direct'); ?></label>
                        <input id="payhalal_direct_card_holder_name" name="payhalal_direct_card_holder_name" type="text" autocomplete="cc-name" placeholder="Name on card">
                    </p>

                    <p class="payhalal-direct-field full has-card-brand">
                        <label for="payhalal_direct_card_number"><?php esc_html_e('Card Number', 'payhalal-direct'); ?></label>
                        <input id="payhalal_direct_card_number" name="payhalal_direct_card_number" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="1234 1234 1234 1234" maxlength="23">
                        <span class="payhalal-direct-brand" aria-live="polite"></span>
                    </p>

                    <div class="payhalal-direct-expiry-group">
                        <p class="payhalal-direct-field">
                            <label for="payhalal_direct_card_exp_mn"><?php esc_html_e('Month', 'payhalal-direct'); ?></label>
                            <input id="payhalal_direct_card_exp_mn" name="payhalal_direct_card_exp_mn" type="text" inputmode="numeric" autocomplete="cc-exp-month" placeholder="MM" maxlength="2">
                        </p>

                        <p class="payhalal-direct-field">
                            <label for="payhalal_direct_card_exp_yy"><?php esc_html_e('Year', 'payhalal-direct'); ?></label>
                            <input id="payhalal_direct_card_exp_yy" name="payhalal_direct_card_exp_yy" type="text" inputmode="numeric" autocomplete="cc-exp-year" placeholder="YY" maxlength="4">
                        </p>
                    </div>

                    <p class="payhalal-direct-field">
                        <label for="payhalal_direct_card_cvv"><?php esc_html_e('CVV', 'payhalal-direct'); ?></label>
                        <input id="payhalal_direct_card_cvv" name="payhalal_direct_card_cvv" type="password" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4">
                    </p>
                </div>

                <div class="payhalal-direct-trust-row payhalal-direct-trust-row-modern">
                    <span class="payhalal-direct-trust-pill">🔒 <?php esc_html_e('Encrypted payment', 'payhalal-direct'); ?></span>
                    <span class="payhalal-direct-trust-pill">🛡️ <?php esc_html_e('No card data stored', 'payhalal-direct'); ?></span>
                    <span class="payhalal-direct-trust-pill">✅ <?php esc_html_e('Order status sync', 'payhalal-direct'); ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    public function validate_fields(): bool
    {
        $posted = $this->get_posted_payment_data();
        $method = isset($posted['payhalal_direct_method']) ? sanitize_text_field($posted['payhalal_direct_method']) : 'card';

        if ($method !== 'card') {
            wc_add_notice(__('Selected PayHalal Direct method is not available yet.', 'payhalal-direct'), 'error');
            return false;
        }

        if ($this->get_option('card_enabled', 'yes') !== 'yes') {
            wc_add_notice(__('Card payments are not enabled for PayHalal Direct.', 'payhalal-direct'), 'error');
            return false;
        }

        $required = [
            'payhalal_direct_card_holder_name' => __('Cardholder name is required.', 'payhalal-direct'),
            'payhalal_direct_card_number' => __('Card number is required.', 'payhalal-direct'),
            'payhalal_direct_card_exp_mn' => __('Expiry month is required.', 'payhalal-direct'),
            'payhalal_direct_card_exp_yy' => __('Expiry year is required.', 'payhalal-direct'),
            'payhalal_direct_card_cvv' => __('CVV is required.', 'payhalal-direct'),
        ];

        foreach ($required as $field => $message) {
            if (empty($posted[$field])) {
                wc_add_notice($message, 'error');
                return false;
            }
        }

        $card_number = preg_replace('/\D+/', '', sanitize_text_field($posted['payhalal_direct_card_number'] ?? ''));
        $month = preg_replace('/\D+/', '', sanitize_text_field($posted['payhalal_direct_card_exp_mn'] ?? ''));
        $year = preg_replace('/\D+/', '', sanitize_text_field($posted['payhalal_direct_card_exp_yy'] ?? ''));
        $cvv = preg_replace('/\D+/', '', sanitize_text_field($posted['payhalal_direct_card_cvv'] ?? ''));

        if (strlen($card_number) < 12 || strlen($card_number) > 19) {
            wc_add_notice(__('Please enter a valid card number.', 'payhalal-direct'), 'error');
            return false;
        }

        if ((int) $month < 1 || (int) $month > 12) {
            wc_add_notice(__('Please enter a valid expiry month.', 'payhalal-direct'), 'error');
            return false;
        }

        if (!in_array(strlen($year), [2, 4], true)) {
            wc_add_notice(__('Please enter a valid expiry year.', 'payhalal-direct'), 'error');
            return false;
        }

        if (strlen($cvv) < 3 || strlen($cvv) > 4) {
            wc_add_notice(__('Please enter a valid CVV.', 'payhalal-direct'), 'error');
            return false;
        }

        return true;
    }

    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            wc_add_notice(__('Unable to locate WooCommerce order.', 'payhalal-direct'), 'error');
            return ['result' => 'failure'];
        }

        $debug_enabled = $this->get_option('debug', 'no') === 'yes';
        $logger = new PayHalal_Direct_Logger($debug_enabled);

        try {
            $logger->debug('Starting PayHalal Direct payment for WooCommerce order #' . $order->get_id(), [
                'order_id' => $order->get_id(),
                'amount' => $order->get_total(),
                'currency' => $order->get_currency(),
            ]);

            $api = new PayHalal_Direct_API(
                $this->get_option('base_url', 'https://agents.souqafintech.com'),
                $this->get_option('app_id'),
                $this->get_option('app_secret'),
                $debug_enabled
            );

            $payload = $this->build_card_payload($order);
            $logger->debug('Built PayHalal Direct card payload for order #' . $order->get_id(), $payload);

            $response = $api->create_card_payment($payload);
            $logger->debug('PayHalal Direct card payment response received for order #' . $order->get_id(), $response);

            $transaction_id = isset($response['transaction_id']) ? sanitize_text_field($response['transaction_id']) : '';
            $link = isset($response['link']) ? (string) $response['link'] : '';

            if (!$link) {
                throw new Exception(__('Payment link missing from PayHalal Direct response.', 'payhalal-direct'));
            }

            $redirect_url = $this->build_redirect_url($link);

            $logger->debug('PayHalal Direct redirect URL prepared for order #' . $order->get_id(), [
                'transaction_id' => $transaction_id,
                'redirect' => $redirect_url,
            ]);

            if ($transaction_id) {
                $order->update_meta_data('_payhalal_direct_transaction_id', $transaction_id);
            }

            $order->update_meta_data('_payhalal_direct_payment_method', 'card');
            $order->add_order_note('PayHalal Direct card payment initiated' . ($transaction_id ? '. Transaction ID: ' . $transaction_id : '.'));
            $order->save();

            WC()->cart->empty_cart();

            return [
                'result' => 'success',
                'redirect' => $redirect_url,
            ];
        } catch (Exception $e) {
            $logger->debug('PayHalal Direct payment failed for WooCommerce order #' . $order->get_id(), [
                'error' => $e->getMessage(),
            ]);

            $order->add_order_note('PayHalal Direct payment error: ' . $e->getMessage());

            if ($this->get_option('debug_checkout_errors', 'no') === 'yes' && current_user_can('manage_woocommerce')) {
                wc_add_notice(sprintf(__('PayHalal Debug: %s', 'payhalal-direct'), esc_html($e->getMessage())), 'error');
            } else {
                wc_add_notice(__('Payment could not be started. Please try again or use another payment method.', 'payhalal-direct'), 'error');
            }

            return ['result' => 'failure'];
        }
    }

    private function build_card_payload(WC_Order $order): array
    {
        $posted = $this->get_posted_payment_data();

        $holder = sanitize_text_field($posted['payhalal_direct_card_holder_name'] ?? '');
        $number = preg_replace('/\D+/', '', sanitize_text_field($posted['payhalal_direct_card_number'] ?? ''));
        $month = preg_replace('/\D+/', '', sanitize_text_field($posted['payhalal_direct_card_exp_mn'] ?? ''));
        $year = preg_replace('/\D+/', '', sanitize_text_field($posted['payhalal_direct_card_exp_yy'] ?? ''));
        $cvv = preg_replace('/\D+/', '', sanitize_text_field($posted['payhalal_direct_card_cvv'] ?? ''));

        if (strlen($year) === 4) {
            $year = substr($year, -2);
        }

        $payhalal_return_url = add_query_arg(
            [
                'order_id' => $order->get_id(),
            ],
            home_url('/?wc-api=payhalal_direct_return')
        );

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
            'success_url' => $payhalal_return_url,
            'return_url' => $payhalal_return_url,
            'callback_url' => home_url('/?wc-api=payhalal_direct_callback'),
        ];
    }

    private function get_posted_payment_data(): array
    {
        $posted = [];

        foreach ($_POST as $key => $value) {
            if (strpos((string) $key, 'payhalal_direct_') !== 0) {
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
                    if (is_string($key) && strpos($key, 'payhalal_direct_') === 0) {
                        $posted[$key] = is_scalar($item) ? sanitize_text_field((string) $item) : '';
                        continue;
                    }

                    if (is_array($item) && isset($item['key'], $item['value'])) {
                        $posted[(string) $item['key']] = is_scalar($item['value']) ? sanitize_text_field((string) $item['value']) : '';
                    } elseif (is_array($item)) {
                        foreach ($item as $nested_key => $value) {
                            if (strpos((string) $nested_key, 'payhalal_direct_') === 0) {
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

        $base = untrailingslashit($this->get_option('base_url', 'https://agents.souqafintech.com'));
        return esc_url_raw($base . '/' . ltrim($link, '/'));
    }
}
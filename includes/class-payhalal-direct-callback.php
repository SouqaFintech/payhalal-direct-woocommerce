<?php

defined('ABSPATH') || exit;

class PayHalal_Direct_Callback
{
    public static function init(): void
    {
        add_action('woocommerce_api_payhalal_direct_callback', [__CLASS__, 'handle']);
        add_action('woocommerce_api_payhalal_callback', [__CLASS__, 'handle']); // Backward-friendly alias.
    }

    public static function handle(): void
    {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw ?: '', true);

        if (!is_array($payload)) {
            $payload = array_merge($_GET, $_POST);
        }

        $order_id = isset($payload['order_id']) ? sanitize_text_field(wp_unslash($payload['order_id'])) : '';
        $transaction_id = isset($payload['transaction_id']) ? sanitize_text_field(wp_unslash($payload['transaction_id'])) : '';
        $status = isset($payload['status']) ? strtoupper(sanitize_text_field(wp_unslash($payload['status']))) : '';

        $order = $order_id ? wc_get_order($order_id) : false;

        if (!$order && $transaction_id) {
            $orders = wc_get_orders([
                'limit' => 1,
                'meta_key' => '_payhalal_direct_transaction_id',
                'meta_value' => $transaction_id,
                'return' => 'objects',
            ]);
            $order = !empty($orders) ? $orders[0] : false;
        }

        if (!$order) {
            status_header(404);
            wp_send_json(['status' => 'error', 'message' => 'Order not found']);
        }

        if ($transaction_id) {
            $order->update_meta_data('_payhalal_direct_transaction_id', $transaction_id);
        }

        $order->update_meta_data('_payhalal_direct_last_callback', wp_json_encode($payload));

        self::apply_status($order, $status);
        $order->save();

        wp_send_json(['status' => 'ok']);
    }

    public static function apply_status(WC_Order $order, string $status): void
    {
        $paid_statuses = ['PAID', 'SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'APPROVED', 'CAPTURED'];
        $failed_statuses = ['FAILED', 'FAIL', 'DECLINED', 'CANCELLED', 'CANCELED', 'EXPIRED'];
        $pending_statuses = ['INITIATED', 'PENDING', 'PROCESSING'];

        if (in_array($status, $paid_statuses, true)) {
            if (!$order->is_paid()) {
                $order->payment_complete($order->get_meta('_payhalal_direct_transaction_id'));
                $order->add_order_note('PayHalal Direct payment completed via callback.');
            }
            return;
        }

        if (in_array($status, $failed_statuses, true)) {
            $order->update_status('failed', 'PayHalal Direct payment failed via callback. Status: ' . $status);
            return;
        }

        if (in_array($status, $pending_statuses, true)) {
            if ($order->has_status(['pending'])) {
                $order->update_status('on-hold', 'PayHalal Direct payment is pending. Status: ' . $status);
            } else {
                $order->add_order_note('PayHalal Direct callback status: ' . $status);
            }
            return;
        }

        $order->add_order_note('PayHalal Direct callback received. Status: ' . ($status ?: 'UNKNOWN'));
    }
}

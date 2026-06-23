<?php

defined('ABSPATH') || exit;

class PayHalal_Direct_API
{
    private string $base_url;
    private string $app_id;
    private string $app_secret;
    private PayHalal_Direct_Logger $logger;

    public function __construct(string $base_url, string $app_id, string $app_secret, bool $debug = false)
    {
        $this->base_url = untrailingslashit($base_url ?: 'https://agents.souqafintech.com');
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        $this->logger = new PayHalal_Direct_Logger($debug);
    }

    public function create_card_payment(array $payload): array
    {
        $token = $this->get_token();

        return $this->request('POST', '/acquiring/cards', $payload, [
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    public function get_transaction(string $transaction_id): array
    {
        $token = $this->get_token();

        return $this->request('GET', '/acquiring/transaction/' . rawurlencode($transaction_id), [], [
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    public function get_order(string $order_id): array
    {
        $token = $this->get_token();

        return $this->request('GET', '/acquiring/order/' . rawurlencode($order_id), [], [
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    private function get_token(): string
    {
        $cache_key = 'payhalal_direct_token_' . md5($this->app_id . '|' . $this->base_url);
        $cached = get_transient($cache_key);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = $this->request('POST', '/auth', [
            'app_id' => $this->app_id,
            'app_secret' => $this->app_secret,
        ], [], false);

        if (empty($response['token'])) {
            throw new Exception('PayHalal Direct authentication failed. Token missing from response.');
        }

        set_transient($cache_key, sanitize_text_field($response['token']), 30 * MINUTE_IN_SECONDS);

        return sanitize_text_field($response['token']);
    }

    private function request(string $method, string $path, array $body = [], array $headers = [], bool $auth_required = true): array
    {
        $url = $this->base_url . '/' . ltrim($path, '/');

        $args = [
            'method' => strtoupper($method),
            'timeout' => 45,
            'headers' => array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Version' => 'v1',
            ], $headers),
        ];

        if (strtoupper($method) !== 'GET') {
            $args['body'] = wp_json_encode($body);
        }

        $this->logger->debug('Request: ' . strtoupper($method) . ' ' . $path, $body);

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->logger->debug('WP Error: ' . $response->get_error_message());
            throw new Exception($response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw_body = (string) wp_remote_retrieve_body($response);
        $decoded = json_decode($raw_body, true);

        $this->logger->debug('Response HTTP ' . $code, is_array($decoded) ? $decoded : $raw_body);

        if (!is_array($decoded)) {
            throw new Exception('Invalid PayHalal Direct API response.');
        }

        if ($code < 200 || $code >= 300) {
            $message = $decoded['status_text'] ?? $decoded['message'] ?? 'PayHalal Direct API request failed.';
            throw new Exception((string) $message);
        }

        return $decoded;
    }
}

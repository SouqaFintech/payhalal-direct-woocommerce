<?php

defined('ABSPATH') || exit;

class AtozPay_Direct_Logger
{
    private bool $enabled;
    private ?WC_Logger $logger = null;

    public function __construct(bool $enabled = false)
    {
        $this->enabled = $enabled;
        if (function_exists('wc_get_logger')) {
            $this->logger = wc_get_logger();
        }
    }

    public function debug(string $message, $context = null): void
    {
        if (!$this->enabled || !$this->logger) {
            return;
        }

        $line = $message;
        if ($context !== null) {
            $line .= ' ' . wp_json_encode($this->redact($context));
        }

        $this->logger->debug($line, ['source' => 'atozpay-direct']);
    }

    public function redact($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sensitive = [
            'app_secret', 'card_number', 'card_cvv', 'card_exp_mn', 'card_exp_yy',
            'authorization', 'Authorization', 'token', 'cvv', 'cvc', 'secret',
        ];

        foreach ($data as $key => $value) {
            if (in_array((string) $key, $sensitive, true)) {
                $data[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }
}

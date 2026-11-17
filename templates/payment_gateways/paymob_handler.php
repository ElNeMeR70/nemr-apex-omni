<?php
/**
 * Nemr Omni-SaaS: Drop-in Paymob Payment Gateway (Egypt & Gulf).
 * Supports: Cards, Mobile Wallets (Vodafone Cash, Orange, Etisalat, WE), Meeza, Aman/Fawry Kiosk.
 */

declare(strict_types=1);

class PaymobGateway {
    private string $apiKey;
    private int $integrationIdCard;
    private int $integrationIdWallet;
    private int $iframeId;
    private string $hmacSecret;

    public function __construct(
        string $apiKey,
        int $integrationIdCard,
        int $integrationIdWallet,
        int $iframeId,
        string $hmacSecret
    ) {
        $this->apiKey = $apiKey;
        $this->integrationIdCard = $integrationIdCard;
        $this->integrationIdWallet = $integrationIdWallet;
        $this->iframeId = $iframeId;
        $this->hmacSecret = $hmacSecret;
    }

    /**
     * Step 1: Authentication Request
     */
    public function getAuthToken(): string {
        $url = 'https://accept.paymob.com/api/auth/tokens';
        $res = $this->postJson($url, ['api_key' => $this->apiKey]);
        return $res['token'] ?? throw new Exception('Paymob auth failed: ' . json_encode($res));
    }

    /**
     * Step 2: Order Registration
     */
    public function registerOrder(string $authToken, int $amountCents, string $merchantOrderId): int {
        $url = 'https://accept.paymob.com/api/ecommerce/orders';
        $data = [
            'auth_token' => $authToken,
            'delivery_needed' => 'false',
            'amount_cents' => $amountCents,
            'currency' => 'EGP',
            'merchant_order_id' => $merchantOrderId,
            'items' => []
        ];
        $res = $this->postJson($url, $data);
        return (int)($res['id'] ?? throw new Exception('Paymob order registration failed'));
    }

    /**
     * Step 3: Generate Payment Key
     */
    public function getPaymentKey(
        string $authToken,
        int $orderId,
        int $amountCents,
        array $billingData,
        bool $isWallet = false
    ): string {
        $url = 'https://accept.paymob.com/api/acceptance/payment_keys';
        $integrationId = $isWallet ? $this->integrationIdWallet : $this->integrationIdCard;

        $data = [
            'auth_token' => $authToken,
            'amount_cents' => $amountCents,
            'expiration' => 3600,
            'order_id' => $orderId,
            'billing_data' => $billingData,
            'currency' => 'EGP',
            'integration_id' => $integrationId
        ];
        $res = $this->postJson($url, $data);
        return $res['token'] ?? throw new Exception('Paymob payment key failed');
    }

    /**
     * Verify Webhook HMAC Signature
     */
    public function verifyHmac(array $queryData, string $receivedHmac): bool {
        $keys = [
            'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
            'id', 'integration_id', 'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded',
            'is_standalone_payment', 'is_voided', 'order', 'owner', 'pending',
            'source_data_pan', 'source_data_sub_type', 'source_data_type', 'success'
        ];

        $concatenated = '';
        foreach ($keys as $k) {
            $val = $queryData[$k] ?? '';
            if (is_bool($val)) {
                $concatenated .= $val ? 'true' : 'false';
            } else {
                $concatenated .= (string)$val;
            }
        }

        $calculated = hash_hmac('sha512', $concatenated, $this->hmacSecret);
        return hash_equals($calculated, $receivedHmac);
    }

    private function postJson(string $url, array $payload): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 20
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode((string)$response, true) ?: [];
    }
}

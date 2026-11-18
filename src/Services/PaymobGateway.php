<?php
declare(strict_types=1);

namespace Nemr\Apex\Services;

/**
 * Enterprise Egyptian Payment Gateway Integration (Paymob Accept)
 * Supports Card, Mobile Wallets (Vodafone Cash, Orange, Etisalat, InstaPay), Aman/Masary Kiosks.
 */
class PaymobGateway
{
    protected string $apiKey;
    protected string $hmacSecret;
    protected string $merchantId;

    public function __construct(string $apiKey = '', string $hmacSecret = '', string $merchantId = '')
    {
        $this->apiKey = $apiKey ?: (getenv('PAYMOB_API_KEY') ?: '');
        $this->hmacSecret = $hmacSecret ?: (getenv('PAYMOB_HMAC') ?: '');
        $this->merchantId = $merchantId ?: (getenv('PAYMOB_MERCHANT_ID') ?: '');
    }

    /**
     * Verifies Paymob transaction callback HMAC-SHA512
     */
    public function verifyCallback(array $data, string $receivedHmac): bool
    {
        $keys = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order.id',
            'owner',
            'pending',
            'source_data.pan',
            'source_data.sub_type',
            'source_data.type',
            'success'
        ];

        $concatenated = '';
        foreach ($keys as $key) {
            $val = '';
            if (str_contains($key, '.')) {
                [$parent, $child] = explode('.', $key);
                $val = $data[$parent][$child] ?? '';
            } else {
                $val = $data[$key] ?? '';
            }

            if (is_bool($val)) {
                $val = $val ? 'true' : 'false';
            }
            $concatenated .= (string)$val;
        }

        $calculatedHmac = hash_hmac('sha512', $concatenated, $this->hmacSecret);
        return hash_equals($calculatedHmac, $receivedHmac);
    }

    /**
     * Helper to authenticate with Paymob API
     */
    public function authenticate(): ?string
    {
        $ch = curl_init('https://accept.paymob.com/api/auth/tokens');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['api_key' => $this->apiKey]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $resData = json_decode($response ?: '', true);
        return $resData['token'] ?? null;
    }
}

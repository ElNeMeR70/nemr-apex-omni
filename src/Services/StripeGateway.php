<?php
declare(strict_types=1);

namespace Nemr\Apex\Services;

/**
 * Enterprise International Stripe Checkout & Webhook Signature Verifier
 */
class StripeGateway
{
    protected string $secretKey;
    protected string $webhookSecret;

    public function __construct(string $secretKey = '', string $webhookSecret = '')
    {
        $this->secretKey = $secretKey ?: (getenv('STRIPE_SECRET_KEY') ?: '');
        $this->webhookSecret = $webhookSecret ?: (getenv('STRIPE_WEBHOOK_SECRET') ?: '');
    }

    /**
     * Verifies Stripe Webhook signature header (`Stripe-Signature: t=...,v1=...`)
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader, int $tolerance = 300): bool
    {
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $sigHeader) as $item) {
            $parts = explode('=', trim($item), 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = (int)$parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }
        }

        if (!$timestamp || empty($signatures)) {
            return false;
        }

        // Check timestamp drift
        if (abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                return true;
            }
        }

        return false;
    }
}

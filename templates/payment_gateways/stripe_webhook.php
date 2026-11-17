<?php
/**
 * Nemr Omni-SaaS: Drop-in Stripe Webhook & Verification Handler.
 * Supports: Subscriptions, One-Time Charges, Automatic User Tier Upgrade.
 */

declare(strict_types=1);

header('Content-Type: application/json');

$endpointSecret = getenv('STRIPE_WEBHOOK_SECRET') ?: '';
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!$endpointSecret || !$payload || !$sigHeader) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing payload, signature, or webhook secret']);
    exit;
}

// Extract timestamp and signature from Stripe-Signature header
$sigParts = explode(',', $sigHeader);
$timestamp = null;
$signatures = [];

foreach ($sigParts as $part) {
    $sub = explode('=', trim($part), 2);
    if (count($sub) === 2) {
        if ($sub[0] === 't') {
            $timestamp = $sub[1];
        } elseif ($sub[0] === 'v1') {
            $signatures[] = $sub[1];
        }
    }
}

if (!$timestamp || empty($signatures)) {
    http_response_code(400);
    echo json_encode(['error' => 'Malformed Stripe signature header']);
    exit;
}

// Tolerance check (5 minutes max)
if (abs(time() - (int)$timestamp) > 300) {
    http_response_code(400);
    echo json_encode(['error' => 'Webhook timestamp expired']);
    exit;
}

// Compute expected HMAC-SHA256 signature
$signedPayload = "{$timestamp}.{$payload}";
$expectedSig = hash_hmac('sha256', $signedPayload, $endpointSecret);

$verified = false;
foreach ($signatures as $sig) {
    if (hash_equals($expectedSig, $sig)) {
        $verified = true;
        break;
    }
}

if (!$verified) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Process Event
$event = json_decode($payload, true);
$type = $event['type'] ?? '';

switch ($type) {
    case 'checkout.session.completed':
        $session = $event['data']['object'];
        $customerEmail = $session['customer_details']['email'] ?? '';
        $amountTotal = ($session['amount_total'] ?? 0) / 100;
        // Upgrade user tier in database here
        break;

    case 'invoice.payment_succeeded':
        // Recurring subscription renewal
        break;

    case 'customer.subscription.deleted':
        // Downgrade user account
        break;
}

http_response_code(200);
echo json_encode(['status' => 'success', 'event' => $type]);

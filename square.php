<?php
require_once __DIR__ . '/config.php';

function createSquarePayment(string $sourceId, int $amountCents, string $currency = 'USD'): array {
    $url  = SQUARE_API_URL . '/v2/payments';
    $body = json_encode([
        'source_id'      => $sourceId,
        'idempotency_key' => uniqid('vr_', true),
        'amount_money'   => [
            'amount'   => $amountCents,
            'currency' => $currency,
        ],
        'location_id' => SQUARE_LOCATION_ID,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SQUARE_ACCESS_TOKEN,
            'Square-Version: 2024-01-18',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode === 200 && !empty($data['payment']['id'])) {
        return [
            'success'    => true,
            'payment_id' => $data['payment']['id'],
        ];
    }

    $errorMsg = 'Payment failed.';
    if (!empty($data['errors'])) {
        $errorMsg = $data['errors'][0]['detail'] ?? $errorMsg;
    }

    return ['success' => false, 'error' => $errorMsg];
}

<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/square.php';

header('Content-Type: application/json');

$user = getCurrentUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($user['paid']) {
    echo json_encode(['success' => true, 'message' => 'Already paid']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$sourceId = $input['source_id'] ?? '';

if (empty($sourceId)) {
    echo json_encode(['success' => false, 'error' => 'Missing payment token']);
    exit;
}

$result = createSquarePayment($sourceId, PAYMENT_AMOUNT, PAYMENT_CURRENCY);

if ($result['success']) {
    $db   = getDB();
    $stmt = $db->prepare('UPDATE vdr_users SET paid = 1, payment_id = ?, paid_at = NOW() WHERE id = ?');
    $stmt->execute([$result['payment_id'], $user['id']]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $result['error']]);
}

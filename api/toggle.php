<?php
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

$user = getCurrentUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$itemId = (int) ($input['item_id'] ?? 0);
$field  = $input['field'] ?? '';
$value  = (int) ($input['value'] ?? 0);

if (!$itemId || !in_array($field, ['seen', 'done']) || !in_array($value, [0, 1])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$db = getDB();

// Get item's level info
$stmt = $db->prepare(
    'SELECT i.id, l.level_number FROM vdr_items i JOIN vdr_levels l ON l.id = i.level_id WHERE i.id = ?'
);
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['success' => false, 'error' => 'Item not found']);
    exit;
}

$levelNum = (int) $item['level_number'];

// Check access
if (!hasAccess($user, $levelNum)) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Check previous level is complete (unless level 1)
if ($levelNum > 1) {
    $stmt = $db->prepare(
        'SELECT COUNT(*) AS total,
                SUM(CASE WHEN up.seen = 1 AND up.done = 1 THEN 1 ELSE 0 END) AS completed
         FROM vdr_items i
         JOIN vdr_levels l ON l.id = i.level_id
         LEFT JOIN vdr_user_progress up ON up.item_id = i.id AND up.user_id = ?
         WHERE l.level_number = ?'
    );
    $stmt->execute([$user['id'], $levelNum - 1]);
    $prev = $stmt->fetch();
    if ((int) $prev['total'] !== (int) $prev['completed']) {
        echo json_encode(['success' => false, 'error' => 'Complete previous level first']);
        exit;
    }
}

// Upsert progress
$timestampField = $field . '_at';
if ($value) {
    $sql = "INSERT INTO vdr_user_progress (user_id, item_id, $field, $timestampField)
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE $field = 1, $timestampField = NOW()";
} else {
    $sql = "INSERT INTO vdr_user_progress (user_id, item_id, $field, $timestampField)
            VALUES (?, ?, 0, NULL)
            ON DUPLICATE KEY UPDATE $field = 0, $timestampField = NULL";
}
$stmt = $db->prepare($sql);
$stmt->execute([$user['id'], $itemId]);

// Check if current level is now complete
$stmt = $db->prepare(
    'SELECT COUNT(*) AS total,
            SUM(CASE WHEN up.seen = 1 AND up.done = 1 THEN 1 ELSE 0 END) AS completed
     FROM vdr_items i
     JOIN vdr_levels l ON l.id = i.level_id
     LEFT JOIN vdr_user_progress up ON up.item_id = i.id AND up.user_id = ?
     WHERE l.level_number = ?'
);
$stmt->execute([$user['id'], $levelNum]);
$current = $stmt->fetch();

echo json_encode([
    'success'        => true,
    'level_complete' => (int) $current['total'] === (int) $current['completed'],
]);

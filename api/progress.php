<?php
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

$user = getCurrentUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$db = getDB();

// Get completion status per level
$stmt = $db->prepare(
    'SELECT l.level_number,
            COUNT(i.id) AS total,
            SUM(CASE WHEN up.seen = 1 AND up.done = 1 THEN 1 ELSE 0 END) AS completed
     FROM vdr_levels l
     JOIN vdr_items i ON i.level_id = l.id
     LEFT JOIN vdr_user_progress up ON up.item_id = i.id AND up.user_id = ?
     GROUP BY l.level_number
     ORDER BY l.level_number'
);
$stmt->execute([$user['id']]);

$levels = [];
foreach ($stmt->fetchAll() as $row) {
    $levels[$row['level_number']] = [
        'total'     => (int) $row['total'],
        'completed' => (int) $row['completed'],
        'complete'  => (int) $row['total'] === (int) $row['completed'],
    ];
}

echo json_encode(['success' => true, 'levels' => $levels, 'paid' => (bool) $user['paid']]);

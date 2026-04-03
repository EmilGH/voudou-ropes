<?php
// Run once to populate levels and items from the JSON checklist
// Usage: php sql/seed.php

require_once __DIR__ . '/../db.php';

$json = file_get_contents(__DIR__ . '/../../Original Flyer/Voudou Ropes Flyer.json');
$data = json_decode($json, true);

if (!$data || empty($data['levels'])) {
    die("Failed to parse JSON.\n");
}

$db = getDB();

foreach ($data['levels'] as $level) {
    // Insert level
    $stmt = $db->prepare(
        'INSERT INTO vdr_levels (level_number, title) VALUES (?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title)'
    );
    $stmt->execute([$level['level'], $level['title']]);

    // Get level ID
    $stmt = $db->prepare('SELECT id FROM vdr_levels WHERE level_number = ?');
    $stmt->execute([$level['level']]);
    $levelId = $stmt->fetchColumn();

    // Insert items
    foreach ($level['items'] as $sortOrder => $itemName) {
        $stmt = $db->prepare(
            'INSERT INTO vdr_items (level_id, sort_order, name) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name)'
        );
        $stmt->execute([$levelId, $sortOrder + 1, $itemName]);
    }

    echo "Level {$level['level']} ({$level['title']}): " . count($level['items']) . " items seeded.\n";
}

echo "\nDone! All levels and items have been seeded.\n";

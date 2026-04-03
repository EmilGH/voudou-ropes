<?php
require_once __DIR__ . '/auth.php';

$user = requireLogin();
$db   = getDB();

// Load all levels with items
$levels = $db->query(
    'SELECT l.id, l.level_number, l.title,
            i.id AS item_id, i.sort_order, i.name AS item_name, i.video_url
     FROM vdr_levels l
     JOIN vdr_items i ON i.level_id = l.id
     ORDER BY l.level_number, i.sort_order'
)->fetchAll();

// Group by level
$grouped = [];
foreach ($levels as $row) {
    $ln = $row['level_number'];
    if (!isset($grouped[$ln])) {
        $grouped[$ln] = [
            'id'    => $row['id'],
            'num'   => $ln,
            'title' => $row['title'],
            'items' => [],
        ];
    }
    $grouped[$ln]['items'][] = [
        'id'        => $row['item_id'],
        'name'      => $row['item_name'],
        'video_url' => $row['video_url'],
    ];
}

// Load user progress
$stmt = $db->prepare('SELECT item_id, seen, done FROM vdr_user_progress WHERE user_id = ?');
$stmt->execute([$user['id']]);
$progress = [];
foreach ($stmt->fetchAll() as $p) {
    $progress[$p['item_id']] = $p;
}

// Calculate level completion
function isLevelComplete(array $levelItems, array $progress): bool {
    foreach ($levelItems as $item) {
        $p = $progress[$item['id']] ?? null;
        if (!$p || !$p['seen'] || !$p['done']) {
            return false;
        }
    }
    return true;
}

$levelComplete = [];
foreach ($grouped as $ln => $level) {
    $levelComplete[$ln] = isLevelComplete($level['items'], $progress);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist - Voudou Ropes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-left">
            <h1>Voudou Ropes</h1>
        </div>
        <div class="header-right">
            <span class="username"><?= htmlspecialchars($user['username']) ?></span>
            <?php if (isAdmin($user)): ?>
                <a href="admin.php" class="btn btn-small">Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-small btn-outline">Log Out</a>
        </div>
    </header>

    <main class="checklist-main">
        <?php foreach ($grouped as $ln => $level):
            $canAccess     = hasAccess($user, $ln);
            $prevComplete  = ($ln === 1) || $levelComplete[$ln - 1];
            $isUnlocked    = $canAccess && $prevComplete;
            $isComplete    = $levelComplete[$ln];
            $needsPayment  = ($ln > 1 && !$user['paid'] && !isAdmin($user));
        ?>
        <section class="level-section <?= $isUnlocked ? 'unlocked' : 'locked' ?> <?= $isComplete ? 'complete' : '' ?>"
                 data-level="<?= $ln ?>">
            <div class="level-header" onclick="toggleLevel(<?= $ln ?>)">
                <div class="level-title">
                    <span class="level-number">Level <?= $ln ?></span>
                    <span class="level-name"><?= htmlspecialchars($level['title']) ?></span>
                    <?php if ($isComplete): ?>
                        <span class="level-badge badge-complete">Complete</span>
                    <?php elseif (!$isUnlocked && $needsPayment): ?>
                        <span class="level-badge badge-locked">Locked</span>
                    <?php elseif (!$isUnlocked): ?>
                        <span class="level-badge badge-locked">Complete previous level</span>
                    <?php endif; ?>
                </div>
                <span class="level-toggle">&#9660;</span>
            </div>

            <div class="level-items" id="level-items-<?= $ln ?>">
                <?php foreach ($level['items'] as $item):
                    $p    = $progress[$item['id']] ?? ['seen' => 0, 'done' => 0];
                    $both = $p['seen'] && $p['done'];
                ?>
                <div class="checklist-item <?= $both ? 'item-complete' : '' ?>" data-item-id="<?= $item['id'] ?>">
                    <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                    <?php if ($item['video_url']): ?>
                        <a href="<?= htmlspecialchars($item['video_url']) ?>" class="video-link" target="_blank" title="Watch video">&#9654;</a>
                    <?php endif; ?>
                    <div class="item-checks">
                        <label class="check-label">
                            <input type="checkbox" class="check-seen"
                                   data-item="<?= $item['id'] ?>" data-field="seen"
                                   <?= $p['seen'] ? 'checked' : '' ?>
                                   <?= $isUnlocked ? '' : 'disabled' ?>>
                            <span>Seen&nbsp;it</span>
                        </label>
                        <label class="check-label">
                            <input type="checkbox" class="check-done"
                                   data-item="<?= $item['id'] ?>" data-field="done"
                                   <?= $p['done'] ? 'checked' : '' ?>
                                   <?= $isUnlocked ? '' : 'disabled' ?>>
                            <span>Done&nbsp;it</span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($ln === 1 && $isComplete && !$user['paid'] && !isAdmin($user)): ?>
                <div class="payment-cta">
                    <p>You've completed Level 1! Unlock all remaining levels for just <strong>$0.99</strong>.</p>
                    <a href="pay.php" class="btn btn-primary btn-large">Unlock All Levels</a>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endforeach; ?>

        <?php
        // Show payment CTA at bottom if Level 1 complete but unpaid
        $showBottomCTA = $levelComplete[1] && !$user['paid'] && !isAdmin($user);
        if ($showBottomCTA): ?>
        <div class="payment-banner">
            <p>Ready for more? <a href="pay.php" class="btn btn-primary">Unlock All Levels - $0.99</a></p>
        </div>
        <?php endif; ?>
    </main>

    <script>
        const BASE_URL = '<?= BASE_URL ?>';
    </script>
    <script src="assets/app.js"></script>
</body>
</html>

<?php
require_once __DIR__ . '/auth.php';

$user = requireAdmin();
$db   = getDB();

$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $targetId = (int) ($_POST['user_id'] ?? 0);

    if ($targetId && $targetId !== $user['id']) {
        if ($action === 'make_admin') {
            $db->prepare('UPDATE vdr_users SET role = "admin" WHERE id = ?')->execute([$targetId]);
            $message = 'User promoted to admin.';
        } elseif ($action === 'remove_admin') {
            $db->prepare('UPDATE vdr_users SET role = "user" WHERE id = ?')->execute([$targetId]);
            $message = 'Admin role removed.';
        } elseif ($action === 'grant_paid') {
            $db->prepare('UPDATE vdr_users SET paid = 1 WHERE id = ?')->execute([$targetId]);
            $message = 'Paid access granted.';
        } elseif ($action === 'revoke_paid') {
            $db->prepare('UPDATE vdr_users SET paid = 0, payment_id = NULL, paid_at = NULL WHERE id = ?')->execute([$targetId]);
            $message = 'Paid access revoked.';
        } elseif ($action === 'delete_user') {
            $db->prepare('DELETE FROM vdr_users WHERE id = ?')->execute([$targetId]);
            $message = 'User deleted.';
        }
    }
}

// Load all users with progress stats
$users = $db->query(
    'SELECT u.*,
            (SELECT COUNT(*) FROM vdr_user_progress up
             JOIN vdr_items i ON i.id = up.item_id
             WHERE up.user_id = u.id AND up.seen = 1 AND up.done = 1) AS items_completed,
            (SELECT COUNT(*) FROM vdr_items) AS total_items
     FROM vdr_users u
     ORDER BY u.created_at DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Voudou Ropes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-left">
            <a href="checklist.php"><h1>Voudou Ropes</h1></a>
        </div>
        <div class="header-right">
            <span class="username"><?= htmlspecialchars($user['username']) ?> (Admin)</span>
            <a href="logout.php" class="btn btn-small btn-outline">Log Out</a>
        </div>
    </header>

    <div class="admin-container">
        <h2 style="margin: 24px 0 16px;">User Management</h2>
        <p style="color: var(--text-muted); margin-bottom: 20px;">
            <?= count($users) ?> registered user<?= count($users) !== 1 ? 's' : '' ?>
        </p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Paid</th>
                    <th>Progress</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['phone']) ?></td>
                    <td><?= $u['role'] === 'admin' ? '<span style="color:var(--accent)">Admin</span>' : 'User' ?></td>
                    <td><?= $u['paid'] ? '<span style="color:var(--success)">Yes</span>' : 'No' ?></td>
                    <td><?= (int) $u['items_completed'] ?>/<?= (int) $u['total_items'] ?></td>
                    <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['id'] !== $user['id']): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <?php if ($u['role'] !== 'admin'): ?>
                                <button name="action" value="make_admin" class="btn btn-small">Make Admin</button>
                            <?php else: ?>
                                <button name="action" value="remove_admin" class="btn btn-small btn-outline">Remove Admin</button>
                            <?php endif; ?>

                            <?php if (!$u['paid']): ?>
                                <button name="action" value="grant_paid" class="btn btn-small">Grant Paid</button>
                            <?php else: ?>
                                <button name="action" value="revoke_paid" class="btn btn-small btn-outline">Revoke Paid</button>
                            <?php endif; ?>

                            <button name="action" value="delete_user" class="btn btn-small"
                                    style="background:#c0392b"
                                    onclick="return confirm('Delete this user? This cannot be undone.')">Delete</button>
                        </form>
                        <?php else: ?>
                            <em style="color:var(--text-muted)">Current user</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</body>
</html>

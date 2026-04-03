<?php
require_once __DIR__ . '/db.php';

session_start();

function getCurrentUser(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM vdr_users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireLogin(): array {
    $user = getCurrentUser();
    if (!$user) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    return $user;
}

function requireAdmin(): array {
    $user = requireLogin();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
    return $user;
}

function isAdmin(?array $user): bool {
    return $user && $user['role'] === 'admin';
}

function hasAccess(?array $user, int $levelNumber): bool {
    if (!$user) return false;
    if ($levelNumber === 1) return true;
    if (isAdmin($user)) return true;
    return (bool) $user['paid'];
}

function createMagicToken(int $userId): string {
    $db    = getDB();
    $token = bin2hex(random_bytes(32));
    $stmt  = $db->prepare(
        'INSERT INTO vdr_login_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
    );
    $stmt->execute([$userId, $token, MAGIC_LINK_EXPIRY_MINUTES]);
    return $token;
}

function verifyMagicToken(string $token): ?int {
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM vdr_login_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()'
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) return null;

    $stmt = $db->prepare('UPDATE vdr_login_tokens SET used = 1 WHERE id = ?');
    $stmt->execute([$row['id']]);

    return (int) $row['user_id'];
}

function loginUser(int $userId): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/sms.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone    = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');

    // Validate
    if (empty($phone) || empty($username)) {
        $error = 'Both fields are required.';
    } elseif (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $error = 'Enter a valid phone number (digits only, optional + prefix).';
    } elseif (strlen($username) < 2 || strlen($username) > 50) {
        $error = 'Username must be 2-50 characters.';
    } else {
        $db = getDB();

        // Check uniqueness
        $stmt = $db->prepare('SELECT id FROM vdr_users WHERE phone = ?');
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            $error = 'This phone number is already registered. <a href="login.php">Log in instead</a>.';
        } else {
            $stmt = $db->prepare('SELECT id FROM vdr_users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username is taken. Try another.';
            }
        }

        if (empty($error)) {
            $stmt = $db->prepare('INSERT INTO vdr_users (phone, username) VALUES (?, ?)');
            $stmt->execute([$phone, $username]);
            $userId = (int) $db->lastInsertId();

            // Send magic link
            $token = createMagicToken($userId);
            sendMagicLink($phone, $token);

            header('Location: ' . BASE_URL . '/login.php?sent=1&phone=' . urlencode($phone));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Voudou Ropes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="auth-container">
        <h1>Voudou Ropes</h1>
        <h2>Create Your Account</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="phone">Mobile Number</label>
                <input type="tel" id="phone" name="phone" placeholder="+1234567890"
                       value="<?= htmlspecialchars($phone ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="username">Choose a Username</label>
                <input type="text" id="username" name="username" placeholder="Something fun..."
                       value="<?= htmlspecialchars($username ?? '') ?>" required
                       minlength="2" maxlength="50">
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <p class="auth-link">Already have an account? <a href="login.php">Log in</a></p>
    </div>
</body>
</html>

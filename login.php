<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/sms.php';

// Already logged in?
if (getCurrentUser()) {
    header('Location: ' . BASE_URL . '/checklist.php');
    exit;
}

$error = '';
$sent  = isset($_GET['sent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');

    if (empty($phone) || !preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $error = 'Enter a valid phone number.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id FROM vdr_users WHERE phone = ?');
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'No account found. <a href="register.php">Register first</a>.';
        } else {
            $token = createMagicToken($user['id']);
            sendMagicLink($phone, $token);
            $sent = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Voudou Ropes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="auth-container">
        <h1>Voudou Ropes</h1>
        <h2>Log In</h2>

        <?php if ($sent): ?>
            <div class="alert alert-success">
                A login link has been sent to your phone. Check your messages and click the link to sign in.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="phone">Mobile Number</label>
                <input type="tel" id="phone" name="phone" placeholder="+1234567890"
                       value="<?= htmlspecialchars($_GET['phone'] ?? $_POST['phone'] ?? '') ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Send Login Link</button>
        </form>

        <p class="auth-link">Don't have an account? <a href="register.php">Register</a></p>
    </div>
</body>
</html>

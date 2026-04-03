<?php
require_once __DIR__ . '/auth.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$userId = verifyMagicToken($token);

if ($userId === null) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invalid Link - Voudou Ropes</title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
        <div class="auth-container">
            <h1>Voudou Ropes</h1>
            <div class="alert alert-error">
                This login link is invalid or has expired. Please request a new one.
            </div>
            <a href="login.php" class="btn btn-primary">Request New Link</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

loginUser($userId);
header('Location: ' . BASE_URL . '/checklist.php');
exit;

<?php
require_once __DIR__ . '/auth.php';

$user = getCurrentUser();
if ($user) {
    header('Location: ' . BASE_URL . '/checklist.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voudou Ropes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="landing">
        <h1>Voudou Ropes</h1>
        <p class="tagline">Master the art, one knot at a time.</p>
        <div class="landing-actions">
            <a href="register.php" class="btn btn-primary btn-large">Get Started</a>
            <a href="login.php" class="btn btn-outline btn-large">Log In</a>
        </div>
    </div>
</body>
</html>

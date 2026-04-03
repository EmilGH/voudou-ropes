<?php
require_once __DIR__ . '/auth.php';

$user = requireLogin();

// Already paid?
if ($user['paid'] || isAdmin($user)) {
    header('Location: ' . BASE_URL . '/checklist.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unlock All Levels - Voudou Ropes</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="<?= SQUARE_JS_URL ?>"></script>
</head>
<body>
    <header class="site-header">
        <div class="header-left">
            <a href="checklist.php"><h1>Voudou Ropes</h1></a>
        </div>
        <div class="header-right">
            <span class="username"><?= htmlspecialchars($user['username']) ?></span>
        </div>
    </header>

    <div class="pay-container">
        <h2>Unlock All Levels</h2>
        <div class="pay-amount">$0.99</div>
        <p style="text-align:center; color: var(--text-muted); margin-bottom: 24px;">
            One-time payment to access Levels 2 through 9.
        </p>

        <div id="card-container"></div>
        <button id="card-button" class="btn btn-primary" style="width:100%">Pay $0.99</button>
        <div id="payment-status"></div>
    </div>

    <script>
    async function initPayment() {
        const payments = window.Square.payments('<?= SQUARE_ENVIRONMENT === 'sandbox' ? 'sandbox-' : '' ?><?= SQUARE_ACCESS_TOKEN ?>', '<?= SQUARE_LOCATION_ID ?>');
        const card = await payments.card();
        await card.attach('#card-container');

        const button = document.getElementById('card-button');
        const status = document.getElementById('payment-status');

        button.addEventListener('click', async function () {
            button.disabled = true;
            button.textContent = 'Processing...';
            status.innerHTML = '';

            try {
                const result = await card.tokenize();
                if (result.status === 'OK') {
                    // Send token to server
                    const response = await fetch('<?= BASE_URL ?>/pay-confirm.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ source_id: result.token })
                    });
                    const data = await response.json();

                    if (data.success) {
                        status.innerHTML = '<div class="alert alert-success">Payment successful! Redirecting...</div>';
                        setTimeout(() => window.location.href = '<?= BASE_URL ?>/checklist.php', 1500);
                    } else {
                        status.innerHTML = '<div class="alert alert-error">' + (data.error || 'Payment failed.') + '</div>';
                        button.disabled = false;
                        button.textContent = 'Pay $0.99';
                    }
                } else {
                    status.innerHTML = '<div class="alert alert-error">Card validation failed. Please check your details.</div>';
                    button.disabled = false;
                    button.textContent = 'Pay $0.99';
                }
            } catch (e) {
                status.innerHTML = '<div class="alert alert-error">An error occurred. Please try again.</div>';
                button.disabled = false;
                button.textContent = 'Pay $0.99';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initPayment);
    </script>
</body>
</html>

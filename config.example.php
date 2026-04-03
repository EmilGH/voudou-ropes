<?php
// Voudou Ropes - Configuration
// Copy this file to config.php and fill in your credentials.

// Base URL (no trailing slash)
define('BASE_URL', 'https://yourdomain.com/voudou-ropes');

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_pass');

// SMSAlert.co.in
$smsAlertKey    = 'YOUR_SMSALERT_API_KEY';
$smsAlertSender = 'YOUR_SENDER_ID';

// Square Payments
define('SQUARE_ACCESS_TOKEN', 'YOUR_SQUARE_ACCESS_TOKEN');
define('SQUARE_LOCATION_ID', 'YOUR_SQUARE_LOCATION_ID');
define('SQUARE_ENVIRONMENT', 'sandbox'); // 'sandbox' or 'production'

// Square SDK URLs
define('SQUARE_JS_URL', SQUARE_ENVIRONMENT === 'sandbox'
    ? 'https://sandbox.web.squarecdn.com/v1/square.js'
    : 'https://web.squarecdn.com/v1/square.js');

define('SQUARE_API_URL', SQUARE_ENVIRONMENT === 'sandbox'
    ? 'https://connect.squareupsandbox.com'
    : 'https://connect.squareup.com');

// App Settings
define('MAGIC_LINK_EXPIRY_MINUTES', 15);
define('PAYMENT_AMOUNT', 99); // in cents
define('PAYMENT_CURRENCY', 'USD');

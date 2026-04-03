<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function appLog(string $level, string $source, string $message, ?array $context = null): void {
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO vdr_log (level, source, message, context) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$level, $source, $message, $context ? json_encode($context) : null]);
    } catch (\Throwable $e) {
        error_log("appLog failed: " . $e->getMessage());
    }
}

function smsAlert($to, $message) {
    global $smsAlertKey;
    global $smsAlertSender;

    $rawMessage  = $message;
    $message     = urlencode($message);
    $smsAlertURL = "https://www.smsalert.co.in/api/push.json?apikey=$smsAlertKey&sender=$smsAlertSender&mobileno=$to&text=$message";

    appLog('debug', 'sms', 'Sending SMS', ['to' => $to, 'message' => $rawMessage]);

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL            => $smsAlertURL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "POST",
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($error) {
        appLog('error', 'sms', 'cURL error', ['to' => $to, 'error' => $error]);
    } else {
        $decoded = json_decode($response, true);
        $status  = $decoded['status'] ?? 'unknown';
        $logLevel = ($status === 'success') ? 'info' : 'error';
        appLog($logLevel, 'sms', "SMSAlert response (HTTP $httpCode)", [
            'to'       => $to,
            'status'   => $status,
            'response' => $decoded,
        ]);
    }

    return $response;
}

function sendMagicLink($phone, $token) {
    $link    = BASE_URL . '/verify.php?token=' . $token;
    $message = "Your Voudou Ropes login link: $link - This link expires in " . MAGIC_LINK_EXPIRY_MINUTES . " minutes.";
    appLog('info', 'sms', 'Magic link requested', ['phone' => $phone]);
    return smsAlert($phone, $message);
}

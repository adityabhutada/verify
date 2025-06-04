<?php
require 'config.php';
require 'mail.php';

$db = new PDO("sqlite:" . DB_PATH);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Extract values
$status  = $data['status'] ?? 'unknown';
$info    = json_encode($data['info'] ?? []);
$details = json_encode($data['details'] ?? []);
$message = $data['message'] ?? '';
$eventId = $data['id'] ?? '';
$eventTime = isset($data['unixtime']) ? date('c', $data['unixtime']) : null;
$receivedAt = date('c');

// Log to DB
try {
 codex/refactor-pdo-initialization-and-error-handling
    $stmt = $db->prepare(
        "INSERT INTO webhook_logs (event_id, lead_status, message, info, details, received_at) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$eventId, $status, $message, $info, $details, $timestamp]);

    $db = new PDO("sqlite:" . DB_PATH);
    $stmt = $db->prepare("INSERT INTO webhook_logs (event_id, lead_status, message, info, details, event_time, received_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$eventId, $status, $message, $info, $details, $eventTime, $receivedAt]);
 main
} catch (Exception $e) {
    file_put_contents(LOG_PATH, "[ERROR] DB log failed: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Update lead status
try {
    $stmt = $db->prepare("UPDATE verifications SET status = ?, message = ? WHERE plaid_id = ?");
    $stmt->execute([$status, $message, $eventId]);
} catch (Exception $e) {
    file_put_contents(LOG_PATH, "[ERROR] Verification update failed: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Email alert if failed
if (strtolower($status) === 'failed') {
    $subject = "⚠️ Verification Failed - Event {$eventId}";
    $timeForEmail = $eventTime ?? $receivedAt;
    $body = "Verification failed at: {$timeForEmail}<br><br>
             <strong>Message:</strong> {$message}<br>
             <strong>Info:</strong><pre>{$info}</pre><br>
             <strong>Details:</strong><pre>{$details}</pre><br>";
    sendAdminEmail($subject, $body);

    // Send failure notice to user
    try {
        $stmt = $db->prepare("SELECT first_name, email FROM verifications WHERE plaid_id = ? LIMIT 1");
        $stmt->execute([$eventId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $failureLink = rtrim($scheme . $host, '/') . '/failure.php';
            sendFailureEmail($row['email'], $row['first_name'], $failureLink);

            // record in logs table
            $infoJson = json_encode(['email' => $row['email']]);
            $stmtLog = $db->prepare("INSERT INTO webhook_logs (event_id, lead_status, message, info, details, received_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtLog->execute([$eventId, 'notification_sent', 'Failure email sent', $infoJson, '{}', date('c')]);
        }
    } catch (Exception $e) {
        file_put_contents(LOG_PATH, "[ERROR] Failure notice error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

http_response_code(200);
echo json_encode(['received' => true]);
?>

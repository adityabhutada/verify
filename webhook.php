<?php
require 'config.php';
require 'mail.php';

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
    $db = new PDO("sqlite:" . DB_PATH);
    $stmt = $db->prepare("INSERT INTO webhook_logs (event_id, lead_status, message, info, details, event_time, received_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$eventId, $status, $message, $info, $details, $eventTime, $receivedAt]);
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
}

http_response_code(200);
echo json_encode(['received' => true]);
?>

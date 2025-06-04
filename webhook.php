<?php
require 'config.php';
require 'mail.php';

function handleWebhook(string $input): array
{
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE || $data === null) {
        file_put_contents(LOG_PATH, "[ERROR] Invalid JSON payload\n", FILE_APPEND);
        return [400, ['error' => 'Invalid JSON']];
    }

    // Extract values
    $status  = $data['status'] ?? 'unknown';
    $info    = json_encode($data['info'] ?? []);
    $details = json_encode($data['details'] ?? []);
    $message = $data['message'] ?? '';
    $eventId = $data['id'] ?? '';
    $timestamp = date('c');

    // Log to DB
    try {
        $db = new PDO("sqlite:" . DB_PATH);
        $stmt = $db->prepare("INSERT INTO webhook_logs (event_id, lead_status, message, info, details, received_at)
                          VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$eventId, $status, $message, $info, $details, $timestamp]);
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
        $body = "Verification failed at: {$timestamp}<br><br>
                 <strong>Message:</strong> {$message}<br>
                 <strong>Info:</strong><pre>{$info}</pre><br>
                 <strong>Details:</strong><pre>{$details}</pre><br>";
        sendAdminEmail($subject, $body);
    }

    return [200, ['received' => true]];
}

if (defined('TESTING') && TESTING) {
    return;
}

$input = file_get_contents('php://input');
[$statusCode, $response] = handleWebhook($input);

http_response_code($statusCode);
echo json_encode($response);
?>

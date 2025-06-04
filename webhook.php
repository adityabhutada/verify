<?php
require 'config.php';
require 'mail.php';

function handleWebhook(string $input): array
{
    // Validate JSON payload
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE || $data === null) {
        file_put_contents(LOG_PATH, "[ERROR] Invalid JSON payload\n", FILE_APPEND);
        return [400, ['error' => 'Invalid JSON']];
    }

    // Connect to database
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        file_put_contents(LOG_PATH, "[ERROR] DB connection failed: " . $e->getMessage() . "\n", FILE_APPEND);
        return [500, ['error' => 'Database error']];
    }

    // Extract values
    $status     = $data['status'] ?? 'unknown';
    $info       = json_encode($data['info'] ?? []);
    $details    = json_encode($data['details'] ?? []);
    $message    = $data['message'] ?? '';
    $eventId    = $data['id'] ?? '';
    $eventTime  = isset($data['unixtime']) ? date('c', $data['unixtime']) : null;
    $receivedAt = date('c');

    // Log webhook payload
    try {
        $stmt = $db->prepare(
            'INSERT INTO webhook_logs (event_id, lead_status, message, info, details, event_time, received_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$eventId, $status, $message, $info, $details, $eventTime, $receivedAt]);
    } catch (Exception $e) {
        file_put_contents(LOG_PATH, "[ERROR] DB log failed: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    // Update verification status
    try {
        $stmt = $db->prepare('UPDATE verifications SET status = ?, message = ? WHERE plaid_id = ?');
        $stmt->execute([$status, $message, $eventId]);
    } catch (Exception $e) {
        file_put_contents(LOG_PATH, "[ERROR] Verification update failed: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    // Send notifications for failed status
    if (strtolower($status) === 'failed') {
        $timeForEmail = $eventTime ?? $receivedAt;
        $subject = "⚠️ Verification Failed - Event {$eventId}";
        $body = "Verification failed at: {$timeForEmail}<br><br>"
              . "<strong>Message:</strong> {$message}<br>"
              . "<strong>Info:</strong><pre>{$info}</pre><br>"
              . "<strong>Details:</strong><pre>{$details}</pre><br>";
        sendAdminEmail($subject, $body);

        try {
            $stmt = $db->prepare('SELECT first_name, email FROM verifications WHERE plaid_id = ? LIMIT 1');
            $stmt->execute([$eventId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $failureLink = rtrim($scheme . $host, '/') . '/failure.php';
                sendFailureEmail($row['email'], $row['first_name'], $failureLink);

                // record notification in logs
                $infoJson = json_encode(['email' => $row['email']]);
                $logStmt = $db->prepare(
                    'INSERT INTO webhook_logs (event_id, lead_status, message, info, details, received_at)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $logStmt->execute([$eventId, 'notification_sent', 'Failure email sent', $infoJson, '{}', date('c')]);
            }
        } catch (Exception $e) {
            file_put_contents(LOG_PATH, "[ERROR] Failure notice error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }

    return [200, ['received' => true]];
}

if (!defined('TESTING') || !TESTING) {
    $input = file_get_contents('php://input');
    [$status, $response] = handleWebhook($input);
    http_response_code($status);
    echo json_encode($response);
}

<?php
require 'config.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Write a message to submit_error.log while removing sensitive
 * Authorization headers or bearer tokens.
 */
function log_submit_error(string $message): void
{
    $patterns = [
        '/Authorization:\s*Bearer\s+[^\s]+/i',
        '/Authorization:\s*[^\s]+/i',
    ];
    $sanitized = preg_replace($patterns, 'Authorization: [REDACTED]', $message);
    file_put_contents('submit_error.log', $sanitized . PHP_EOL, FILE_APPEND);
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Validate form input
function validate($data) {
    foreach ($data as $key => $value) {
        if ($key !== 'terms' && empty($value)) {
            return false;
        }
    }

    if (!isset($data['terms'])) {
        return false;
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if (!preg_match('/^\+1\d{10}$/', $data['phone'])) {
        return false;
    }

    return true;
}

// When running tests we only need the validate() function
if (defined('TESTING') && TESTING) {
    return;
}

if (empty($_POST['token']) || empty($_SESSION['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
    http_response_code(403);
    exit('Invalid token');
}

$data = array_map('trim', $_POST);
if (!validate($data)) {
    header("Location: index.php?error=1");
    exit;
}

// ✅ Format DOB before saving
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['dob'])) {
    log_submit_error("[DOB ERROR] Invalid format: {$data['dob']}");
    header("Location: index.php?error=1");
    exit;
}
$data['DOB'] = $data['dob'];

// ✅ Normalize phone before saving
$rawPhone = preg_replace('/\D/', '', $data['phone']);
if (strlen($rawPhone) === 10) {
    $data['phone'] = '+1' . $rawPhone;
} elseif (preg_match('/^\+1\d{10}$/', $data['phone'])) {
    // already correct
} else {
    log_submit_error("[PHONE ERROR] Invalid phone: {$data['phone']}");
    header("Location: index.php?error=1");
    exit;
}

// Save to database
try {
    $db = new PDO("sqlite:" . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare("INSERT INTO verifications 
        (first_name, last_name, email, phone, street, city, state, zip, dob)
        VALUES (:first_name, :last_name, :email, :phone, :street, :city, :state, :zip, :dob)");
    $stmt->execute([
        ':first_name' => $data['first_name'],
        ':last_name'  => $data['last_name'],
        ':email'      => $data['email'],
        ':phone'      => $data['phone'],
        ':street'     => $data['street'],
        ':city'       => $data['city'],
        ':state'      => $data['state'],
        ':zip'        => $data['zip'],
        ':dob'        => $data['dob'],
    ]);
    $id = $db->lastInsertId();
} catch (Exception $e) {
    log_submit_error("[DB ERROR] " . $e->getMessage());
    die("Database error.");
}

unset($data['dob']);


// Prepare API request
$headers = "Content-type: application/x-www-form-urlencoded\r\n" .
           "Authorization: Bearer " . API_KEY;
$options = [
    'http' => [
        'header'  => $headers,
        'method'  => 'POST',
        'content' => http_build_query($data)
    ]
];
$context = stream_context_create($options);
$result = file_get_contents('https://idcheck.expressmarketinginc.com/intake/', false, $context);
if ($result === false) {
    file_put_contents("submit_error.log", "[FETCH ERROR] " . json_encode(error_get_last()) . "\n", FILE_APPEND);
    header("Location: index.php?error=2");
    exit;
}
$response = json_decode($result, true);

// Handle API response
if (!$response['error'] && isset($response['result'])) {
    $verifyLink = $response['result'];
$parsed = parse_url($verifyLink);
parse_str($parsed['query'], $query);
$plaidId = basename($parsed['path']); // e.g., idv_12345...

$db->prepare("UPDATE verifications SET result_url = ?, plaid_id = ?, status = 'Link Sent' WHERE id = ?")
   ->execute([$verifyLink, $plaidId, $id]);

    // 📧 Send verification email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SENDER_EMAIL, 'Find The Firm');
        $mail->addAddress($data['email'], $data['first_name'] . ' ' . $data['last_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Your ID Verification Link';

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; padding: 20px; background-color: #f9f9f9; border: 1px solid #e1e1e1; border-radius: 8px;'>
                <h2 style='color: #0056b3;'>Hi {$data['first_name']},</h2>
                <p>Thank you for your submission. Please click the button below to verify your identity:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$verifyLink}' style='background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>Verify Now</a>
                </p>
                <p>If the button above doesn't work, you can also copy and paste the following link into your browser:</p>
                <p><a href='{$verifyLink}'>{$verifyLink}</a></p>
                <hr>
                <p style='font-size: 13px; color: #888;'>If you did not request this verification, you can safely ignore this message.</p>
            </div>
        ";
        $mail->AltBody = "Hi {$data['first_name']},\n\nPlease verify your identity using this link:\n{$verifyLink}\n\nIf you did not request this, please ignore.";

        $mail->send();
        log_submit_error("[MAIL SUCCESS] Sent to {$data['email']} at " . date('Y-m-d H:i:s'));
    } catch (Exception $e) {
        log_submit_error("[MAIL ERROR] " . $mail->ErrorInfo);
    }

    header("Location: thankyou.php");
    exit;
} else {
    log_submit_error("[PLAID ERROR] " . json_encode($response));
    header("Location: index.php?error=2");
    exit;
}

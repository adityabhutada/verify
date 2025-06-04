<?php
require 'config.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Validate form input
function validate($data) {
    foreach ($data as $key => $value) {
        if (empty($value) && $key !== 'terms') return false;
    }
    return isset($data['terms']);
}

// Send the verification data to the Plaid API
function send_api_request(array $data, string $url = 'https://idcheck.expressmarketinginc.com/intake/') {
    $headers = "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Bearer " . API_KEY;
    $options = [
        'http' => [
            'header'  => $headers,
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

// When running tests we only need the validate() function
if (defined('TESTING') && TESTING) {
    return;
}

$data = array_map('trim', $_POST);
if (!validate($data)) {
    header("Location: index.php?error=1");
    exit;
}

// ✅ Format DOB before saving
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['dob'])) {
    file_put_contents("submit_error.log", "[DOB ERROR] Invalid format: {$data['dob']}\n", FILE_APPEND);
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
    file_put_contents("submit_error.log", "[PHONE ERROR] Invalid phone: {$data['phone']}\n", FILE_APPEND);
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
    file_put_contents("submit_error.log", "[DB ERROR] " . $e->getMessage() . "\n", FILE_APPEND);
    die("Database error.");
}

unset($data['dob']);


// Prepare API request
$result = send_api_request($data);
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
        file_put_contents("submit_error.log", "[MAIL SUCCESS] Sent to {$data['email']} at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents("submit_error.log", "[MAIL ERROR] " . $mail->ErrorInfo . "\n", FILE_APPEND);
    }

    header("Location: thankyou.php");
    exit;
} else {
    file_put_contents("submit_error.log", "[PLAID ERROR] " . json_encode($response) . "\n", FILE_APPEND);
    header("Location: index.php?error=2");
    exit;
}

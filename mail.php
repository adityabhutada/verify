<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendAdminEmail($subject, $bodyHtml) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SENDER_EMAIL, 'Find The Firm');
        $mail->addAddress(ADMIN_EMAIL);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;
        $mail->send();
    } catch (Exception $e) {
        file_put_contents(LOG_PATH, "[EMAIL ERROR] " . $mail->ErrorInfo . "\n", FILE_APPEND);
    }
}

function sendFailureEmail(string $toEmail, string $toName, string $failureLink): void
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SENDER_EMAIL, 'Find The Firm');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = 'ID Verification Failed';
        $mail->Body = "<p>Hi {$toName},</p>"
            . '<p>Unfortunately, we were unable to verify your identity.</p>'
            . "<p>Please visit <a href='{$failureLink}'>this page</a> for next steps.</p>";
        $mail->AltBody = "We were unable to verify your identity. Visit {$failureLink} for next steps.";
        $mail->send();
    } catch (Exception $e) {
        file_put_contents(LOG_PATH, "[EMAIL ERROR] " . $mail->ErrorInfo . "\n", FILE_APPEND);
    }
}
?>

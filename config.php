<?php
define('API_KEY', getenv('API_KEY'));

define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_PORT', getenv('SMTP_PORT'));
define('SMTP_USER', getenv('SMTP_USER'));
define('SMTP_PASS', getenv('SMTP_PASS'));
define('SENDER_EMAIL', getenv('SENDER_EMAIL'));

define('ADMIN_USER', getenv('ADMIN_USER'));
define('ADMIN_PASS', getenv('ADMIN_PASS'));
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL'));

define('DB_PATH', getenv('DB_PATH') ?: __DIR__ . '/db.sqlite');
define('LOG_PATH', getenv('LOG_PATH') ?: __DIR__ . '/logs/webhook.log');

session_start();
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}
?>

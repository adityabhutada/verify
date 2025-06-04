<?php
require '../config.php';

if (!isset($_POST['username'], $_POST['password'])) {
    header('Location: login.php');
    exit;
}

if ($_POST['username'] === ADMIN_USER && $_POST['password'] === ADMIN_PASS) {
    $_SESSION['admin'] = true;
    header("Location: dashboard.php");
} else {
    echo "Invalid credentials.";
}
?>
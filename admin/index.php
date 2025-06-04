<?php
require '../config.php';
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
} else {
    header("Location: dashboard.php");
    exit;
}
?>
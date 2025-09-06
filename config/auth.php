<?php
session_start();

define('BASE_URL', dirname($_SERVER['PHP_SELF']));
define('LOGIN_URL', 'login.php');

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    error_log("Unauthorized access attempt from IP: " . $_SERVER['REMOTE_ADDR']);

    session_unset();
    session_destroy();

    header("Location: " . BASE_URL . "/login.php?error=unauthorized");
    exit;
}


if (
    isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > 1800
) {
    error_log("Session timeout for user: " . $_SERVER['REMOTE_ADDR']);
    session_unset();
    session_destroy();
    header('Location: ' . LOGIN_URL . '?error=session_timeout');
    exit;
}

$_SESSION['last_activity'] = time();

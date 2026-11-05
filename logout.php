<?php
/**
 * Clean Session Termination & Logout
 * Hotel Event Management System
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start a fresh session to carry the flash message
session_start();
set_flash('info', 'You have been signed out safely. Thank you for visiting.');

header('Location: ' . BASE_URL . 'login.php');
exit;

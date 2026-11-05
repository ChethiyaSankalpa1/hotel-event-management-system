<?php
/**
 * Session Security, Authentication Guards & CSRF Middleware
 * Hotel Event Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie parameters before starting
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path'     => '/',
        'domain'   => $cookieParams['domain'],
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/constants.php';

/**
 * Check if a user session is currently active
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Return current logged in user array or null
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'email'     => $_SESSION['user_email'] ?? '',
        'role'      => $_SESSION['user_role'] ?? '',
        'name'      => $_SESSION['user_name'] ?? 'User',
        'entity_id' => $_SESSION['entity_id'] ?? null, // Customer ID or Staff ID
    ];
}

/**
 * Return current user role or null
 */
function current_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Enforce that the user must be logged in. If not, redirect to login page.
 */
function require_login(string $redirectAfter = ''): void {
    if (!is_logged_in()) {
        if (!empty($redirectAfter)) {
            $_SESSION['redirect_after_login'] = $redirectAfter;
        } else {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }
        set_flash('warning', 'Please sign in to access that page.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Role-Based Access Control (RBAC) Guard
 * @param array|string $allowedRoles Single role string or array of allowed roles
 */
function require_role($allowedRoles): void {
    require_login();
    
    $allowed = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $userRole = current_user_role();
    
    if (!in_array($userRole, $allowed, true)) {
        set_flash('danger', 'Access denied. You do not have permission to view that page.');
        
        // Redirect to their respective authorized home
        if ($userRole === ROLE_ADMIN) {
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
        } elseif ($userRole === ROLE_STAFF) {
            header('Location: ' . BASE_URL . 'staff/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'customer/dashboard.php');
        }
        exit;
    }
}

/**
 * CSRF Token Management
 */
function generate_csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

function csrf_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION[CSRF_TOKEN_KEY])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_KEY], $token);
}

/**
 * Flash Notification Messages
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function get_flash(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

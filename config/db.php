<?php
/**
 * Database Configuration
 * Pharmacy Management System
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'pharmacy_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Low stock threshold
define('LOW_STOCK_THRESHOLD', 50);

// Expiry warning days
define('EXPIRY_WARNING_DAYS', 30);

// Application name
define('APP_NAME', 'Pharmacy Management System');

/**
 * Get PDO database connection
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES    => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed. Please check your configuration.<br>Error: " . $e->getMessage());
        }
    }
    return $pdo;
}

/**
 * Start session safely
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    startSession();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /pharmacy-system/auth/login.php');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = 'Access denied. Admin privileges required.';
        header('Location: /pharmacy-system/admin/dashboard.php');
        exit;
    }
}

/**
 * Sanitize output
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    startSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get count of expired medicines
 */
function getExpiredCount() {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) FROM medicines WHERE expiry_date < CURDATE() AND quantity > 0");
    return $stmt->fetchColumn();
}

/**
 * Get count of expiring soon medicines
 */
function getExpiringSoonCount() {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY) AND quantity > 0");
    $stmt->execute(['days' => EXPIRY_WARNING_DAYS]);
    return $stmt->fetchColumn();
}

/**
 * Get count of low stock medicines
 */
function getLowStockCount() {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM medicines WHERE quantity > 0 AND quantity <= :threshold");
    $stmt->execute(['threshold' => LOW_STOCK_THRESHOLD]);
    return $stmt->fetchColumn();
}

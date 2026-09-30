<?php
// ============================================
// EXPENSE MANAGER - Database Configuration
// ============================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'expense_manager');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('APP_NAME', 'SpendSmart');
define('APP_VERSION', '1.0.0');
define('CURRENCY', '₹');

// Create Database Connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
        <h2 style="color:#ef4444;">⚠️ Database Connection Failed</h2>
        <p style="color:#64748b;">Please check your MySQL server and database credentials in <code>includes/config.php</code></p>
        <p style="color:#ef4444;font-size:12px;">' . $conn->connect_error . '</p>
        <p><a href="database_fixed.sql" style="color:#6366f1;">Download Database SQL File</a> and import it in phpMyAdmin.</p>
    </div>');
}

$conn->set_charset("utf8mb4");

// Session Start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper: Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
}

// Helper: Format currency
function formatCurrency($amount, $currency = '₹') {
    return $currency . number_format($amount, 2);
}

// Helper: Get user data
function getUser($conn) {
    if (!isLoggedIn()) return null;
    $id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Helper: Time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hr ago';
    return floor($diff/86400) . ' days ago';
}
?>

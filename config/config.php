<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'greenleaf_db');

// Application configuration
define('SITE_URL', 'http://localhost/greenleaf-app');
define('SITE_NAME', 'Greenleaf');
define('UPLOAD_PATH', 'uploads/');
define('DEFAULT_PLANT_IMAGE', 'assets/images/default-plant.jpg');
define('DEFAULT_AVATAR', 'assets/images/default-avatar.jpg');

// Include database class
require_once 'database.php';

// Helper functions
function redirect($url) {
    header("Location: " . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function format_price($price) {
    return '$' . number_format($price, 2);
}

function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}
?>

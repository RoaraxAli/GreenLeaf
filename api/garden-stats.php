<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get active reminders count
$active_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM reminders 
    WHERE user_id = ? AND is_completed = 0
");
$active_stmt->bind_param("i", $user_id);
$active_stmt->execute();
$active_reminders = $active_stmt->get_result()->fetch_assoc()['count'];

// Get today's tasks count
$today_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM reminders 
    WHERE user_id = ? AND reminder_date = CURDATE() AND is_completed = 0
");
$today_stmt->bind_param("i", $user_id);
$today_stmt->execute();
$today_tasks = $today_stmt->get_result()->fetch_assoc()['count'];

echo json_encode([
    'success' => true,
    'active_reminders' => $active_reminders,
    'today_tasks' => $today_tasks
]);

$db->close();
?>

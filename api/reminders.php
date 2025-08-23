<?php
require_once '../config/config.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $garden_id = intval($_POST['garden_id']);
            $type = sanitize_input($_POST['type']);
            $reminder_date = $_POST['reminder_date'];
            $reminder_time = $_POST['reminder_time'];
            $message = sanitize_input($_POST['message']);
            
            if (!$garden_id || !$type || !$reminder_date || !$reminder_time) {
                redirect('../reminders.php?error=missing_fields');
            }
            
            // Verify garden belongs to user
            $verify_stmt = $conn->prepare("SELECT garden_id FROM user_garden WHERE garden_id = ? AND user_id = ?");
            $verify_stmt->bind_param("ii", $garden_id, $user_id);
            $verify_stmt->execute();
            
            if ($verify_stmt->get_result()->num_rows === 0) {
                redirect('../reminders.php?error=invalid_garden');
            }
            
            // Add reminder
            $insert_stmt = $conn->prepare("
                INSERT INTO reminders (user_id, plant_id, garden_id, reminder_date, reminder_time, type, message) 
                SELECT ?, ug.plant_id, ?, ?, ?, ?, ?
                FROM user_garden ug 
                WHERE ug.garden_id = ? AND ug.user_id = ?
            ");
            $insert_stmt->bind_param("iissssii", $user_id, $garden_id, $reminder_date, $reminder_time, $type, $message, $garden_id, $user_id);
            
            if ($insert_stmt->execute()) {
                redirect('../reminders.php?success=added');
            } else {
                redirect('../reminders.php?error=add_failed');
            }
            break;
            
        case 'complete':
            $reminder_id = intval($_POST['reminder_id']);
            
            if (!$reminder_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid reminder']);
                exit;
            }
            
            $update_stmt = $conn->prepare("
                UPDATE reminders 
                SET is_completed = 1 
                WHERE reminder_id = ? AND user_id = ?
            ");
            $update_stmt->bind_param("ii", $reminder_id, $user_id);
            
            if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Reminder completed']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Reminder not found or already completed']);
            }
            break;
            
        case 'delete':
            $reminder_id = intval($_POST['reminder_id']);
            
            if (!$reminder_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid reminder']);
                exit;
            }
            
            $delete_stmt = $conn->prepare("DELETE FROM reminders WHERE reminder_id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $reminder_id, $user_id);
            
            if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Reminder deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Reminder not found']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$db->close();
?>

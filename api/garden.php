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
            $plant_id = intval($_POST['plant_id']);
            $plant_name = sanitize_input($_POST['plant_name']);
            $notes = sanitize_input($_POST['notes']);
            
            if (!$plant_id) {
                redirect('../my-garden.php?error=invalid_plant');
            }
            
            // Check if plant exists and user has purchased it
            $check_stmt = $conn->prepare("
                SELECT p.name FROM plants p
                JOIN order_items oi ON p.plant_id = oi.plant_id
                JOIN orders o ON oi.order_id = o.order_id
                WHERE p.plant_id = ? AND o.user_id = ? AND o.status IN ('confirmed', 'shipped', 'delivered')
                LIMIT 1
            ");
            $check_stmt->bind_param("ii", $plant_id, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                redirect('../my-garden.php?error=not_purchased');
            }
            
            // Check if already in garden
            $existing_stmt = $conn->prepare("SELECT garden_id FROM user_garden WHERE user_id = ? AND plant_id = ?");
            $existing_stmt->bind_param("ii", $user_id, $plant_id);
            $existing_stmt->execute();
            
            if ($existing_stmt->get_result()->num_rows > 0) {
                redirect('../my-garden.php?error=already_added');
            }
            
            // Add to garden
            $insert_stmt = $conn->prepare("
                INSERT INTO user_garden (user_id, plant_id, plant_name, notes) 
                VALUES (?, ?, ?, ?)
            ");
            $insert_stmt->bind_param("iiss", $user_id, $plant_id, $plant_name, $notes);
            
            if ($insert_stmt->execute()) {
                redirect('../my-garden.php?success=added');
            } else {
                redirect('../my-garden.php?error=add_failed');
            }
            break;
            
        case 'remove':
            $garden_id = intval($_POST['garden_id']);
            
            if (!$garden_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid garden item']);
                exit;
            }
            
            // Remove from garden and associated reminders
            $conn->begin_transaction();
            
            try {
                // Remove reminders
                $remove_reminders = $conn->prepare("DELETE FROM reminders WHERE garden_id = ? AND user_id = ?");
                $remove_reminders->bind_param("ii", $garden_id, $user_id);
                $remove_reminders->execute();
                
                // Remove from garden
                $remove_plant = $conn->prepare("DELETE FROM user_garden WHERE garden_id = ? AND user_id = ?");
                $remove_plant->bind_param("ii", $garden_id, $user_id);
                $remove_plant->execute();
                
                if ($remove_plant->affected_rows > 0) {
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Plant removed from garden']);
                } else {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Plant not found in your garden']);
                }
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Error removing plant']);
            }
            break;
            
        case 'update':
            $garden_id = intval($_POST['garden_id']);
            $plant_name = sanitize_input($_POST['plant_name']);
            $notes = sanitize_input($_POST['notes']);
            
            if (!$garden_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid garden item']);
                exit;
            }
            
            $update_stmt = $conn->prepare("
                UPDATE user_garden 
                SET plant_name = ?, notes = ? 
                WHERE garden_id = ? AND user_id = ?
            ");
            $update_stmt->bind_param("ssii", $plant_name, $notes, $garden_id, $user_id);
            
            if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Plant updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes made or plant not found']);
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

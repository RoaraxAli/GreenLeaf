<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!is_logged_in() || !is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete':
            $plant_id = intval($_POST['plant_id']);
            
            if (!$plant_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid plant ID']);
                exit;
            }
            
            // Check if plant has orders
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE plant_id = ?");
            $check_stmt->bind_param("i", $plant_id);
            $check_stmt->execute();
            $has_orders = $check_stmt->get_result()->fetch_assoc()['count'] > 0;
            
            if ($has_orders) {
                // Don't delete, just deactivate
                $update_stmt = $conn->prepare("UPDATE plants SET is_active = 0 WHERE plant_id = ?");
                $update_stmt->bind_param("i", $plant_id);
                
                if ($update_stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Plant deactivated (has existing orders)']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deactivating plant']);
                }
            } else {
                // Safe to delete
                $delete_stmt = $conn->prepare("DELETE FROM plants WHERE plant_id = ?");
                $delete_stmt->bind_param("i", $plant_id);
                
                if ($delete_stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Plant deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deleting plant']);
                }
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

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
        case 'approve':
            $review_id = intval($_POST['review_id']);
            
            if (!$review_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid review ID']);
                exit;
            }
            
            $stmt = $conn->prepare("UPDATE reviews SET is_approved = 1 WHERE review_id = ?");
            $stmt->bind_param("i", $review_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Review approved']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Review not found or already approved']);
            }
            break;
            
        case 'reject':
            $review_id = intval($_POST['review_id']);
            
            if (!$review_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid review ID']);
                exit;
            }
            
            $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
            $stmt->bind_param("i", $review_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Review rejected and deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Review not found']);
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

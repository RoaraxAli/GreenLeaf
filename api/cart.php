<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to manage your cart']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $plant_id = intval($input['plant_id']);
            $quantity = intval($input['quantity'] ?? 1);
            
            if (!$plant_id || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }
            
            // Check if plant exists and is in stock
            $plant_stmt = $conn->prepare("SELECT stock_quantity, name FROM plants WHERE plant_id = ? AND is_active = 1");
            $plant_stmt->bind_param("i", $plant_id);
            $plant_stmt->execute();
            $plant_result = $plant_stmt->get_result();
            
            if ($plant_result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Plant not found']);
                exit;
            }
            
            $plant = $plant_result->fetch_assoc();
            if ($plant['stock_quantity'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
                exit;
            }
            
            // Check if item already in cart
            $check_stmt = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND plant_id = ?");
            $check_stmt->bind_param("ii", $user_id, $plant_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing cart item
                $existing = $check_result->fetch_assoc();
                $new_quantity = $existing['quantity'] + $quantity;
                
                if ($new_quantity > $plant['stock_quantity']) {
                    echo json_encode(['success' => false, 'message' => 'Cannot add more items than available stock']);
                    exit;
                }
                
                $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
                $update_stmt->bind_param("ii", $new_quantity, $existing['cart_id']);
                $update_stmt->execute();
            } else {
                // Add new cart item
                $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, plant_id, quantity) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iii", $user_id, $plant_id, $quantity);
                $insert_stmt->execute();
            }
            
            // Get updated cart count
            $count_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
            $count_stmt->bind_param("i", $user_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $cart_count = $count_result->fetch_assoc()['total'] ?? 0;
            
            echo json_encode([
                'success' => true, 
                'message' => $plant['name'] . ' added to cart',
                'cart_count' => $cart_count
            ]);
            break;
            
        case 'update':
            $cart_id = intval($input['cart_id']);
            $quantity = intval($input['quantity']);
            
            if (!$cart_id || $quantity < 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }
            
            if ($quantity === 0) {
                // Remove item
                $delete_stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
                $delete_stmt->bind_param("ii", $cart_id, $user_id);
                $delete_stmt->execute();
            } else {
                // Check stock availability
                $stock_stmt = $conn->prepare("
                    SELECT p.stock_quantity, p.name 
                    FROM cart c 
                    JOIN plants p ON c.plant_id = p.plant_id 
                    WHERE c.cart_id = ? AND c.user_id = ?
                ");
                $stock_stmt->bind_param("ii", $cart_id, $user_id);
                $stock_stmt->execute();
                $stock_result = $stock_stmt->get_result();
                
                if ($stock_result->num_rows === 0) {
                    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                    exit;
                }
                
                $stock_data = $stock_result->fetch_assoc();
                if ($quantity > $stock_data['stock_quantity']) {
                    echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
                    exit;
                }
                
                // Update quantity
                $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
                $update_stmt->bind_param("iii", $quantity, $cart_id, $user_id);
                $update_stmt->execute();
            }
            
            echo json_encode(['success' => true, 'message' => 'Cart updated']);
            break;
            
        case 'remove':
            $cart_id = intval($input['cart_id']);
            
            if (!$cart_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
                exit;
            }
            
            $delete_stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $cart_id, $user_id);
            $delete_stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            break;
            
        case 'clear':
            $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Cart cleared']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$db->close();
?>

<?php
require_once 'config/config.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    redirect('auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get cart items
$stmt = $conn->prepare("
    SELECT c.cart_id, c.quantity,
           p.plant_id, p.name, p.price, p.stock_quantity
    FROM cart c
    JOIN plants p ON c.plant_id = p.plant_id
    WHERE c.user_id = ? AND p.is_active = 1
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$cart_result = $stmt->get_result();

$cart_items = [];
$total_amount = 0;
$total_items = 0;

while ($item = $cart_result->fetch_assoc()) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $total_amount += $item['subtotal'];
    $total_items += $item['quantity'];
    $cart_items[] = $item;
}

if (empty($cart_items)) {
    redirect('cart.php');
}

$shipping_cost = $total_amount >= 50 ? 0 : 9.99;
$final_total = $total_amount + $shipping_cost;

// Get user details
$user_stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = sanitize_input($_POST['shipping_address']);
    $notes = sanitize_input($_POST['notes']);
    
    if (empty($shipping_address)) {
        $error = 'Please provide a shipping address.';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create order
            $order_stmt = $conn->prepare("
                INSERT INTO orders (user_id, total_amount, shipping_address, notes) 
                VALUES (?, ?, ?, ?)
            ");
            $order_stmt->bind_param("idss", $_SESSION['user_id'], $final_total, $shipping_address, $notes);
            $order_stmt->execute();
            $order_id = $conn->insert_id;
            
            // Add order items and update stock
            foreach ($cart_items as $item) {
                // Check stock availability again
                $stock_check = $conn->prepare("SELECT stock_quantity FROM plants WHERE plant_id = ?");
                $stock_check->bind_param("i", $item['plant_id']);
                $stock_check->execute();
                $current_stock = $stock_check->get_result()->fetch_assoc()['stock_quantity'];
                
                if ($current_stock < $item['quantity']) {
                    throw new Exception("Insufficient stock for " . $item['name']);
                }
                
                // Insert order item
                $item_stmt = $conn->prepare("
                    INSERT INTO order_items (order_id, plant_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ");
                $item_stmt->bind_param("iiid", $order_id, $item['plant_id'], $item['quantity'], $item['price']);
                $item_stmt->execute();
                
                // Update stock
                $update_stock = $conn->prepare("
                    UPDATE plants 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE plant_id = ?
                ");
                $update_stock->bind_param("ii", $item['quantity'], $item['plant_id']);
                $update_stock->execute();
            }
            
            // Clear cart
            $clear_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_cart->bind_param("i", $_SESSION['user_id']);
            $clear_cart->execute();
            
            $conn->commit();
            
            // Redirect to order confirmation
            redirect("order-confirmation.php?order_id=$order_id");
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-5 fw-bold text-success mb-2">Checkout</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                        <li class="breadcrumb-item active">Checkout</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Checkout Form -->
            <div class="col-lg-8">
                <form method="POST" action="">
                    <!-- Shipping Information -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Shipping Address *</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" 
                                          rows="3" required placeholder="Enter your complete shipping address..."><?php echo htmlspecialchars($_POST['shipping_address'] ?? $user['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Order Notes (Optional)</label>
                                <textarea class="form-control" id="notes" name="notes" 
                                          rows="2" placeholder="Any special instructions for your order..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Demo Mode:</strong> This is a demonstration checkout. No actual payment will be processed.
                                In a real application, you would integrate with payment processors like Stripe, PayPal, etc.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="card_number" class="form-label">Card Number</label>
                                    <input type="text" class="form-control" id="card_number" 
                                           placeholder="1234 5678 9012 3456" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="card_name" class="form-label">Name on Card</label>
                                    <input type="text" class="form-control" id="card_name" 
                                           placeholder="John Doe" disabled>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="expiry_month" class="form-label">Expiry Month</label>
                                    <select class="form-select" id="expiry_month" disabled>
                                        <option>01</option>
                                        <option>02</option>
                                        <!-- ... more months ... -->
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="expiry_year" class="form-label">Expiry Year</label>
                                    <select class="form-select" id="expiry_year" disabled>
                                        <option>2024</option>
                                        <option>2025</option>
                                        <!-- ... more years ... -->
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cvv" placeholder="123" disabled>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="cart.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Cart
                        </a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-check me-2"></i>Place Order
                        </button>
                    </div>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <!-- Order Items -->
                        <div class="mb-3">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 small"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">Qty: <?php echo $item['quantity']; ?> × <?php echo format_price($item['price']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold"><?php echo format_price($item['subtotal']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr>

                        <!-- Totals -->
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span><?php echo format_price($total_amount); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>
                                <?php if ($shipping_cost > 0): ?>
                                    <?php echo format_price($shipping_cost); ?>
                                <?php else: ?>
                                    <span class="text-success">FREE</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-success"><?php echo format_price($final_total); ?></strong>
                        </div>

                        <!-- Security Info -->
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Your order is secured with SSL encryption
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

<?php $db->close(); ?>

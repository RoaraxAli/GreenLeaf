<?php
require_once 'config/config.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'cart.php';
    redirect('auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get cart items
$stmt = $conn->prepare("
    SELECT c.cart_id, c.quantity, c.added_at,
           p.plant_id, p.name, p.botanical_name, p.price, p.image, p.stock_quantity
    FROM cart c
    JOIN plants p ON c.plant_id = p.plant_id
    WHERE c.user_id = ? AND p.is_active = 1
    ORDER BY c.added_at DESC
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

$shipping_cost = $total_amount >= 50 ? 0 : 9.99;
$final_total = $total_amount + $shipping_cost;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>
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
                <h1 class="display-5 fw-bold text-success mb-2">Shopping Cart</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="catalog.php">Plants</a></li>
                        <li class="breadcrumb-item active">Cart</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (empty($cart_items)): ?>
            <!-- Empty Cart -->
            <div class="row">
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                        <h3 class="text-muted mb-3">Your cart is empty</h3>
                        <p class="text-muted mb-4">Looks like you haven't added any plants to your cart yet.</p>
                        <a href="catalog.php" class="btn btn-success btn-lg">
                            <i class="fas fa-leaf me-2"></i>Shop Plants
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Cart Items (<?php echo $total_items; ?>)</h5>
                            <button class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                                <i class="fas fa-trash me-1"></i>Clear Cart
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item border-bottom p-4" data-cart-id="<?php echo $item['cart_id']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="<?php echo $item['image'] ?: DEFAULT_PLANT_IMAGE; ?>" 
                                                 class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 style="height: 80px; width: 80px; object-fit: cover;">
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="mb-1">
                                                <a href="plant-detail.php?id=<?php echo $item['plant_id']; ?>" 
                                                   class="text-decoration-none text-success">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($item['botanical_name']); ?></small>
                                            <div class="mt-1">
                                                <span class="badge bg-light text-dark">
                                                    Stock: <?php echo $item['stock_quantity']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="fw-bold text-success">
                                                <?php echo format_price($item['price']); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="input-group input-group-sm">
                                                <button class="btn btn-outline-secondary" type="button" 
                                                        onclick="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" class="form-control text-center quantity-input" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" max="<?php echo $item['stock_quantity']; ?>"
                                                       onchange="updateQuantity(<?php echo $item['cart_id']; ?>, this.value)">
                                                <button class="btn btn-outline-secondary" type="button" 
                                                        onclick="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                                        <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="fw-bold">
                                                <?php echo format_price($item['subtotal']); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="removeFromCart(<?php echo $item['cart_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Continue Shopping -->
                    <div class="mt-3">
                        <a href="catalog.php" class="btn btn-outline-success">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal (<?php echo $total_items; ?> items):</span>
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
                            <?php if ($total_amount < 50 && $shipping_cost > 0): ?>
                                <div class="alert alert-info small">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Add <?php echo format_price(50 - $total_amount); ?> more for free shipping!
                                </div>
                            <?php endif; ?>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong class="text-success"><?php echo format_price($final_total); ?></strong>
                            </div>
                            
                            <div class="d-grid">
                                <a href="checkout.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                                </a>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Secure checkout with SSL encryption
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Info -->
                    <div class="card shadow-sm mt-3">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-truck text-success me-2"></i>Shipping Information
                            </h6>
                            <ul class="list-unstyled small mb-0">
                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Free shipping on orders over $50</li>
                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Standard delivery: 3-5 business days</li>
                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Express delivery available</li>
                                <li><i class="fas fa-check text-success me-2"></i>Plant care instructions included</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function updateQuantity(cartId, quantity) {
            quantity = parseInt(quantity);
            
            if (quantity < 0) return;
            
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update',
                    cart_id: cartId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error updating cart', 'error');
            });
        }

        function removeFromCart(cartId) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }
            
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove',
                    cart_id: cartId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error removing item', 'error');
            });
        }

        function clearCart() {
            if (!confirm('Are you sure you want to clear your entire cart?')) {
                return;
            }
            
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'clear'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error clearing cart', 'error');
            });
        }
    </script>
</body>
</html>

<?php $db->close(); ?>

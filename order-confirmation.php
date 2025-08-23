<?php
require_once 'config/config.php';

if (!is_logged_in()) {
    redirect('auth/login.php');
}

$order_id = intval($_GET['order_id'] ?? 0);
if (!$order_id) {
    redirect('orders.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, u.full_name, u.email 
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('orders.php');
}

$order = $result->fetch_assoc();

// Get order items
$items_stmt = $conn->prepare("
    SELECT oi.*, p.name, p.botanical_name, p.image
    FROM order_items oi
    JOIN plants p ON oi.plant_id = p.plant_id
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <!-- Success Message -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="text-center">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h1 class="display-5 fw-bold text-success mb-2">Order Confirmed!</h1>
                    <p class="lead text-muted">Thank you for your order. We'll send you a confirmation email shortly.</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Order Details -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Order Number:</strong> #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['order_date'])); ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Status:</strong> 
                                <span class="badge bg-warning"><?php echo ucfirst($order['status']); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Total:</strong> 
                                <span class="text-success fw-bold"><?php echo format_price($order['total_amount']); ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <strong>Shipping Address:</strong><br>
                            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                        </div>
                        <?php if ($order['notes']): ?>
                            <div class="mb-3">
                                <strong>Order Notes:</strong><br>
                                <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php while ($item = $items_result->fetch_assoc()): ?>
                            <div class="border-bottom p-3">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="<?php echo $item['image'] ?: DEFAULT_PLANT_IMAGE; ?>" 
                                             class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             style="height: 60px; width: 60px; object-fit: cover;">
                                    </div>
                                    <div class="col-md-5">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['botanical_name']); ?></small>
                                    </div>
                                    <div class="col-md-2">
                                        <span>Qty: <?php echo $item['quantity']; ?></span>
                                    </div>
                                    <div class="col-md-2">
                                        <span><?php echo format_price($item['price']); ?></span>
                                    </div>
                                    <div class="col-md-1">
                                        <strong><?php echo format_price($item['price'] * $item['quantity']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">What's Next?</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                            <i class="fas fa-envelope text-success me-3 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Confirmation Email</h6>
                                <small class="text-muted">We'll send you an order confirmation email within a few minutes.</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <i class="fas fa-box text-success me-3 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Order Processing</h6>
                                <small class="text-muted">Your order will be processed and prepared for shipping within 1-2 business days.</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <i class="fas fa-truck text-success me-3 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Shipping</h6>
                                <small class="text-muted">Your plants will be carefully packaged and shipped within 3-5 business days.</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="fas fa-seedling text-success me-3 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Plant Care</h6>
                                <small class="text-muted">Each plant comes with detailed care instructions to help them thrive.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="orders.php" class="btn btn-success">
                                <i class="fas fa-list me-2"></i>View All Orders
                            </a>
                            <a href="catalog.php" class="btn btn-outline-success">
                                <i class="fas fa-leaf me-2"></i>Continue Shopping
                            </a>
                            <a href="my-garden.php" class="btn btn-outline-success">
                                <i class="fas fa-seedling me-2"></i>Add to My Garden
                            </a>
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

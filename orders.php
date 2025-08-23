<?php
require_once 'config/config.php';

if (!is_logged_in()) {
    redirect('auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get user's orders
$stmt = $conn->prepare("
    SELECT o.*, COUNT(oi.order_item_id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - <?php echo SITE_NAME; ?></title>
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
                <h1 class="display-5 fw-bold text-success mb-2">My Orders</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">My Orders</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($orders_result->num_rows > 0): ?>
            <div class="row">
                <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <div class="col-12 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h5>
                                    <small class="text-muted">Placed on <?php echo date('F j, Y', strtotime($order['order_date'])); ?></small>
                                </div>
                                <div class="text-end">
                                    <?php
                                    $status_class = match($order['status']) {
                                        'pending' => 'bg-warning',
                                        'confirmed' => 'bg-info',
                                        'shipped' => 'bg-primary',
                                        'delivered' => 'bg-success',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $status_class; ?> mb-2">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                    <div class="fw-bold text-success"><?php echo format_price($order['total_amount']); ?></div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <p class="mb-2">
                                            <i class="fas fa-box me-2"></i>
                                            <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] !== 1 ? 's' : ''; ?>
                                        </p>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            <?php echo substr(htmlspecialchars($order['shipping_address']), 0, 100) . '...'; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <a href="order-confirmation.php?order_id=<?php echo $order['order_id']; ?>" 
                                           class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <button class="btn btn-outline-danger btn-sm ms-2" 
                                                    onclick="cancelOrder(<?php echo $order['order_id']; ?>)">
                                                <i class="fas fa-times me-1"></i>Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
                <h3 class="text-muted mb-3">No orders yet</h3>
                <p class="text-muted mb-4">You haven't placed any orders yet. Start shopping to see your orders here.</p>
                <a href="catalog.php" class="btn btn-success btn-lg">
                    <i class="fas fa-leaf me-2"></i>Shop Plants
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function cancelOrder(orderId) {
            if (!confirm('Are you sure you want to cancel this order?')) {
                return;
            }
            
            // In a real application, you would implement order cancellation
            showNotification('Order cancellation requested. We will process your request shortly.', 'info');
        }
    </script>
</body>
</html>

<?php $db->close(); ?>

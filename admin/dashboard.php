<?php
require_once '../config/config.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get dashboard statistics
$stats = [];

// Total plants
$plants_result = $conn->query("SELECT COUNT(*) as total, SUM(stock_quantity) as total_stock FROM plants WHERE is_active = 1");
$plants_data = $plants_result->fetch_assoc();
$stats['total_plants'] = $plants_data['total'];
$stats['total_stock'] = $plants_data['total_stock'];

// Total users
$users_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['total_users'] = $users_result->fetch_assoc()['total'];

// Total orders
$orders_result = $conn->query("SELECT COUNT(*) as total, SUM(total_amount) as total_revenue FROM orders");
$orders_data = $orders_result->fetch_assoc();
$stats['total_orders'] = $orders_data['total'];
$stats['total_revenue'] = $orders_data['total_revenue'] ?? 0;

// Pending orders
$pending_result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $pending_result->fetch_assoc()['total'];

// Pending reviews
$reviews_result = $conn->query("SELECT COUNT(*) as total FROM reviews WHERE is_approved = 0");
$stats['pending_reviews'] = $reviews_result->fetch_assoc()['total'];

// Recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.full_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    ORDER BY o.order_date DESC 
    LIMIT 5
");

// Low stock plants
$low_stock = $conn->query("
    SELECT * FROM plants 
    WHERE is_active = 1 AND stock_quantity <= 5 
    ORDER BY stock_quantity ASC 
    LIMIT 5
");

// Recent reviews
$recent_reviews = $conn->query("
    SELECT r.*, u.full_name, p.name as plant_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.user_id 
    JOIN plants p ON r.plant_id = p.plant_id 
    WHERE r.is_approved = 0 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <div class="container-fluid my-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-5 fw-bold text-success mb-2">Admin Dashboard</h1>
                <p class="lead text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']); ?>!</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo number_format($stats['total_plants']); ?></h4>
                                <p class="mb-0">Total Plants</p>
                                <small>Stock: <?php echo number_format($stats['total_stock']); ?></small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-seedling fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo number_format($stats['total_users']); ?></h4>
                                <p class="mb-0">Total Users</p>
                                <small>Registered customers</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo number_format($stats['total_orders']); ?></h4>
                                <p class="mb-0">Total Orders</p>
                                <small><?php echo $stats['pending_orders']; ?> pending</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo format_price($stats['total_revenue']); ?></h4>
                                <p class="mb-0">Total Revenue</p>
                                <small><?php echo $stats['pending_reviews']; ?> reviews pending</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Orders -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Orders</h5>
                        <a href="orders.php" class="btn btn-outline-primary btn-sm">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($recent_orders->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                <td>
                                                    <div class="small">
                                                        <?php echo htmlspecialchars($order['full_name']); ?><br>
                                                        <span class="text-muted"><?php echo htmlspecialchars($order['email']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo format_price($order['total_amount']); ?></td>
                                                <td>
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
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="small"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No recent orders</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Low Stock Alert</h5>
                        <a href="plants.php" class="btn btn-outline-warning btn-sm">Manage Plants</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($low_stock->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Plant</th>
                                            <th>Stock</th>
                                            <th>Price</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($plant = $low_stock->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="small">
                                                        <?php echo htmlspecialchars($plant['name']); ?><br>
                                                        <span class="text-muted"><?php echo htmlspecialchars($plant['botanical_name']); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $plant['stock_quantity'] <= 2 ? 'danger' : 'warning'; ?>">
                                                        <?php echo $plant['stock_quantity']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo format_price($plant['price']); ?></td>
                                                <td>
                                                    <a href="plants.php?edit=<?php echo $plant['plant_id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <p class="text-muted mb-0">All plants are well stocked</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Reviews -->
        <?php if ($recent_reviews->num_rows > 0): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Pending Reviews</h5>
                            <a href="reviews.php" class="btn btn-outline-info btn-sm">Manage Reviews</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Customer</th>
                                            <th>Plant</th>
                                            <th>Rating</th>
                                            <th>Review</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($review = $recent_reviews->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($review['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($review['plant_name']); ?></td>
                                                <td>
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'text-muted'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                                <td class="small">
                                                    <?php echo substr(htmlspecialchars($review['review_text']), 0, 100) . '...'; ?>
                                                </td>
                                                <td class="small"><?php echo time_ago($review['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button class="btn btn-success btn-sm" 
                                                                onclick="approveReview(<?php echo $review['review_id']; ?>)">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm" 
                                                                onclick="rejectReview(<?php echo $review['review_id']; ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function approveReview(reviewId) {
            fetch('../api/admin-reviews.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=approve&review_id=${reviewId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.message || 'Error approving review', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error approving review', 'error');
            });
        }

        function rejectReview(reviewId) {
            if (!confirm('Are you sure you want to reject this review?')) {
                return;
            }
            
            fetch('../api/admin-reviews.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reject&review_id=${reviewId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.message || 'Error rejecting review', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error rejecting review', 'error');
            });
        }
    </script>
</body>
</html>

<?php $db->close(); ?>

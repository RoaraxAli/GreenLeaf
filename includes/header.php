<?php
if (!isset($db)) {
    require_once 'config/config.php';
    $db = new Database();
}

// Get cart count for logged in users
$cart_count = 0;
if (is_logged_in()) {
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_data = $result->fetch_assoc();
    $cart_count = $cart_data['total'] ?? 0;
    $stmt->close();
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand text-success fw-bold" href="<?php echo SITE_URL; ?>">
            <i class="fas fa-leaf me-2"></i><?php echo SITE_NAME; ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/catalog.php">Plants</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/blog.php">Garden Tips</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/about.php#contact">Contact</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/my-garden.php">
                                <i class="fas fa-seedling me-2"></i>My Garden
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/orders.php">
                                <i class="fas fa-box me-2"></i>My Orders
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/reminders.php">
                                <i class="fas fa-bell me-2"></i>Reminders
                            </a></li>
                            <?php if (is_admin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-success" href="<?php echo SITE_URL; ?>/admin/">
                                    <i class="fas fa-cog me-2"></i>Admin Panel
                                </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-success text-white ms-2 px-3" href="<?php echo SITE_URL; ?>/auth/register.php">
                            <i class="fas fa-user-plus me-1"></i>Sign Up
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
    <div class="alert alert-success alert-dismissible fade show m-0" role="alert">
        <div class="container">
            <i class="fas fa-check-circle me-2"></i>You have been successfully logged out.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

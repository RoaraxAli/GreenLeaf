<!DOCTYPE html>
<html lang="en">
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet"></head>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="fas fa-leaf me-2"></i><?php echo SITE_NAME; ?> Admin
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" 
                       href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'plants.php' ? 'active' : ''; ?>" 
                       href="plants.php">
                        <i class="fas fa-seedling me-1"></i>Plants
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>" 
                       href="orders.php">
                        <i class="fas fa-shopping-cart me-1"></i>Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>" 
                       href="users.php">
                        <i class="fas fa-users me-1"></i>Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'blogs.php' ? 'active' : ''; ?>" 
                       href="blogs.php">
                        <i class="fas fa-blog me-1"></i>Blogs
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i>View Site
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../profile.php">
                            <i class="fas fa-user me-2"></i>My Profile
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

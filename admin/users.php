<?php
session_start();
require_once '../config/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $password = $_POST['password'] ?? '';
        
        // Validation
        if (empty($username) || empty($email) || ($action === 'add' && empty($password))) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } else {
            if ($action === 'add') {
                // Check if username or email exists
                $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?");
                $check_stmt->bind_param("ss", $username, $email);
                $check_stmt->execute();
                $count = $check_stmt->get_result()->fetch_assoc()['count'];
                
                if ($count > 0) {
                    $error = 'Username or email already exists.';
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        INSERT INTO users (username, email, password_hash, full_name, phone, address, role) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("sssssss", $username, $email, $password_hash, $full_name, $phone, $address, $role);
                    
                    if ($stmt->execute()) {
                        $message = 'User added successfully!';
                    } else {
                        $error = 'Error adding user.';
                    }
                }
            } else {
                // Update user
                $query = "UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, address = ?, role = ?";
                $types = "ssssss";
                $params = [&$username, &$email, &$full_name, &$phone, &$address, &$role];
                
                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $query .= ", password_hash = ?";
                    $types .= "s";
                    $params[] = &$password_hash;
                }
                $query .= " WHERE user_id = ?";
                $types .= "i";
                $params[] = &$user_id;
                
                $stmt = $db->prepare($query);
                $stmt->bind_param($types, ...$params);
                
                if ($stmt->execute()) {
                    $message = 'User updated successfully!';
                } else {
                    $error = 'Error updating user.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id === $_SESSION['user_id']) {
            $error = 'Cannot delete the current logged-in user.';
        } else {
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $message = 'User deleted successfully!';
            } else {
                $error = 'Error deleting user.';
            }
        }
    }
}

// Get search and filter parameters
$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$count_stmt = $db->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);

// Get users with pagination
$query = "SELECT user_id, username, email, full_name, phone, address, role, created_at,
          (SELECT COUNT(*) FROM orders WHERE user_id = users.user_id) as order_count
          FROM users 
          $where_clause 
          ORDER BY created_at DESC 
          LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    if ($edit_result->num_rows > 0) {
        $edit_user = $edit_result->fetch_assoc();
    }
}

include 'includes/admin-header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Greenleaf Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>User Management</h2>
                        <p class="text-muted">Manage user accounts and permissions</p>
                    </div>
                    <div>
                        <span class="text-muted me-3">Total Users: <?php echo $total_users; ?></span>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="fas fa-plus me-1"></i> Add New User
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Search and Filter Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search by Username, Email, or Full Name" 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <select name="role" class="form-select">
                                    <option value="">All Roles</option>
                                    <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="users.php" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User Info</th>
                                        <th>Role</th>
                                        <th>Orders</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></div>
                                            <?php if ($user['full_name']): ?>
                                            <div class="small"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($user['phone']): ?>
                                            <div class="small"><?php echo htmlspecialchars($user['phone']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($user['address']): ?>
                                            <div class="small"><?php echo htmlspecialchars($user['address']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $user['order_count']; ?></span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                            <div class="btn-group">
                                                <a href="?edit=<?php echo $user['user_id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <?php else: ?>
                                            <span class="text-muted small">Current User</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Users pagination">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $edit_user ? 'edit' : 'add'; ?>">
                        <?php if ($edit_user): ?>
                            <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password <?php echo $edit_user ? '(Leave blank to keep unchanged)' : '*'; ?></label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       <?php echo $edit_user ? '' : 'required'; ?>>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?php echo htmlspecialchars($edit_user['address'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="user" <?php echo ($edit_user['role'] ?? 'user') === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo ($edit_user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <?php echo $edit_user ? 'Update User' : 'Add User'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteUser(userId, username) {
            if (!confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('user_id', userId);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => location.reload())
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting user');
            });
        }

        // Auto-open modal if editing
        <?php if ($edit_user): ?>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('userModal')).show();
            });
        <?php endif; ?>
    </script>

<?php include '../includes/footer.php'; ?>
<?php $database->close(); ?>
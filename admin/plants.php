<?php
require_once '../config/config.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $plant_id = intval($_POST['plant_id'] ?? 0);
        $name = sanitize_input($_POST['name']);
        $botanical_name = sanitize_input($_POST['botanical_name']);
        $category = sanitize_input($_POST['category']);
        $price = floatval($_POST['price']);
        $care_level = sanitize_input($_POST['care_level']);
        $light_requirement = sanitize_input($_POST['light_requirement']);
        $watering_schedule = sanitize_input($_POST['watering_schedule']);
        $description = sanitize_input($_POST['description']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name) || empty($category) || $price <= 0) {
            $error = 'Please fill in all required fields with valid values.';
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare("
                    INSERT INTO plants (name, botanical_name, category, price, care_level, light_requirement, 
                                      watering_schedule, description, stock_quantity, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssdssssii", $name, $botanical_name, $category, $price, $care_level, 
                                $light_requirement, $watering_schedule, $description, $stock_quantity, $is_active);
                
                if ($stmt->execute()) {
                    $message = 'Plant added successfully!';
                } else {
                    $error = 'Error adding plant.';
                }
            } else {
                $stmt = $conn->prepare("
                    UPDATE plants 
                    SET name = ?, botanical_name = ?, category = ?, price = ?, care_level = ?, 
                        light_requirement = ?, watering_schedule = ?, description = ?, 
                        stock_quantity = ?, is_active = ?
                    WHERE plant_id = ?
                ");
                $stmt->bind_param("sssdssssiii", $name, $botanical_name, $category, $price, $care_level, 
                                $light_requirement, $watering_schedule, $description, $stock_quantity, $is_active, $plant_id);
                
                if ($stmt->execute()) {
                    $message = 'Plant updated successfully!';
                } else {
                    $error = 'Error updating plant.';
                }
            }
        }
    }
}

// Get plants with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$where_conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR botanical_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($category_filter)) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM plants $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_plants = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_plants = $conn->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_plants / $per_page);

// Get plants
$plants_query = "SELECT * FROM plants $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
if (!empty($params)) {
    $plants_stmt = $conn->prepare($plants_query);
    $plants_stmt->bind_param($types, ...$params);
    $plants_stmt->execute();
    $plants_result = $plants_stmt->get_result();
} else {
    $plants_result = $conn->query($plants_query);
}

// Get plant for editing
$edit_plant = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $conn->prepare("SELECT * FROM plants WHERE plant_id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    if ($edit_result->num_rows > 0) {
        $edit_plant = $edit_result->fetch_assoc();
    }
}

// Get categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM plants ORDER BY category");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Plants - <?php echo SITE_NAME; ?> Admin</title>
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
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="display-6 fw-bold text-success mb-2">Manage Plants</h1>
                        <p class="text-muted">Add, edit, and manage your plant inventory</p>
                    </div>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#plantModal">
                        <i class="fas fa-plus me-1"></i>Add New Plant
                    </button>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search plants..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['category']; ?>" 
                                        <?php echo $category_filter === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="text-muted">Total: <?php echo number_format($total_plants); ?> plants</span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Plants Table -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Care Level</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($plant = $plants_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo $plant['image'] ?: DEFAULT_PLANT_IMAGE; ?>" 
                                             class="rounded" style="width: 50px; height: 50px; object-fit: cover;"
                                             alt="<?php echo htmlspecialchars($plant['name']); ?>">
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($plant['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($plant['botanical_name']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $plant['category']; ?></span>
                                    </td>
                                    <td><?php echo format_price($plant['price']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $plant['stock_quantity'] <= 5 ? 'danger' : 'success'; ?>">
                                            <?php echo $plant['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $plant['care_level']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $plant['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $plant['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?edit=<?php echo $plant['plant_id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="deletePlant(<?php echo $plant['plant_id']; ?>)">
                                                <i class="fas fa-trash"></i>
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

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Plant Modal -->
    <div class="modal fade" id="plantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $edit_plant ? 'Edit Plant' : 'Add New Plant'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $edit_plant ? 'edit' : 'add'; ?>">
                        <?php if ($edit_plant): ?>
                            <input type="hidden" name="plant_id" value="<?php echo $edit_plant['plant_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Plant Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($edit_plant['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="botanical_name" class="form-label">Botanical Name</label>
                                <input type="text" class="form-control" id="botanical_name" name="botanical_name" 
                                       value="<?php echo htmlspecialchars($edit_plant['botanical_name'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="category" class="form-label">Category *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select category...</option>
                                    <option value="Indoor" <?php echo ($edit_plant['category'] ?? '') === 'Indoor' ? 'selected' : ''; ?>>Indoor</option>
                                    <option value="Outdoor" <?php echo ($edit_plant['category'] ?? '') === 'Outdoor' ? 'selected' : ''; ?>>Outdoor</option>
                                    <option value="Flowering" <?php echo ($edit_plant['category'] ?? '') === 'Flowering' ? 'selected' : ''; ?>>Flowering</option>
                                    <option value="Air-Purifying" <?php echo ($edit_plant['category'] ?? '') === 'Air-Purifying' ? 'selected' : ''; ?>>Air-Purifying</option>
                                    <option value="Seasonal" <?php echo ($edit_plant['category'] ?? '') === 'Seasonal' ? 'selected' : ''; ?>>Seasonal</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label">Price *</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0"
                                       value="<?php echo $edit_plant['price'] ?? ''; ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0"
                                       value="<?php echo $edit_plant['stock_quantity'] ?? '0'; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="care_level" class="form-label">Care Level</label>
                                <select class="form-select" id="care_level" name="care_level">
                                    <option value="Easy" <?php echo ($edit_plant['care_level'] ?? '') === 'Easy' ? 'selected' : ''; ?>>Easy</option>
                                    <option value="Medium" <?php echo ($edit_plant['care_level'] ?? '') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="Hard" <?php echo ($edit_plant['care_level'] ?? '') === 'Hard' ? 'selected' : ''; ?>>Hard</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="light_requirement" class="form-label">Light Requirement</label>
                                <select class="form-select" id="light_requirement" name="light_requirement">
                                    <option value="Low" <?php echo ($edit_plant['light_requirement'] ?? '') === 'Low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="Medium" <?php echo ($edit_plant['light_requirement'] ?? '') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="High" <?php echo ($edit_plant['light_requirement'] ?? '') === 'High' ? 'selected' : ''; ?>>High</option>
                                    <option value="Direct" <?php echo ($edit_plant['light_requirement'] ?? '') === 'Direct' ? 'selected' : ''; ?>>Direct</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="watering_schedule" class="form-label">Watering Schedule</label>
                                <input type="text" class="form-control" id="watering_schedule" name="watering_schedule" 
                                       placeholder="e.g., Weekly, Every 2 weeks"
                                       value="<?php echo htmlspecialchars($edit_plant['watering_schedule'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($edit_plant['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo ($edit_plant['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active (visible to customers)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <?php echo $edit_plant ? 'Update Plant' : 'Add Plant'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function deletePlant(plantId) {
            if (!confirm('Are you sure you want to delete this plant? This action cannot be undone.')) {
                return;
            }
            
            fetch('../api/admin-plants.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&plant_id=${plantId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.message || 'Error deleting plant', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error deleting plant', 'error');
            });
        }

        // Auto-open modal if editing
        <?php if ($edit_plant): ?>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('plantModal')).show();
            });
        <?php endif; ?>
    </script>
</body>
</html>

<?php $db->close(); ?>

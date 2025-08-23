<?php
require_once 'config/config.php';
$db = new Database();
$conn = $db->getConnection();

// Get filter parameters
$category = $_GET['category'] ?? '';
$care_level = $_GET['care_level'] ?? '';
$light_requirement = $_GET['light_requirement'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = ["is_active = 1"];
$params = [];
$types = "";

if (!empty($category)) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($care_level)) {
    $where_conditions[] = "care_level = ?";
    $params[] = $care_level;
    $types .= "s";
}

if (!empty($light_requirement)) {
    $where_conditions[] = "light_requirement = ?";
    $params[] = $light_requirement;
    $types .= "s";
}

if (!empty($min_price)) {
    $where_conditions[] = "price >= ?";
    $params[] = floatval($min_price);
    $types .= "d";
}

if (!empty($max_price)) {
    $where_conditions[] = "price <= ?";
    $params[] = floatval($max_price);
    $types .= "d";
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR botanical_name LIKE ? OR description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$where_clause = implode(" AND ", $where_conditions);

// Build ORDER BY clause
$order_by = "name ASC";
switch ($sort) {
    case 'name_desc':
        $order_by = "name DESC";
        break;
    case 'price_asc':
        $order_by = "price ASC";
        break;
    case 'price_desc':
        $order_by = "price DESC";
        break;
    case 'newest':
        $order_by = "created_at DESC";
        break;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM plants WHERE $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
} else {
    $count_result = $conn->query($count_query);
}
$total_plants = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_plants / $per_page);

// Get plants
$plants_query = "SELECT * FROM plants WHERE $where_clause ORDER BY $order_by LIMIT $per_page OFFSET $offset";
if (!empty($params)) {
    $plants_stmt = $conn->prepare($plants_query);
    $plants_stmt->bind_param($types, ...$params);
    $plants_stmt->execute();
    $plants_result = $plants_stmt->get_result();
} else {
    $plants_result = $conn->query($plants_query);
}

// Get filter options
$categories = $conn->query("SELECT DISTINCT category FROM plants WHERE is_active = 1 ORDER BY category");
$care_levels = $conn->query("SELECT DISTINCT care_level FROM plants WHERE is_active = 1 ORDER BY care_level");
$light_requirements = $conn->query("SELECT DISTINCT light_requirement FROM plants WHERE is_active = 1 ORDER BY light_requirement");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plant Catalog - <?php echo SITE_NAME; ?></title>
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
                <h1 class="display-5 fw-bold text-success mb-2">Plant Catalog</h1>
                <p class="lead text-muted">Discover the perfect plants for your home and garden</p>
            </div>
        </div>

        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="catalog.php" id="filterForm">
                            <!-- Search -->
                            <div class="mb-3">
                                <label for="search" class="form-label">Search Plants</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Plant name or description...">
                            </div>

                            <!-- Category Filter -->
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['category']; ?>" 
                                                <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Care Level Filter -->
                            <div class="mb-3">
                                <label for="care_level" class="form-label">Care Level</label>
                                <select class="form-select" id="care_level" name="care_level">
                                    <option value="">All Levels</option>
                                    <?php while ($level = $care_levels->fetch_assoc()): ?>
                                        <option value="<?php echo $level['care_level']; ?>" 
                                                <?php echo $care_level === $level['care_level'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($level['care_level']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Light Requirement Filter -->
                            <div class="mb-3">
                                <label for="light_requirement" class="form-label">Light Requirement</label>
                                <select class="form-select" id="light_requirement" name="light_requirement">
                                    <option value="">All Light Levels</option>
                                    <?php while ($light = $light_requirements->fetch_assoc()): ?>
                                        <option value="<?php echo $light['light_requirement']; ?>" 
                                                <?php echo $light_requirement === $light['light_requirement'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($light['light_requirement']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Price Range -->
                            <div class="mb-3">
                                <label class="form-label">Price Range</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="min_price" 
                                               placeholder="Min" value="<?php echo htmlspecialchars($min_price); ?>" 
                                               min="0" step="0.01">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="max_price" 
                                               placeholder="Max" value="<?php echo htmlspecialchars($max_price); ?>" 
                                               min="0" step="0.01">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-search me-2"></i>Apply Filters
                                </button>
                                <a href="catalog.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Clear All
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Plants Grid -->
            <div class="col-lg-9">
                <!-- Results Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-0">
                            <?php echo $total_plants; ?> plant<?php echo $total_plants !== 1 ? 's' : ''; ?> found
                        </h5>
                        <?php if (!empty($search)): ?>
                            <small class="text-muted">Search results for "<?php echo htmlspecialchars($search); ?>"</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <label for="sort" class="form-label me-2 mb-0">Sort by:</label>
                        <select class="form-select" id="sort" name="sort" style="width: auto;" onchange="updateSort(this.value)">
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        </select>
                    </div>
                </div>

                <!-- Plants Grid -->
                <?php if ($total_plants > 0): ?>
                    <div class="row">
                        <?php while ($plant = $plants_result->fetch_assoc()): ?>
                            <div class="col-md-6 col-xl-4 mb-4">
                                <div class="card h-100 shadow-sm plant-card">
                                    <div class="position-relative">
                                    <img src="placeholder.jfif"

                                             class="card-img-top" alt="<?php echo htmlspecialchars($plant['name']); ?>" 
                                             style="height: 250px; object-fit: cover;">
                                        <?php if ($plant['stock_quantity'] <= 5): ?>
                                            <span class="badge bg-warning position-absolute top-0 end-0 m-2">
                                                Low Stock
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title text-success"><?php echo htmlspecialchars($plant['name']); ?></h5>
                                        <p class="card-text text-muted small mb-2">
                                            <em><?php echo htmlspecialchars($plant['botanical_name']); ?></em>
                                        </p>
                                        <p class="card-text flex-grow-1">
                                            <?php echo substr(htmlspecialchars($plant['description']), 0, 100) . '...'; ?>
                                        </p>
                                        
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-secondary"><?php echo $plant['care_level']; ?></span>
                                                <span class="badge bg-info"><?php echo $plant['category']; ?></span>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-sun me-1"></i><?php echo $plant['light_requirement']; ?> Light
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-tint me-1"></i><?php echo $plant['watering_schedule']; ?>
                                            </small>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <span class="h5 text-success mb-0"><?php echo format_price($plant['price']); ?></span>
                                            <div class="btn-group">
                                                <a href="plant-detail.php?id=<?php echo $plant['plant_id']; ?>" 
                                                   class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (is_logged_in()): ?>
                                                    <button onclick="addToCart(<?php echo $plant['plant_id']; ?>)" 
                                                            class="btn btn-success btn-sm">
                                                        <i class="fas fa-cart-plus"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Plant catalog pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo buildPaginationUrl($page - 1); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo buildPaginationUrl($i); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo buildPaginationUrl($page + 1); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No plants found</h4>
                        <p class="text-muted">Try adjusting your filters or search terms.</p>
                        <a href="catalog.php" class="btn btn-success">View All Plants</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function updateSort(sortValue) {
            const url = new URL(window.location);
            url.searchParams.set('sort', sortValue);
            url.searchParams.delete('page'); // Reset to first page
            window.location.href = url.toString();
        }

        // Auto-submit form on filter change
        document.querySelectorAll('#filterForm select, #filterForm input').forEach(element => {
            element.addEventListener('change', function() {
                if (this.name !== 'search') {
                    document.getElementById('filterForm').submit();
                }
            });
        });

        // Search with debounce
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 500);
        });
    </script>
</body>
</html>

<?php
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'catalog.php?' . http_build_query($params);
}

$db->close();
?>

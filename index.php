<?php
require_once 'config/config.php';
$db = new Database();
$conn = $db->getConnection();

// Get featured plants
$featured_query = "SELECT * FROM plants WHERE is_active = 1 ORDER BY created_at DESC LIMIT 6";
$featured_result = $conn->query($featured_query);

// Get recent blog posts
$blog_query = "SELECT * FROM blogs WHERE is_published = 1 ORDER BY created_at DESC LIMIT 3";
$blog_result = $conn->query($blog_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Your Plant Paradise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-success mb-4">Welcome to Greenleaf</h1>
                    <p class="lead mb-4">Discover the perfect plants for your home and garden. From easy-care houseplants to exotic outdoor varieties, we have everything you need to create your green paradise.</p>
                    <div class="d-flex gap-3">
                        <a href="catalog.php" class="btn btn-success btn-lg">Shop Plants</a>
                        <a href="blogs.php" class="btn btn-outline-success btn-lg">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="image.webp" alt="Beautiful Plants" class="img-fluid rounded-3 shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Plants Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-success">Featured Plants</h2>
                <p class="lead">Discover our most popular and recommended plants</p>
            </div>
            <div class="row">
                <?php while ($plant = $featured_result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm plant-card">
                        <img src="<?php echo $plant['image'] ?: DEFAULT_PLANT_IMAGE; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($plant['name']); ?>" style="height: 250px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-success"><?php echo htmlspecialchars($plant['name']); ?></h5>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars($plant['botanical_name']); ?></p>
                            <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($plant['description']), 0, 100) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="h5 text-success mb-0"><?php echo format_price($plant['price']); ?></span>
                                <div>
                                    <span class="badge bg-secondary"><?php echo $plant['care_level']; ?></span>
                                    <span class="badge bg-info"><?php echo $plant['category']; ?></span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="plant-detail.php?id=<?php echo $plant['plant_id']; ?>" class="btn btn-success w-100">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <div class="text-center mt-4">
                <a href="catalog.php" class="btn btn-outline-success btn-lg">View All Plants</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-seedling fa-3x text-success"></i>
                    </div>
                    <h4>Expert Care Guides</h4>
                    <p>Get detailed care instructions and tips from our plant experts to keep your plants thriving.</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-bell fa-3x text-success"></i>
                    </div>
                    <h4>Care Reminders</h4>
                    <p>Never forget to water or fertilize your plants with our personalized reminder system.</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-users fa-3x text-success"></i>
                    </div>
                    <h4>Community Reviews</h4>
                    <p>Read reviews from fellow plant lovers and share your own experiences.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Blogs Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-success">Latest Garden Tips</h2>
                <p class="lead">Stay updated with the latest gardening advice and trends</p>
            </div>
            <div class="row">
                <?php while ($blog = $blog_result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo $blog['image'] ?: '/placeholder.svg?height=200&width=400'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($blog['title']); ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($blog['title']); ?></h5>
                            <p class="card-text flex-grow-1"><?php echo substr(strip_tags($blog['content']), 0, 120) . '...'; ?></p>
                            <div class="mt-auto">
                                <small class="text-muted"><?php echo time_ago($blog['created_at']); ?></small>
                                <a href="blog-detail.php?id=<?php echo $blog['blog_id']; ?>" class="btn btn-outline-success btn-sm float-end">Read More</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <div class="text-center mt-4">
                <a href="blogs.php" class="btn btn-outline-success btn-lg">View All Articles</a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

<?php $db->close(); ?>

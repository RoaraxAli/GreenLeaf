<?php
require_once 'config/config.php';

$plant_id = intval($_GET['id'] ?? 0);
if (!$plant_id) {
    redirect('catalog.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get plant details
$stmt = $conn->prepare("SELECT * FROM plants WHERE plant_id = ? AND is_active = 1");
$stmt->bind_param("i", $plant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('catalog.php');
}

$plant = $result->fetch_assoc();

// Get plant reviews
$reviews_stmt = $conn->prepare("
    SELECT r.*, u.username, u.full_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.user_id 
    WHERE r.plant_id = ? AND r.is_approved = 1 
    ORDER BY r.created_at DESC
");
$reviews_stmt->bind_param("i", $plant_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

// Get average rating
$rating_stmt = $conn->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
    FROM reviews 
    WHERE plant_id = ? AND is_approved = 1
");
$rating_stmt->bind_param("i", $plant_id);
$rating_stmt->execute();
$rating_data = $rating_stmt->get_result()->fetch_assoc();
$avg_rating = round($rating_data['avg_rating'], 1);
$total_reviews = $rating_data['total_reviews'];

// Get related plants
$related_stmt = $conn->prepare("
    SELECT * FROM plants 
    WHERE category = ? AND plant_id != ? AND is_active = 1 
    ORDER BY RAND() 
    LIMIT 4
");
$related_stmt->bind_param("si", $plant['category'], $plant_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();

// Check if user has already reviewed this plant
$user_has_reviewed = false;
if (is_logged_in()) {
    $user_review_stmt = $conn->prepare("SELECT review_id FROM reviews WHERE user_id = ? AND plant_id = ?");
    $user_review_stmt->bind_param("ii", $_SESSION['user_id'], $plant_id);
    $user_review_stmt->execute();
    $user_has_reviewed = $user_review_stmt->get_result()->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($plant['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="catalog.php">Plants</a></li>
                <li class="breadcrumb-item"><a href="catalog.php?category=<?php echo urlencode($plant['category']); ?>"><?php echo htmlspecialchars($plant['category']); ?></a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($plant['name']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Plant Image -->
            <div class="col-lg-6 mb-4">
                <div class="position-relative">
                    <img src="<?php echo $plant['image'] ?: DEFAULT_PLANT_IMAGE; ?>" 
                         class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($plant['name']); ?>"
                         style="width: 100%; height: 500px; object-fit: cover;">
                    <?php if ($plant['stock_quantity'] <= 5): ?>
                        <span class="badge bg-warning position-absolute top-0 end-0 m-3 fs-6">
                            Only <?php echo $plant['stock_quantity']; ?> left!
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Plant Details -->
            <div class="col-lg-6">
                <div class="mb-3">
                    <h1 class="display-5 fw-bold text-success mb-2"><?php echo htmlspecialchars($plant['name']); ?></h1>
                    <p class="text-muted fs-5 mb-3">
                        <em><?php echo htmlspecialchars($plant['botanical_name']); ?></em>
                    </p>
                    
                    <?php if ($total_reviews > 0): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $avg_rating ? 'text-warning' : 'text-muted'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="text-muted"><?php echo $avg_rating; ?> (<?php echo $total_reviews; ?> review<?php echo $total_reviews !== 1 ? 's' : ''; ?>)</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <span class="h2 text-success fw-bold"><?php echo format_price($plant['price']); ?></span>
                    <?php if ($plant['stock_quantity'] > 0): ?>
                        <span class="text-success ms-2"><i class="fas fa-check-circle"></i> In Stock</span>
                    <?php else: ?>
                        <span class="text-danger ms-2"><i class="fas fa-times-circle"></i> Out of Stock</span>
                    <?php endif; ?>
                </div>

                <!-- Plant Info -->
                <div class="row mb-4">
                    <div class="col-6">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-layer-group text-success me-2"></i>
                            <strong>Category:</strong>
                            <span class="ms-2 badge bg-info"><?php echo htmlspecialchars($plant['category']); ?></span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-heart text-success me-2"></i>
                            <strong>Care Level:</strong>
                            <span class="ms-2 badge bg-secondary"><?php echo htmlspecialchars($plant['care_level']); ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-sun text-success me-2"></i>
                            <strong>Light:</strong>
                            <span class="ms-2"><?php echo htmlspecialchars($plant['light_requirement']); ?></span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-tint text-success me-2"></i>
                            <strong>Watering:</strong>
                            <span class="ms-2"><?php echo htmlspecialchars($plant['watering_schedule']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Add to Cart -->
                <?php if (is_logged_in()): ?>
                    <div class="mb-4">
                        <div class="row align-items-center">
                            <div class="col-4">
                                <label for="quantity" class="form-label">Quantity:</label>
                                <input type="number" class="form-control" id="quantity" value="1" min="1" max="<?php echo $plant['stock_quantity']; ?>">
                            </div>
                            <div class="col-8">
                                <button onclick="addToCartWithQuantity(<?php echo $plant['plant_id']; ?>)" 
                                        class="btn btn-success btn-lg w-100" 
                                        <?php echo $plant['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <a href="auth/login.php" class="alert-link">Login</a> to add plants to your cart.
                    </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="d-flex gap-2 mb-4">
                    <button class="btn btn-outline-success" onclick="shareProduct()">
                        <i class="fas fa-share-alt me-1"></i>Share
                    </button>
                    <?php if (is_logged_in()): ?>
                        <button class="btn btn-outline-success" onclick="addToWishlist(<?php echo $plant['plant_id']; ?>)">
                            <i class="fas fa-heart me-1"></i>Save
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Plant Description -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="mb-0">About This Plant</h3>
                    </div>
                    <div class="card-body">
                        <p class="lead"><?php echo nl2br(htmlspecialchars($plant['description'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Customer Reviews</h3>
                        <?php if (is_logged_in() && !$user_has_reviewed): ?>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                <i class="fas fa-star me-1"></i>Write Review
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($total_reviews > 0): ?>
                            <?php while ($review = $reviews_result->fetch_assoc()): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($review['full_name'] ?: $review['username']); ?></strong>
                                            <div class="text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo time_ago($review['created_at']); ?></small>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No reviews yet. Be the first to review this plant!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Plants -->
        <?php if ($related_result->num_rows > 0): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <h3 class="mb-4">Related Plants</h3>
                    <div class="row">
                        <?php while ($related = $related_result->fetch_assoc()): ?>
                            <div class="col-md-3 mb-4">
                                <div class="card h-100 shadow-sm plant-card">
                                    <img src="<?php echo $related['image'] ?: DEFAULT_PLANT_IMAGE; ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($related['name']); ?>"
                                         style="height: 200px; object-fit: cover;">
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title text-success"><?php echo htmlspecialchars($related['name']); ?></h6>
                                        <p class="card-text flex-grow-1 small">
                                            <?php echo substr(htmlspecialchars($related['description']), 0, 80) . '...'; ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <span class="fw-bold text-success"><?php echo format_price($related['price']); ?></span>
                                            <a href="plant-detail.php?id=<?php echo $related['plant_id']; ?>" 
                                               class="btn btn-outline-success btn-sm">View</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Review Modal -->
    <?php if (is_logged_in() && !$user_has_reviewed): ?>
        <div class="modal fade" id="reviewModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Write a Review</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="api/submit-review.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="plant_id" value="<?php echo $plant_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <div class="rating-input">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>" class="star-label">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="review_text" class="form-label">Your Review</label>
                                <textarea class="form-control" id="review_text" name="review_text" rows="4" 
                                          placeholder="Share your experience with this plant..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Submit Review</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function addToCartWithQuantity(plantId) {
            const quantity = document.getElementById('quantity').value;
            addToCart(plantId, parseInt(quantity));
        }

        function shareProduct() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo htmlspecialchars($plant['name']); ?>',
                    text: 'Check out this amazing plant!',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    showNotification('Link copied to clipboard!', 'success');
                });
            }
        }

        function addToWishlist(plantId) {
            // Implement wishlist functionality
            showNotification('Added to wishlist!', 'success');
        }

        // Rating input styling
        document.addEventListener('DOMContentLoaded', function() {
            const ratingInputs = document.querySelectorAll('.rating-input input[type="radio"]');
            const starLabels = document.querySelectorAll('.star-label');
            
            starLabels.forEach((label, index) => {
                label.addEventListener('mouseover', function() {
                    highlightStars(5 - index);
                });
                
                label.addEventListener('click', function() {
                    selectStars(5 - index);
                });
            });
            
            document.querySelector('.rating-input').addEventListener('mouseleave', function() {
                const checkedInput = document.querySelector('.rating-input input[type="radio"]:checked');
                if (checkedInput) {
                    highlightStars(parseInt(checkedInput.value));
                } else {
                    highlightStars(0);
                }
            });
        });

        function highlightStars(rating) {
            const starLabels = document.querySelectorAll('.star-label i');
            starLabels.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('text-warning');
                    star.classList.remove('text-muted');
                } else {
                    star.classList.add('text-muted');
                    star.classList.remove('text-warning');
                }
            });
        }

        function selectStars(rating) {
            const input = document.querySelector(`input[name="rating"][value="${rating}"]`);
            if (input) {
                input.checked = true;
            }
            highlightStars(rating);
        }
    </script>

    <style>
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .rating-input input[type="radio"] {
            display: none;
        }

        .star-label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #ddd;
            margin-right: 5px;
        }

        .star-label:hover i,
        .rating-input input[type="radio"]:checked ~ .star-label i {
            color: #ffc107;
        }
    </style>
</body>
</html>

<?php $db->close(); ?>

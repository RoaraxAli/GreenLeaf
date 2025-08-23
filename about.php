<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Placeholder for contact form submission (to be implemented)
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $message = 'Thank you for your message! (Note: Contact form submission is not yet implemented.)';
    }
}

$page_title = "About Greenleaf - Plant Care & Gardening";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Greenleaf</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">About Greenleaf</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Your trusted companion for plant care, gardening tips, and sustainable living
            </p>
        </div>

        <!-- About Content -->
        <section class="bg-white rounded-lg shadow-md p-6 mb-12">
            <div class="row g-4">
                <div class="col-md-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Our Mission</h2>
                    <p class="text-gray-600 mb-4">
                        At Greenleaf, we are passionate about promoting environmental sustainability through home gardening and plant care. Our mission is to empower plant enthusiasts, gardeners, and nursery owners with a comprehensive platform that simplifies plant selection, care, and learning.
                    </p>
                    <p class="text-gray-600 mb-4">
                        Whether you're a beginner nurturing your first indoor plant or an experienced gardener cultivating a vibrant outdoor garden, Greenleaf provides the tools and knowledge to make your gardening journey rewarding and enjoyable.
                    </p>
                </div>
                <div class="col-md-6">
                    <img src="assets/images/about-hero.jpg" alt="Greenleaf Mission" class="w-100 h-100 object-cover rounded-lg" style="max-height: 300px;">
                </div>
            </div>

            <div class="mt-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">What We Offer</h2>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex align-items-start">
                        <i class="fas fa-leaf text-success me-3 mt-1"></i>
                        <div>
                            <strong>Plant Catalog</strong>: Browse a wide variety of plants with detailed care instructions, including light requirements, watering schedules, and more.
                        </div>
                    </li>
                    <li class="list-group-item d-flex align-items-start">
                        <i class="fas fa-seedling text-success me-3 mt-1"></i>
                        <div>
                            <strong>Personalized Recommendations</strong>: Get plant suggestions tailored to your climate and preferences.
                        </div>
                    </li>
                    <li class="list-group-item d-flex align-items-start">
                        <i class="fas fa-book-open text-success me-3 mt-1"></i>
                        <div>
                            <strong>Gardening Tips & Blogs</strong>: Access expert articles, seasonal guides, and tutorial videos to enhance your gardening skills.
                        </div>
                    </li>
                    <li class="list-group-item d-flex align-items-start">
                        <i class="fas fa-cart-shopping text-success me-3 mt-1"></i>
                        <div>
                            <strong>Shopping Made Easy</strong>: Add plants and gardening tools to your cart and view your selections with ease.
                        </div>
                    </li>
                    <li class="list-group-item d-flex align-items-start">
                        <i class="fas fa-bell text-success me-3 mt-1"></i>
                        <div>
                            <strong>Care Reminders</strong>: Set up notifications for watering, fertilizing, and pruning to keep your plants thriving.
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="bg-white rounded-lg shadow-md p-6 mb-12">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4 text-center">Contact Us</h2>
            <p class="text-gray-600 mb-6 text-center max-w-2xl mx-auto">
                Have questions or need help? Reach out to us, and we'll get back to you as soon as possible.
            </p>

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

            <div class="row g-4">
                <div class="col-md-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-3">Get in Touch</h3>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject *</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Send Message</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-3">Contact Information</h3>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex align-items-start">
                            <i class="fas fa-envelope text-success me-3 mt-1"></i>
                            <div>
                                <strong>Email</strong>: <a href="mailto:support@greenleaf.com">support@greenleaf.com</a>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start">
                            <i class="fas fa-phone text-success me-3 mt-1"></i>
                            <div>
                                <strong>Phone</strong>: +1 (555) 123-4567
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start">
                            <i class="fas fa-map-marker-alt text-success me-3 mt-1"></i>
                            <div>
                                <strong>Address</strong>: 123 Greenleaf Lane, Garden City, NY 12345
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-start">
                            <i class="fas fa-clock text-success me-3 mt-1"></i>
                            <div>
                                <strong>Hours</strong>: Mon-Fri, 9 AM - 5 PM
                            </div>
                        </li>
                    </ul>
                    <div class="mt-4">
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Follow Us</h4>
                        <div class="d-flex gap-3">
                            <a href="https://facebook.com/greenleaf" class="text-success"><i class="fab fa-facebook-f fa-2x"></i></a>
                            <a href="https://twitter.com/greenleaf" class="text-success"><i class="fab fa-twitter fa-2x"></i></a>
                            <a href="https://instagram.com/greenleaf" class="text-success"><i class="fab fa-instagram fa-2x"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include 'includes/footer.php'; ?>
<?php $database->close(); ?>
<!-- Bootstrap CSS CDN -->
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<!-- Back Button with Text and Icon -->
<a href="javascript:history.back()" class="btn btn-light">
    <i class="bi bi-arrow-left"></i> Back
</a>
<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$blog_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$blog_id) {
    header('Location: blog.php');
    exit;
}

// Get blog post
$query = "SELECT * FROM blogs WHERE blog_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $blog_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    header('Location: blog.php');
    exit;
}

// Get related posts
$related_query = "SELECT blog_id, title, image, created_at 
                  FROM blogs 
                  WHERE blog_id != ? AND category = ? 
                  ORDER BY created_at DESC 
                  LIMIT 3";
$related_stmt = $db->prepare($related_query);
$related_stmt->bind_param('is', $blog_id, $post['category']);
$related_stmt->execute();
$related_posts = $related_stmt->get_result();

$page_title = htmlspecialchars($post['title']);
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 text-sm text-gray-600">
            <li><a href="index.php" class="hover:text-green-600">Home</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="blog.php" class="hover:text-green-600">Blog</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-800"><?php echo htmlspecialchars($post['title']); ?></li>
        </ol>
    </nav>

    <article class="max-w-4xl mx-auto">
        <!-- Article Header -->
        <header class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <?php if ($post['category']): ?>
                    <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full">
                        <?php echo htmlspecialchars($post['category']); ?>
                    </span>
                <?php endif; ?>
                <span class="text-gray-500">
                    <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                </span>
            </div>
            
            <h1 class="text-4xl font-bold text-gray-800 mb-4">
                <?php echo htmlspecialchars($post['title']); ?>
            </h1>
            
            <div class="flex items-center text-gray-600">
                <span>By <?php echo htmlspecialchars($post['author'] ?? 'Admin'); ?></span>
            </div>
        </header>

        <!-- Featured Image -->
        <?php if ($post['image']): ?>
            <div class="mb-8">
                <img src="<?php echo htmlspecialchars($post['image']); ?>" 
                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                     class="w-full h-96 object-cover rounded-lg shadow-lg">
            </div>
        <?php endif; ?>

        <!-- Article Content -->
        <div class="prose prose-lg max-w-none mb-12">
            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
        </div>

        <!-- Share Section -->
        <div class="border-t border-gray-200 pt-8 mb-12">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Share this article</h3>
            <div class="flex space-x-4">
                <a href="#" class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                    </svg>
                    Twitter
                </a>
                <a href="#" class="flex items-center px-4 py-2 bg-blue-800 text-white rounded-lg hover:bg-blue-900 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Facebook
                </a>
            </div>
        </div>
    </article>

    <!-- Related Posts -->
    <?php if ($related_posts->num_rows > 0): ?>
        <section class="max-w-6xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-8">Related Articles</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php while ($related = $related_posts->fetch_assoc()): ?>
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <?php if ($related['image']): ?>
                            <img src="<?php echo htmlspecialchars($related['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($related['title']); ?>"
                                 class="w-full h-48 object-cover">
                        <?php else: ?>
                            <div class="w-full h-48 bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
                                <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                                <?php echo htmlspecialchars($related['title']); ?>
                            </h3>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($related['created_at'])); ?>
                                </span>
                                <a href="blog-detail.php?id=<?php echo $related['blog_id']; ?>" 
                                   class="text-green-600 hover:text-green-700 text-sm font-medium">
                                    Read More →
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<style>
.prose {
    color: #374151;
    line-height: 1.75;
}

.prose p {
    margin-bottom: 1.25em;
}

.prose h2 {
    font-size: 1.5em;
    font-weight: 600;
    margin-top: 2em;
    margin-bottom: 1em;
    color: #1f2937;
}

.prose h3 {
    font-size: 1.25em;
    font-weight: 600;
    margin-top: 1.6em;
    margin-bottom: 0.6em;
    color: #1f2937;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include 'includes/footer.php'; ?>

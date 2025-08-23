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

// Get search and category filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts_per_page = 6;
$offset = ($page - 1) * $posts_per_page;

// Build query
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if (!empty($category)) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total posts for pagination
$count_query = "SELECT COUNT(*) as total FROM blogs $where_clause";
$count_stmt = $db->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_posts = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Get posts
$query = "SELECT blog_id, title, content, image, category, created_at
          FROM blogs $where_clause 
          ORDER BY created_at DESC 
          LIMIT ? OFFSET ?";

$params[] = $posts_per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$posts = $stmt->get_result();

// Get categories for filter
$cat_query = "SELECT DISTINCT category FROM blogs WHERE category IS NOT NULL ORDER BY category";
$categories = $db->query($cat_query);

$page_title = "Gardening Blog - Tips & Guides";
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header Section -->
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Gardening Blog</h1>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Discover expert tips, seasonal guides, and everything you need to know about plant care
        </p>
    </div>

    <!-- Search and Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search articles..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>
            <div class="md:w-48">
                <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">All Categories</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                Search
            </button>
        </form>
    </div>

    <!-- Blog Posts Grid -->
    <?php if ($posts->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <?php while ($post = $posts->fetch_assoc()): ?>
                <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <?php if ($post['image']): ?>
                        <img src="<?php echo htmlspecialchars($post['image']); ?>" 
                             alt="<?php echo htmlspecialchars($post['title']); ?>"
                             class="w-full h-48 object-cover">
                    <?php else: ?>
                        <div class="w-full h-48 bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
                            <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            <?php if ($post['category']): ?>
                                <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full">
                                    <?php echo htmlspecialchars($post['category']); ?>
                                </span>
                            <?php endif; ?>
                            <span class="text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                            </span>
                        </div>
                        
                        <h2 class="text-xl font-semibold text-gray-800 mb-3 line-clamp-2">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </h2>
                        
                        <p class="text-gray-600 mb-4 line-clamp-3">
                            <?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 150)) . '...'; ?>
                        </p>
                        
                        <div class="flex items-center justify-between">
                            <a href="blog-detail.php?id=<?php echo $post['blog_id']; ?>" 
                               class="text-green-600 hover:text-green-700 font-medium">
                                Read More →
                            </a>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center">
                <nav class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
                           class="px-4 py-2 <?php echo $i === $page ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-lg">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Next
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center py-12">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No articles found</h3>
            <p class="text-gray-500">Try adjusting your search criteria or check back later for new content.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include 'includes/footer.php'; ?>

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
        $blog_id = intval($_POST['blog_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $image = trim($_POST['image'] ?? '');
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $author_id = $_SESSION['user_id']; // Use current admin's user_id
        
        // Validation
        if (empty($title) || empty($content)) {
            $error = 'Please fill in all required fields.';
        } else {
            if ($action === 'add') {
                $stmt = $db->prepare("
                    INSERT INTO blogs (title, content, category, image, author_id, is_published, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->bind_param("ssssii", $title, $content, $category, $image, $author_id, $is_published);
                
                if ($stmt->execute()) {
                    $message = 'Blog post added successfully!';
                } else {
                    $error = 'Error adding blog post.';
                }
            } else {
                $stmt = $db->prepare("
                    UPDATE blogs 
                    SET title = ?, content = ?, category = ?, image = ?, author_id = ?, is_published = ?, updated_at = NOW()
                    WHERE blog_id = ?
                ");
                $stmt->bind_param("ssssiii", $title, $content, $category, $image, $author_id, $is_published, $blog_id);
                
                if ($stmt->execute()) {
                    $message = 'Blog post updated successfully!';
                } else {
                    $error = 'Error updating blog post.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $blog_id = intval($_POST['blog_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM blogs WHERE blog_id = ?");
        $stmt->bind_param("i", $blog_id);
        
        if ($stmt->execute()) {
            $message = 'Blog post deleted successfully!';
        } else {
            $error = 'Error deleting blog post.';
        }
    }
}

// Get search and filter parameters
$search = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$is_published_filter = $_GET['is_published'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR content LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

if (!empty($category_filter)) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
    $param_types .= 's';
}

if ($is_published_filter !== '') {
    $where_conditions[] = "is_published = ?";
    $params[] = (int)$is_published_filter;
    $param_types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM blogs $where_clause";
$count_stmt = $db->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_blogs = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_blogs / $per_page);

// Get blogs with pagination
$query = "SELECT b.*, u.username as author_name 
          FROM blogs b 
          LEFT JOIN users u ON b.author_id = u.user_id 
          $where_clause 
          ORDER BY b.created_at DESC 
          LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$blogs = $stmt->get_result();

// Get blog for editing
$edit_blog = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $db->prepare("SELECT * FROM blogs WHERE blog_id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    if ($edit_result->num_rows > 0) {
        $edit_blog = $edit_result->fetch_assoc();
    }
}

// Get categories for filter
$categories = $db->query("SELECT DISTINCT category FROM blogs WHERE category IS NOT NULL AND category != '' ORDER BY category");

include 'includes/admin-header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Management - Greenleaf Admin</title>
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
                        <h2>Blog Management</h2>
                        <p class="text-muted">Manage blog posts and content</p>
                    </div>
                    <div>
                        <span class="text-muted me-3">Total Posts: <?php echo $total_blogs; ?></span>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#blogModal">
                            <i class="fas fa-plus me-1"></i> Add New Post
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
                                       placeholder="Search by Title or Content" 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                                <?php echo $category_filter === $cat['category'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="is_published" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="1" <?php echo $is_published_filter === '1' ? 'selected' : ''; ?>>Published</option>
                                    <option value="0" <?php echo $is_published_filter === '0' ? 'selected' : ''; ?>>Unpublished</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="blogs.php" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Blogs Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Author</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Views</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($blog = $blogs->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($blog['image'] ?: '/placeholder.svg?height=50&width=50'); ?>" 
                                                 class="rounded" style="width: 50px; height: 50px; object-fit: cover;"
                                                 alt="<?php echo htmlspecialchars($blog['title']); ?>">
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($blog['title']); ?></div>
                                            <div class="text-muted small">
                                                <?php echo htmlspecialchars(substr(strip_tags($blog['content']), 0, 100)) . '...'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($blog['category'] ?: 'Uncategorized'); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($blog['author_name'] ?: 'Unknown'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $blog['is_published'] ? 'success' : 'danger'; ?>">
                                                <?php echo $blog['is_published'] ? 'Published' : 'Unpublished'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($blog['created_at'])); ?></td>
                                        <td><?php echo $blog['views']; ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?edit=<?php echo $blog['blog_id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteBlog(<?php echo $blog['blog_id']; ?>, '<?php echo htmlspecialchars($blog['title']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Blogs pagination">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&is_published=<?php echo urlencode($is_published_filter); ?>">
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

    <!-- Blog Modal -->
    <div class="modal fade" id="blogModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $edit_blog ? 'Edit Blog Post' : 'Add New Blog Post'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $edit_blog ? 'edit' : 'add'; ?>">
                        <?php if ($edit_blog): ?>
                            <input type="hidden" name="blog_id" value="<?php echo $edit_blog['blog_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($edit_blog['title'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" 
                                   value="<?php echo htmlspecialchars($edit_blog['category'] ?? ''); ?>" 
                                   placeholder="e.g., Plant Care, Gardening Tips">
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="image" name="image" 
                                   value="<?php echo htmlspecialchars($edit_blog['image'] ?? ''); ?>" 
                                   placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Content *</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($edit_blog['content'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_published" name="is_published" 
                                   <?php echo ($edit_blog['is_published'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_published">
                                Published (visible to users)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <?php echo $edit_blog ? 'Update Post' : 'Add Post'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteBlog(blogId, title) {
            if (!confirm(`Are you sure you want to delete the blog post "${title}"? This action cannot be undone.`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('blog_id', blogId);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => location.reload())
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting blog post');
            });
        }

        // Auto-open modal if editing
        <?php if ($edit_blog): ?>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('blogModal')).show();
            });
        <?php endif; ?>
    </script>

<?php include '../includes/footer.php'; ?>
<?php $database->close(); ?>
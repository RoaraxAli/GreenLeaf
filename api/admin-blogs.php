<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        // Get blog statistics
        $stats = [];
        
        // Total blogs
        $result = $db->query("SELECT COUNT(*) as total FROM blogs");
        $stats['total_blogs'] = $result->fetch_assoc()['total'];
        
        // Blogs by category
        $result = $db->query("SELECT category, COUNT(*) as count FROM blogs WHERE category IS NOT NULL GROUP BY category ORDER BY count DESC LIMIT 5");
        $stats['categories'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['categories'][] = $row;
        }
        
        // Recent blogs
        $result = $db->query("SELECT blog_id, title, created_at FROM blogs ORDER BY created_at DESC LIMIT 5");
        $stats['recent_blogs'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['recent_blogs'][] = $row;
        }
        
        echo json_encode($stats);
        break;
        
    case 'POST':
        // Add new blog
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        $category = trim($input['category'] ?? '');
        $author = trim($input['author'] ?? 'Admin');
        $image = trim($input['image'] ?? '');
        
        if (empty($title) || empty($content)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and content are required']);
            exit;
        }
        
        $query = "INSERT INTO blogs (title, content, category, author, image, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bind_param('sssss', $title, $content, $category, $author, $image);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'blog_id' => $db->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create blog post']);
        }
        break;
        
    case 'PUT':
        // Update blog
        $blog_id = (int)($input['blog_id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        $category = trim($input['category'] ?? '');
        $author = trim($input['author'] ?? 'Admin');
        $image = trim($input['image'] ?? '');
        
        if (!$blog_id || empty($title) || empty($content)) {
            http_response_code(400);
            echo json_encode(['error' => 'Blog ID, title and content are required']);
            exit;
        }
        
        $query = "UPDATE blogs SET title = ?, content = ?, category = ?, author = ?, image = ? WHERE blog_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('sssssi', $title, $content, $category, $author, $image, $blog_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update blog post']);
        }
        break;
        
    case 'DELETE':
        // Delete blog
        $blog_id = (int)($input['blog_id'] ?? 0);
        
        if (!$blog_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Blog ID is required']);
            exit;
        }
        
        $query = "DELETE FROM blogs WHERE blog_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $blog_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete blog post']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>

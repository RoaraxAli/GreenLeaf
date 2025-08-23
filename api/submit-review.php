<?php
require_once '../config/config.php';

if (!is_logged_in()) {
    redirect('../auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../catalog.php');
}

$plant_id = intval($_POST['plant_id']);
$rating = intval($_POST['rating']);
$review_text = sanitize_input($_POST['review_text']);

// Validation
if (!$plant_id || $rating < 1 || $rating > 5) {
    redirect("../plant-detail.php?id=$plant_id&error=invalid_data");
}

$db = new Database();
$conn = $db->getConnection();

// Check if user already reviewed this plant
$check_stmt = $conn->prepare("SELECT review_id FROM reviews WHERE user_id = ? AND plant_id = ?");
$check_stmt->bind_param("ii", $_SESSION['user_id'], $plant_id);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows > 0) {
    redirect("../plant-detail.php?id=$plant_id&error=already_reviewed");
}

// Insert review
$stmt = $conn->prepare("INSERT INTO reviews (user_id, plant_id, rating, review_text) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $_SESSION['user_id'], $plant_id, $rating, $review_text);

if ($stmt->execute()) {
    redirect("../plant-detail.php?id=$plant_id&success=review_submitted");
} else {
    redirect("../plant-detail.php?id=$plant_id&error=review_failed");
}

$db->close();
?>

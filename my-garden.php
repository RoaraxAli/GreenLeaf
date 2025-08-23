<?php
require_once 'config/config.php';

if (!is_logged_in()) {
    redirect('auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get user's garden plants
$stmt = $conn->prepare("
    SELECT ug.*, p.name, p.botanical_name, p.image, p.care_level, p.light_requirement, p.watering_schedule,
           COUNT(r.reminder_id) as reminder_count
    FROM user_garden ug
    JOIN plants p ON ug.plant_id = p.plant_id
    LEFT JOIN reminders r ON ug.garden_id = r.garden_id AND r.is_completed = 0
    WHERE ug.user_id = ?
    GROUP BY ug.garden_id
    ORDER BY ug.date_added DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$garden_result = $stmt->get_result();

// Get plants from recent orders that can be added to garden
$available_stmt = $conn->prepare("
    SELECT DISTINCT p.plant_id, p.name, p.botanical_name, p.image
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN plants p ON oi.plant_id = p.plant_id
    WHERE o.user_id = ? AND o.status IN ('confirmed', 'shipped', 'delivered')
    AND p.plant_id NOT IN (
        SELECT plant_id FROM user_garden WHERE user_id = ?
    )
    ORDER BY o.order_date DESC
    LIMIT 10
");
$available_stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$available_stmt->execute();
$available_result = $available_stmt->get_result();

$message = '';
if (isset($_GET['success'])) {
    $message = match($_GET['success']) {
        'added' => 'Plant added to your garden successfully!',
        'updated' => 'Plant information updated successfully!',
        'removed' => 'Plant removed from your garden.',
        default => ''
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Garden - <?php echo SITE_NAME; ?></title>
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
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="display-5 fw-bold text-success mb-2">My Garden</h1>
                        <p class="lead text-muted">Track your plants and manage their care</p>
                    </div>
                    <div>
                        <a href="reminders.php" class="btn btn-outline-success me-2">
                            <i class="fas fa-bell me-1"></i>Care Reminders
                        </a>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPlantModal">
                            <i class="fas fa-plus me-1"></i>Add Plant
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($garden_result->num_rows > 0): ?>
            <!-- Garden Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-seedling fa-2x mb-2"></i>
                            <h4><?php echo $garden_result->num_rows; ?></h4>
                            <p class="mb-0">Plants in Garden</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-bell fa-2x mb-2"></i>
                            <h4 id="activeReminders">0</h4>
                            <p class="mb-0">Active Reminders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar fa-2x mb-2"></i>
                            <h4 id="todayTasks">0</h4>
                            <p class="mb-0">Tasks Today</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                            <h4><?php echo date('j'); ?></h4>
                            <p class="mb-0">Days This Month</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Garden Plants -->
            <div class="row">
                <?php 
                $garden_result->data_seek(0); // Reset result pointer
                while ($plant = $garden_result->fetch_assoc()): 
                    $growth_images = json_decode($plant['growth_images'] ?? '[]', true);
                ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm garden-plant-card">
                            <div class="position-relative">
                                <img src="<?php echo $plant['image'] ?: DEFAULT_PLANT_IMAGE; ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($plant['name']); ?>"
                                     style="height: 200px; object-fit: cover;">
                                <?php if ($plant['reminder_count'] > 0): ?>
                                    <span class="badge bg-warning position-absolute top-0 end-0 m-2">
                                        <?php echo $plant['reminder_count']; ?> reminder<?php echo $plant['reminder_count'] !== 1 ? 's' : ''; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-success">
                                    <?php echo htmlspecialchars($plant['plant_name'] ?: $plant['name']); ?>
                                </h5>
                                <p class="card-text text-muted small mb-2">
                                    <em><?php echo htmlspecialchars($plant['botanical_name']); ?></em>
                                </p>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>Added <?php echo time_ago($plant['date_added']); ?>
                                    </small>
                                </div>

                                <!-- Care Info -->
                                <div class="mb-3">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <i class="fas fa-heart text-success"></i>
                                            <div class="small"><?php echo $plant['care_level']; ?></div>
                                        </div>
                                        <div class="col-4">
                                            <i class="fas fa-sun text-warning"></i>
                                            <div class="small"><?php echo $plant['light_requirement']; ?></div>
                                        </div>
                                        <div class="col-4">
                                            <i class="fas fa-tint text-info"></i>
                                            <div class="small"><?php echo substr($plant['watering_schedule'], 0, 10); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($plant['notes']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <strong>Notes:</strong> <?php echo substr(htmlspecialchars($plant['notes']), 0, 100) . (strlen($plant['notes']) > 100 ? '...' : ''); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <!-- Growth Images -->
                                <?php if (!empty($growth_images)): ?>
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-2">Growth Progress:</small>
                                        <div class="d-flex gap-1">
                                            <?php foreach (array_slice($growth_images, -3) as $image): ?>
                                                <img src="<?php echo htmlspecialchars($image); ?>" 
                                                     class="rounded" style="width: 40px; height: 40px; object-fit: cover;"
                                                     alt="Growth progress">
                                            <?php endforeach; ?>
                                            <?php if (count($growth_images) > 3): ?>
                                                <div class="d-flex align-items-center justify-content-center bg-light rounded" 
                                                     style="width: 40px; height: 40px;">
                                                    <small>+<?php echo count($growth_images) - 3; ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Actions -->
                                <div class="mt-auto">
                                    <div class="btn-group w-100" role="group">
                                        <button class="btn btn-outline-success btn-sm" 
                                                onclick="editPlant(<?php echo $plant['garden_id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-info btn-sm" 
                                                onclick="addReminder(<?php echo $plant['garden_id']; ?>)">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm" 
                                                onclick="addGrowthImage(<?php echo $plant['garden_id']; ?>)">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" 
                                                onclick="removePlant(<?php echo $plant['garden_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <!-- Empty Garden -->
            <div class="text-center py-5">
                <i class="fas fa-seedling fa-4x text-muted mb-4"></i>
                <h3 class="text-muted mb-3">Your garden is empty</h3>
                <p class="text-muted mb-4">Start building your digital garden by adding plants from your orders.</p>
                <?php if ($available_result->num_rows > 0): ?>
                    <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addPlantModal">
                        <i class="fas fa-plus me-2"></i>Add Your First Plant
                    </button>
                <?php else: ?>
                    <a href="catalog.php" class="btn btn-success btn-lg">
                        <i class="fas fa-shopping-cart me-2"></i>Shop Plants First
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Plant Modal -->
    <div class="modal fade" id="addPlantModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Plant to Garden</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="api/garden.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <?php if ($available_result->num_rows > 0): ?>
                            <div class="mb-3">
                                <label for="plant_id" class="form-label">Select Plant</label>
                                <select class="form-select" id="plant_id" name="plant_id" required>
                                    <option value="">Choose a plant from your orders...</option>
                                    <?php while ($available = $available_result->fetch_assoc()): ?>
                                        <option value="<?php echo $available['plant_id']; ?>">
                                            <?php echo htmlspecialchars($available['name']); ?>
                                            <?php if ($available['botanical_name']): ?>
                                                (<?php echo htmlspecialchars($available['botanical_name']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="plant_name" class="form-label">Custom Name (Optional)</label>
                                <input type="text" class="form-control" id="plant_name" name="plant_name" 
                                       placeholder="Give your plant a personal name...">
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Initial Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Add any notes about your plant..."></textarea>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                You need to purchase plants first before adding them to your garden.
                                <a href="catalog.php" class="alert-link">Shop plants now</a>.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <?php if ($available_result->num_rows > 0): ?>
                            <button type="submit" class="btn btn-success">Add to Garden</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function editPlant(gardenId) {
            // Implement edit plant functionality
            showNotification('Edit plant feature coming soon!', 'info');
        }

        function addReminder(gardenId) {
            window.location.href = `reminders.php?add=1&garden_id=${gardenId}`;
        }

        function addGrowthImage(gardenId) {
            // Implement add growth image functionality
            showNotification('Growth tracking feature coming soon!', 'info');
        }

        function removePlant(gardenId) {
            if (!confirm('Are you sure you want to remove this plant from your garden?')) {
                return;
            }
            
            fetch('api/garden.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove&garden_id=${gardenId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.message || 'Error removing plant', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error removing plant', 'error');
            });
        }

        // Load reminder stats
        document.addEventListener('DOMContentLoaded', function() {
            fetch('api/garden-stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('activeReminders').textContent = data.active_reminders;
                        document.getElementById('todayTasks').textContent = data.today_tasks;
                    }
                })
                .catch(error => console.error('Error loading stats:', error));
        });
    </script>

    <style>
        .garden-plant-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .garden-plant-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
    </style>
</body>
</html>

<?php $db->close(); ?>

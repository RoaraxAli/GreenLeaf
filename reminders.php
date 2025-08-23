<?php
require_once 'config/config.php';

if (!is_logged_in()) {
    redirect('auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get user's reminders
$stmt = $conn->prepare("
    SELECT r.*, p.name as plant_name, p.botanical_name, p.image, ug.plant_name as custom_name
    FROM reminders r
    JOIN user_garden ug ON r.garden_id = ug.garden_id
    JOIN plants p ON ug.plant_id = p.plant_id
    WHERE r.user_id = ?
    ORDER BY r.reminder_date ASC, r.reminder_time ASC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$reminders_result = $stmt->get_result();

// Get garden plants for adding reminders
$garden_stmt = $conn->prepare("
    SELECT ug.garden_id, ug.plant_name, p.name, p.botanical_name
    FROM user_garden ug
    JOIN plants p ON ug.plant_id = p.plant_id
    WHERE ug.user_id = ?
    ORDER BY ug.date_added DESC
");
$garden_stmt->bind_param("i", $_SESSION['user_id']);
$garden_stmt->execute();
$garden_result = $garden_stmt->get_result();

$message = '';
if (isset($_GET['success'])) {
    $message = match($_GET['success']) {
        'added' => 'Reminder added successfully!',
        'completed' => 'Reminder marked as completed!',
        'deleted' => 'Reminder deleted successfully!',
        default => ''
    };
}

// Group reminders by status and date
$today_reminders = [];
$upcoming_reminders = [];
$completed_reminders = [];
$overdue_reminders = [];

$today = date('Y-m-d');
$reminders_result->data_seek(0);

while ($reminder = $reminders_result->fetch_assoc()) {
    if ($reminder['is_completed']) {
        $completed_reminders[] = $reminder;
    } elseif ($reminder['reminder_date'] < $today) {
        $overdue_reminders[] = $reminder;
    } elseif ($reminder['reminder_date'] === $today) {
        $today_reminders[] = $reminder;
    } else {
        $upcoming_reminders[] = $reminder;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Care Reminders - <?php echo SITE_NAME; ?></title>
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
                        <h1 class="display-5 fw-bold text-success mb-2">Care Reminders</h1>
                        <p class="lead text-muted">Stay on top of your plant care schedule</p>
                    </div>
                    <div>
                        <a href="my-garden.php" class="btn btn-outline-success me-2">
                            <i class="fas fa-seedling me-1"></i>My Garden
                        </a>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addReminderModal">
                            <i class="fas fa-plus me-1"></i>Add Reminder
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

        <!-- Reminder Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <h4><?php echo count($overdue_reminders); ?></h4>
                        <p class="mb-0">Overdue</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-day fa-2x mb-2"></i>
                        <h4><?php echo count($today_reminders); ?></h4>
                        <p class="mb-0">Due Today</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                        <h4><?php echo count($upcoming_reminders); ?></h4>
                        <p class="mb-0">Upcoming</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4><?php echo count($completed_reminders); ?></h4>
                        <p class="mb-0">Completed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reminder Sections -->
        <?php if (!empty($overdue_reminders)): ?>
            <div class="card shadow-sm mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Overdue Reminders</h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($overdue_reminders as $reminder): ?>
                        <?php include 'includes/reminder-item.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($today_reminders)): ?>
            <div class="card shadow-sm mb-4 border-warning">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-day me-2"></i>Due Today</h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($today_reminders as $reminder): ?>
                        <?php include 'includes/reminder-item.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($upcoming_reminders)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Reminders</h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($upcoming_reminders as $reminder): ?>
                        <?php include 'includes/reminder-item.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($overdue_reminders) && empty($today_reminders) && empty($upcoming_reminders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-bell-slash fa-4x text-muted mb-4"></i>
                <h3 class="text-muted mb-3">No active reminders</h3>
                <p class="text-muted mb-4">Add reminders to keep track of your plant care schedule.</p>
                <?php if ($garden_result->num_rows > 0): ?>
                    <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addReminderModal">
                        <i class="fas fa-plus me-2"></i>Add Your First Reminder
                    </button>
                <?php else: ?>
                    <a href="my-garden.php" class="btn btn-success btn-lg">
                        <i class="fas fa-seedling me-2"></i>Add Plants to Garden First
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Reminder Modal -->
    <div class="modal fade" id="addReminderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Care Reminder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="api/reminders.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <?php if ($garden_result->num_rows > 0): ?>
                            <div class="mb-3">
                                <label for="garden_id" class="form-label">Select Plant</label>
                                <select class="form-select" id="garden_id" name="garden_id" required>
                                    <option value="">Choose a plant...</option>
                                    <?php while ($garden_plant = $garden_result->fetch_assoc()): ?>
                                        <option value="<?php echo $garden_plant['garden_id']; ?>"
                                                <?php echo (isset($_GET['garden_id']) && $_GET['garden_id'] == $garden_plant['garden_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($garden_plant['plant_name'] ?: $garden_plant['name']); ?>
                                            <?php if ($garden_plant['botanical_name']): ?>
                                                (<?php echo htmlspecialchars($garden_plant['botanical_name']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Reminder Type</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select type...</option>
                                    <option value="watering">Watering</option>
                                    <option value="fertilizing">Fertilizing</option>
                                    <option value="pruning">Pruning</option>
                                    <option value="repotting">Repotting</option>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="reminder_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="reminder_date" name="reminder_date" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="reminder_time" class="form-label">Time</label>
                                    <input type="time" class="form-control" id="reminder_time" name="reminder_time" 
                                           value="09:00" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Custom Message (Optional)</label>
                                <textarea class="form-control" id="message" name="message" rows="2" 
                                          placeholder="Add a custom reminder message..."></textarea>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                You need to add plants to your garden first before creating reminders.
                                <a href="my-garden.php" class="alert-link">Go to My Garden</a>.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <?php if ($garden_result->num_rows > 0): ?>
                            <button type="submit" class="btn btn-success">Add Reminder</button>
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
        function completeReminder(reminderId) {
            fetch('api/reminders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=complete&reminder_id=${reminderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.message || 'Error completing reminder', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error completing reminder', 'error');
            });
        }

        function deleteReminder(reminderId) {
            if (!confirm('Are you sure you want to delete this reminder?')) {
                return;
            }
            
            fetch('api/reminders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&reminder_id=${reminderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.message || 'Error deleting reminder', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error deleting reminder', 'error');
            });
        }

        // Auto-open modal if garden_id is provided
        <?php if (isset($_GET['add']) && isset($_GET['garden_id'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('addReminderModal')).show();
            });
        <?php endif; ?>
    </script>
</body>
</html>

<?php $db->close(); ?>

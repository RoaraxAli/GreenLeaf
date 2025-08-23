<div class="border-bottom p-3">
    <div class="row align-items-center">
        <div class="col-md-2">
            <img src="<?php echo $reminder['image'] ?: DEFAULT_PLANT_IMAGE; ?>" 
                 class="img-fluid rounded" alt="<?php echo htmlspecialchars($reminder['plant_name']); ?>"
                 style="height: 60px; width: 60px; object-fit: cover;">
        </div>
        <div class="col-md-4">
            <h6 class="mb-1"><?php echo htmlspecialchars($reminder['custom_name'] ?: $reminder['plant_name']); ?></h6>
            <small class="text-muted"><?php echo htmlspecialchars($reminder['botanical_name']); ?></small>
        </div>
        <div class="col-md-2">
            <?php
            $type_icons = [
                'watering' => 'fas fa-tint text-info',
                'fertilizing' => 'fas fa-seedling text-success',
                'pruning' => 'fas fa-cut text-warning',
                'repotting' => 'fas fa-box text-secondary'
            ];
            $icon = $type_icons[$reminder['type']] ?? 'fas fa-bell';
            ?>
            <i class="<?php echo $icon; ?> me-2"></i>
            <span class="text-capitalize"><?php echo $reminder['type']; ?></span>
        </div>
        <div class="col-md-2">
            <div class="fw-bold"><?php echo date('M j, Y', strtotime($reminder['reminder_date'])); ?></div>
            <small class="text-muted"><?php echo date('g:i A', strtotime($reminder['reminder_time'])); ?></small>
        </div>
        <div class="col-md-2">
            <?php if (!$reminder['is_completed']): ?>
                <div class="btn-group">
                    <button class="btn btn-success btn-sm" onclick="completeReminder(<?php echo $reminder['reminder_id']; ?>)">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="deleteReminder(<?php echo $reminder['reminder_id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            <?php else: ?>
                <span class="badge bg-success">Completed</span>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($reminder['message']): ?>
        <div class="row mt-2">
            <div class="col-md-10 offset-md-2">
                <small class="text-muted">
                    <i class="fas fa-comment me-1"></i>
                    <?php echo htmlspecialchars($reminder['message']); ?>
                </small>
            </div>
        </div>
    <?php endif; ?>
</div>

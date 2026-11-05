<?php
/**
 * Flash Notification Alerts Component
 * Hotel Event Management System
 */

$flashMessages = get_flash();
if (!empty($flashMessages)): ?>
    <div class="flash-messages-container mb-4">
        <?php foreach ($flashMessages as $flash): 
            $alertType = in_array($flash['type'], ['success', 'danger', 'warning', 'info']) ? $flash['type'] : 'info';
            $icon = 'info-circle-fill';
            if ($alertType === 'success') $icon = 'check-circle-fill';
            elseif ($alertType === 'danger') $icon = 'exclamation-triangle-fill';
            elseif ($alertType === 'warning') $icon = 'exclamation-circle-fill';
        ?>
            <div class="alert alert-<?= $alertType ?> alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="bi bi-<?= $icon ?> fs-5 me-2 flex-shrink-0"></i>
                <div class="flex-grow-1">
                    <?= $flash['message'] ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

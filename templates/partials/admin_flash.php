<?php if (!empty($flashOk)): ?>
    <div class="admin-alert admin-alert-success" role="status"><?= e($flashOk) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="admin-alert admin-alert-error" role="alert"><?= e($flashError) ?></div>
<?php endif; ?>

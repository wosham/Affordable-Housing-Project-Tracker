<?php
$manualFlashMessages = $flashMessages ?? [];
$flashConfig = [
    'status' => ['type' => 'success', 'class' => 'alert--success', 'icon' => 'fa-circle-check', 'role' => 'status'],
    'success' => ['type' => 'success', 'class' => 'alert--success', 'icon' => 'fa-circle-check', 'role' => 'status'],
    'error' => ['type' => 'danger', 'class' => 'alert--danger', 'icon' => 'fa-circle-exclamation', 'role' => 'alert'],
    'danger' => ['type' => 'danger', 'class' => 'alert--danger', 'icon' => 'fa-circle-exclamation', 'role' => 'alert'],
    'warning' => ['type' => 'warning', 'class' => 'alert--warning', 'icon' => 'fa-triangle-exclamation', 'role' => 'alert'],
    'info' => ['type' => 'info', 'class' => 'alert--info', 'icon' => 'fa-circle-info', 'role' => 'status'],
];

$messages = [];

foreach ($flashConfig as $key => $config) {
    $value = Session::flash($key);
    if ($value === null || $value === '' || $value === []) {
        continue;
    }

    $messages[] = [
        'type' => $config['type'],
        'class' => $config['class'],
        'icon' => $config['icon'],
        'role' => $config['role'],
        'message' => $value,
    ];
}

foreach ((array)$manualFlashMessages as $message) {
    if (!is_array($message)) {
        continue;
    }

    $type = strtolower((string)($message['type'] ?? 'info'));
    $config = $flashConfig[$type] ?? $flashConfig['info'];
    $value = $message['message'] ?? null;

    if ($value === null || $value === '' || $value === []) {
        continue;
    }

    $messages[] = [
        'type' => $config['type'],
        'class' => $config['class'],
        'icon' => $config['icon'],
        'role' => $config['role'],
        'message' => $value,
    ];
}
?>
<?php if ($messages !== []): ?>
<div class="admin-flash" aria-live="polite">
<?php foreach ($messages as $item): ?>
  <div class="alert <?= Security::e($item['class']) ?>" role="<?= Security::e($item['role']) ?>" data-alert>
    <i class="fa-solid <?= Security::e($item['icon']) ?>" aria-hidden="true"></i>
    <div class="alert__content">
<?php if (is_array($item['message'])): ?>
      <ul>
<?php foreach ($item['message'] as $line): ?>
        <li><?= Security::e($line) ?></li>
<?php endforeach; ?>
      </ul>
<?php else: ?>
      <?= Security::e($item['message']) ?>
<?php endif; ?>
    </div>
    <button type="button" class="alert__close" data-alert-dismiss aria-label="Dismiss message">
      <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php

function consultant_quality_page(array $config): void
{
    $type = (string)$config['type'];
    $userId = (int)(Auth::id() ?? 0);
    $role = (string)(Auth::role() ?? '');
    $filters = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
        'review_status' => trim((string)($_GET['review_status'] ?? '')),
        'result' => trim((string)($_GET['result'] ?? '')),
        'outcome' => trim((string)($_GET['outcome'] ?? '')),
        'status' => trim((string)($_GET['status'] ?? '')),
        'severity' => trim((string)($_GET['severity'] ?? '')),
        'witness' => trim((string)($_GET['witness'] ?? '')),
        'from' => trim((string)($_GET['from'] ?? '')),
        'to' => trim((string)($_GET['to'] ?? '')),
    ];
    $page = max(1, Security::cleanInt($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $projects = ConsultantQuality::projects($userId, $role);
    $summary = ConsultantQuality::summary($type, $userId, $role, $filters);
    $items = ConsultantQuality::items($type, $userId, $role, $filters, $limit, $offset);
    $total = ConsultantQuality::count($type, $userId, $role, $filters);
    $pages = max(1, (int)ceil($total / $limit));
    $priorityItems = ConsultantQuality::priorityItems($type, $userId, $role, $filters, 8);
    $baseUrl = (string)$config['url'];
    ?>

    <section class="quality-hero card">
      <div>
        <span class="sa-panel-label"><i class="fa-solid <?= Security::e($config['icon']) ?>" aria-hidden="true"></i> <?= Security::e($config['eyebrow']) ?></span>
        <h2><?= Security::e($config['heading']) ?></h2>
        <p><?= Security::e($config['description']) ?></p>
      </div>
      <div class="quality-actions">
        <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/consultant/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
        <a class="btn btn--outline" href="<?= Security::e(Url::to($config['peer_link'])) ?>"><i class="fa-solid <?= Security::e($config['peer_icon']) ?>"></i> <?= Security::e($config['peer_label']) ?></a>
      </div>
    </section>

    <section class="quality-stats" aria-label="<?= Security::e($config['heading']) ?> summary">
      <?php foreach ($config['stats'] as $stat): ?>
        <?php
          $statFilter = $stat['filter'] ?? [];
          $statPath = consultant_quality_filter_url($baseUrl, $statFilter);
          consultant_quality_stat($stat['icon'], $summary[$stat['key']] ?? 0, $stat['label'], $stat['hint'], $statPath);
        ?>
      <?php endforeach; ?>
    </section>

    <section class="quality-layout">
      <article class="quality-card card">
        <div class="quality-card__head">
          <div>
            <h2><?= Security::e($config['register_title']) ?></h2>
            <p><?= Security::e($config['register_hint']) ?></p>
          </div>
          <span class="badge badge--info"><?= format_number($total) ?> records</span>
        </div>

        <?php consultant_quality_filters($type, $filters, $projects, $baseUrl); ?>

        <div class="table-wrap">
          <table class="data-table quality-table">
            <?php consultant_quality_table($type, $items); ?>
          </table>
        </div>
        <?php consultant_quality_pagination($page, $pages, $total, $limit); ?>
      </article>

      <aside class="quality-card card">
        <h2><?= Security::e($config['side_title']) ?></h2>
        <p><?= Security::e($config['side_hint']) ?> Portfolio-wide (not limited to this page).</p>
        <div class="quality-side-list">
          <?php if ($priorityItems === []): ?>
            <div class="empty-state empty-state--compact">
              <strong class="empty-state__title">No priority records</strong>
              <span class="empty-state__text">Pending or flagged items will appear here.</span>
            </div>
          <?php else: foreach ($priorityItems as $item): ?>
            <?php consultant_quality_side_item($type, $item, $baseUrl); ?>
          <?php endforeach; endif; ?>
        </div>
        <a class="btn btn--outline btn--sm" href="<?= Security::e(consultant_quality_filter_url($baseUrl, ['review_status' => 'pending'])) ?>">View pending reviews</a>
      </aside>
    </section>

    <?php consultant_quality_modal(); ?>
    <?php
}

function consultant_quality_filter_url(string $baseUrl, array $extra): string
{
    $query = array_filter(array_merge($_GET, $extra), static fn ($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    unset($query['page']);
    return Url::to($baseUrl . ($query ? '?' . http_build_query($query) : ''));
}

function consultant_quality_filters(string $type, array $filters, array $projects, string $url): void
{
    ?>
    <form class="quality-filter-grid" method="get" action="<?= Security::e(Url::to($url)) ?>">
      <label>Search <input type="search" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Project, record or location..."></label>
      <label>Project
        <select name="project_id">
          <option value="0">All assigned projects</option>
          <?php foreach ($projects as $project): ?>
            <option value="<?= (int)$project['id'] ?>" <?= (int)$filters['project_id'] === (int)$project['id'] ? 'selected' : '' ?>><?= Security::e($project['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if ($type === 'quality_test'): ?>
        <label>Result <select name="result"><option value="">All results</option><?php foreach (['pass', 'fail', 'pending'] as $value): ?><option value="<?= $value ?>" <?= $filters['result'] === $value ? 'selected' : '' ?>><?= Security::e(status_label($value)) ?></option><?php endforeach; ?></select></label>
      <?php elseif ($type === 'inspection'): ?>
        <label>Witness <select name="witness"><option value="">Any</option><option value="1" <?= $filters['witness'] === '1' ? 'selected' : '' ?>>Required</option><option value="0" <?= $filters['witness'] === '0' ? 'selected' : '' ?>>Not required</option></select></label>
      <?php else: ?>
        <label>Status <select name="status"><option value="">All statuses</option><?php foreach (ConsultantQuality::ISSUE_STATUSES as $value): ?><option value="<?= $value ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= Security::e(status_label($value)) ?></option><?php endforeach; ?></select></label>
        <label>Severity <select name="severity"><option value="">Any</option><?php foreach (ConsultantQuality::SEVERITIES as $value): ?><option value="<?= $value ?>" <?= $filters['severity'] === $value ? 'selected' : '' ?>><?= Security::e(status_label($value)) ?></option><?php endforeach; ?></select></label>
      <?php endif; ?>
      <label>Review <select name="review_status"><option value="">All reviews</option><?php foreach (ConsultantQuality::REVIEW_STATUSES as $value): ?><option value="<?= $value ?>" <?= $filters['review_status'] === $value ? 'selected' : '' ?>><?= Security::e(status_label($value)) ?></option><?php endforeach; ?></select></label>
      <label>From <input type="date" name="from" value="<?= Security::e($filters['from']) ?>"></label>
      <label>To <input type="date" name="to" value="<?= Security::e($filters['to']) ?>"></label>
      <button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
      <a class="btn btn--outline" href="<?= Security::e(Url::to($url)) ?>">Reset</a>
    </form>
    <?php
}

function consultant_quality_table(string $type, array $items): void
{
    $heads = match ($type) {
        'quality_test' => ['Test', 'Project', 'Location', 'Result', 'Review', 'Actions'],
        'inspection' => ['Activity', 'Project', 'Inspection', 'Outcome', 'Review', 'Actions'],
        'ncr' => ['NCR', 'Project', 'Severity', 'Corrective Action', 'Review', 'Actions'],
        'defect' => ['Defect', 'Project', 'Severity', 'Due Date', 'Review', 'Actions'],
    };
    echo '<thead><tr>';
    foreach ($heads as $head) {
        echo '<th>' . Security::e($head) . '</th>';
    }
    echo '</tr></thead><tbody>';
    if ($items === []) {
        echo '<tr><td colspan="' . count($heads) . '"><div class="empty-state"><strong class="empty-state__title">No records found</strong><span class="empty-state__text">Adjust filters or wait for site records on your assigned projects.</span></div></td></tr>';
    }
    foreach ($items as $item) {
        consultant_quality_row($type, $item);
    }
    echo '</tbody>';
}

function consultant_quality_row(string $type, array $item): void
{
    $review = (string)($item['consultant_review_status'] ?? 'pending');
    echo '<tr>';
    if ($type === 'quality_test') {
        echo '<td><span class="quality-primary">' . Security::e($item['test_type']) . '</span><span class="quality-secondary">' . Security::e($item['lab_ref'] ?: format_date($item['test_date'])) . '</span></td>';
        echo '<td><span class="quality-primary">' . Security::e($item['project_name']) . '</span><span class="quality-secondary">' . Security::e($item['actor_name'] ?: 'Project team') . '</span></td>';
        echo '<td>' . Security::e($item['location_on_site'] ?: '-') . '</td>';
        echo '<td><span class="badge ' . Security::e(ConsultantQuality::statusClass((string)$item['pass_fail'])) . '">' . Security::e(status_label($item['pass_fail'])) . '</span><span class="quality-secondary">' . Security::e(safe_truncate((string)($item['result'] ?? ''), 70)) . '</span></td>';
    } elseif ($type === 'inspection') {
        echo '<td><span class="quality-primary">' . Security::e($item['activity']) . '</span><span class="quality-secondary">' . Security::e($item['hold_point'] ?: 'Inspection record') . '</span></td>';
        echo '<td><span class="quality-primary">' . Security::e($item['project_name']) . '</span><span class="quality-secondary">' . Security::e($item['actor_name'] ?: 'Project team') . '</span></td>';
        echo '<td>' . Security::e(format_date($item['inspection_date'])) . '<span class="quality-secondary">' . ((int)$item['witness_required'] === 1 ? 'Witness required' : 'No witness requirement') . '</span></td>';
        echo '<td><span class="badge ' . Security::e(ConsultantQuality::statusClass((string)($item['outcome'] ?: 'pending'))) . '">' . Security::e(status_label($item['outcome'] ?: 'pending')) . '</span></td>';
    } elseif ($type === 'ncr') {
        echo '<td><span class="quality-primary">NCR #' . (int)$item['id'] . '</span><span class="quality-secondary">' . Security::e(safe_truncate($item['description'], 90)) . '</span></td>';
        echo '<td><span class="quality-primary">' . Security::e($item['project_name']) . '</span><span class="quality-secondary">' . Security::e(format_date($item['raised_date'])) . '</span></td>';
        echo '<td><span class="badge ' . Security::e(ConsultantQuality::statusClass((string)$item['severity'])) . '">' . Security::e(status_label($item['severity'])) . '</span><span class="quality-secondary">' . Security::e(status_label($item['status'])) . '</span></td>';
        echo '<td>' . Security::e(safe_truncate((string)($item['corrective_action'] ?? ''), 90)) . '</td>';
    } else {
        echo '<td><span class="quality-primary">' . Security::e($item['location'] ?: 'Site defect') . '</span><span class="quality-secondary">' . Security::e(safe_truncate($item['description'], 90)) . '</span></td>';
        echo '<td><span class="quality-primary">' . Security::e($item['project_name']) . '</span><span class="quality-secondary">' . Security::e($item['assigned_name'] ?: 'Unassigned') . '</span></td>';
        echo '<td><span class="badge ' . Security::e(ConsultantQuality::statusClass((string)$item['severity'])) . '">' . Security::e(status_label($item['severity'])) . '</span><span class="quality-secondary">' . Security::e(status_label($item['status'])) . '</span></td>';
        echo '<td>' . Security::e(format_date($item['due_date'])) . '</td>';
    }
    echo '<td><span class="badge ' . Security::e(ConsultantQuality::statusClass($review)) . '">' . Security::e(status_label($review)) . '</span><span class="quality-secondary">' . Security::e($item['reviewer_name'] ?: '-') . '</span></td>';
    echo '<td><div class="quality-row-actions">';
    consultant_quality_action($type, (int)$item['id'], 'review', 'Review', 'fa-check', consultant_quality_item_label($type, $item));
    consultant_quality_action($type, (int)$item['id'], 'return', 'Return', 'fa-rotate-left', consultant_quality_item_label($type, $item));
    consultant_quality_action($type, (int)$item['id'], 'flag', 'Flag', 'fa-flag', consultant_quality_item_label($type, $item));
    consultant_quality_action($type, (int)$item['id'], 'close', 'Close', 'fa-circle-check', consultant_quality_item_label($type, $item));
    echo '</div></td></tr>';
}

function consultant_quality_item_label(string $type, array $item): string
{
    return match ($type) {
        'quality_test' => (string)$item['test_type'] . ' - ' . (string)$item['project_name'],
        'inspection' => (string)$item['activity'] . ' - ' . (string)$item['project_name'],
        'ncr' => 'NCR #' . (int)$item['id'] . ' - ' . (string)$item['project_name'],
        'defect' => 'Defect #' . (int)$item['id'] . ' - ' . (string)$item['project_name'],
    };
}

function consultant_quality_action(string $type, int $id, string $action, string $label, string $icon, string $item): void
{
    echo '<button class="btn btn--sm btn--outline" type="button" aria-label="' . Security::e($label) . '" title="' . Security::e($label) . '" data-quality-action data-type="' . Security::e($type) . '" data-id="' . $id . '" data-action="' . Security::e($action) . '" data-title="' . Security::e($label . ' record') . '" data-item="' . Security::e($item) . '"><i class="fa-solid ' . Security::e($icon) . '"></i><span class="sr-only">' . Security::e($label) . '</span></button>';
}

function consultant_quality_side_item(string $type, array $item, string $baseUrl): void
{
    $q = match ($type) {
        'quality_test' => (string)($item['test_type'] ?? ''),
        'inspection' => (string)($item['activity'] ?? ''),
        'ncr' => 'NCR',
        'defect' => (string)($item['location'] ?? 'Defect'),
        default => '',
    };
    $href = consultant_quality_filter_url($baseUrl, [
        'project_id' => (int)($item['project_id'] ?? 0),
        'q' => $q,
        'review_status' => (string)($item['consultant_review_status'] ?? 'pending'),
    ]);
    echo '<a class="quality-side-item quality-side-item--link" href="' . Security::e($href) . '"><strong>' . Security::e(consultant_quality_item_label($type, $item)) . '</strong><span>' . Security::e(status_label((string)($item['consultant_review_status'] ?? 'pending'))) . '</span></a>';
}

function consultant_quality_stat(string $icon, mixed $value, string $label, string $hint, string $path = ''): void
{
    $tag = $path !== '' ? 'a' : 'article';
    $href = $path !== '' ? ' href="' . Security::e($path) . '"' : '';
    echo '<' . $tag . ' class="quality-stat card"' . $href . '><span class="quality-stat__icon"><i class="fa-solid ' . Security::e($icon) . '"></i></span><div><strong>' . Security::e(is_numeric($value) ? format_number($value) : (string)$value) . '</strong><span>' . Security::e($label) . '</span><small>' . Security::e($hint) . '</small></div></' . $tag . '>';
}

function consultant_quality_pagination(int $page, int $pages, int $total, int $limit): void
{
    if ($total <= 0) {
        return;
    }
    $from = min($total, (($page - 1) * $limit) + 1);
    $to = min($total, $page * $limit);
    $query = $_GET;
    echo '<div class="pagination"><span>Showing ' . format_number($from) . '-' . format_number($to) . ' of ' . format_number($total) . '</span><div>';
    $query['page'] = max(1, $page - 1);
    $prevDis = $page <= 1 ? ' is-disabled' : '';
    echo '<a class="btn btn--sm btn--outline' . $prevDis . '" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-left"></i></a>';
    echo '<span class="btn btn--sm btn--primary">' . $page . ' / ' . $pages . '</span>';
    $query['page'] = min($pages, $page + 1);
    $nextDis = $page >= $pages ? ' is-disabled' : '';
    echo '<a class="btn btn--sm btn--outline' . $nextDis . '" href="?' . Security::e(http_build_query($query)) . '"><i class="fa-solid fa-chevron-right"></i></a></div></div>';
}

function consultant_quality_modal(): void
{
    ?>
    <div class="quality-modal" data-quality-modal hidden>
      <div class="quality-modal__panel">
        <div class="quality-modal__head">
          <div>
            <strong data-modal-title>Review quality record</strong>
            <span class="quality-secondary" data-modal-item></span>
          </div>
          <button class="btn btn--sm btn--ghost" type="button" data-modal-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form>
          <input type="hidden" name="type"><input type="hidden" name="id"><input type="hidden" name="action">
          <div class="quality-modal__body">
            <label>Severity
              <select name="severity">
                <?php foreach (ConsultantQuality::SEVERITIES as $severity): ?>
                  <option value="<?= Security::e($severity) ?>"><?= Security::e(status_label($severity)) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label class="quality-check"><span><input type="checkbox" name="documents_checked" value="1"> Supporting documents checked</span></label>
            <label class="span-2">Review note <textarea name="note" rows="5" placeholder="Add a clear note for the project team."></textarea></label>
          </div>
          <div class="quality-modal__foot">
            <button class="btn btn--outline" type="button" data-modal-close>Cancel</button>
            <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save review</button>
          </div>
        </form>
      </div>
    </div>
    <?php
}

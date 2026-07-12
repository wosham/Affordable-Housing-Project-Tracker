<?php

declare(strict_types=1);

if (!function_exists('mcc_default_project')) {
    function mcc_default_project(array $projects, string $countKey): int
    {
        foreach ($projects as $project) {
            if ((int)($project[$countKey] ?? 0) > 0) {
                return (int)$project['id'];
            }
        }

        return $projects !== [] ? (int)$projects[0]['id'] : 0;
    }
}

if (!function_exists('mcc_stat')) {
    function mcc_stat(string $icon, mixed $value, string $label, string $trend, ?string $href = null): void
    {
        $tag = $href ? 'a' : 'article';
        $attr = $href ? ' href="' . Security::e($href) . '"' : '';
        ?>
  <<?= $tag ?> class="stat-widget<?= $href ? ' stat-widget--link' : '' ?>"<?= $attr ?>>
    <span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
      <strong class="stat-widget__value"><?= Security::e(is_numeric($value) ? format_number((float)$value) : (string)$value) ?></strong>
      <span class="stat-widget__label"><?= Security::e($label) ?></span>
      <small class="stat-widget__trend"><?= Security::e($trend) ?></small>
    </span>
  </<?= $tag ?>>
        <?php
    }
}

if (!function_exists('mcc_project_strip')) {
    function mcc_project_strip(array $projects, array $filters, string $countKey, string $label, string $path): void
    {
        ?>
<section class="mcc-projects" aria-label="Assigned projects">
<?php if ($projects === []): ?>
  <article class="card mcc-project is-empty"><strong>No assigned projects</strong><span>Contract controls appear once projects are allocated to you.</span></article>
<?php else: foreach ($projects as $project):
    $query = array_filter(
        array_merge($filters, ['project_id' => (int)$project['id'], 'page' => 1]),
        static fn ($v): bool => $v !== '' && $v !== null && $v !== 0
    );
    ?>
  <a class="card mcc-project<?= (int)($filters['project_id'] ?? 0) === (int)$project['id'] ? ' is-active' : '' ?>" href="<?= Security::e(Url::to($path . '?' . http_build_query($query))) ?>">
    <span>
      <strong><?= Security::e($project['name']) ?></strong>
      <small><?= Security::e($project['constituency_name'] ?: status_label($project['status'] ?? 'active')) ?></small>
    </span>
    <em><?= Security::e(format_number($project[$countKey] ?? 0)) ?> <?= Security::e($label) ?></em>
  </a>
<?php endforeach; endif; ?>
</section>
        <?php
    }
}

if (!function_exists('mcc_pagination')) {
    function mcc_pagination(int $total, int $offset, int $count, int $page, int $totalPages, array $filters, string $path, int $perPage = 15): void
    {
        $from = $total > 0 ? $offset + 1 : 0;
        $to = min($offset + $count, $total);
        $prev = array_filter(array_merge($filters, ['page' => max(1, $page - 1)]), static fn ($v): bool => $v !== '' && $v !== null && $v !== 0);
        $next = array_filter(array_merge($filters, ['page' => min($totalPages, $page + 1)]), static fn ($v): bool => $v !== '' && $v !== null && $v !== 0);
        ?>
<nav class="pagination" aria-label="Pagination">
  <p class="pagination__info">Showing <?= Security::e(format_number($from)) ?>-<?= Security::e(format_number($to)) ?> of <?= Security::e(format_number($total)) ?> records (<?= (int)$perPage ?> per page)</p>
  <div class="pagination__links">
    <a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e(Url::to($path . '?' . http_build_query($prev))) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
    <span class="pagination__link is-active"><?= Security::e(format_number($page)) ?> / <?= Security::e(format_number(max(1, $totalPages))) ?></span>
    <a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e(Url::to($path . '?' . http_build_query($next))) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
  </div>
</nav>
        <?php
    }
}

if (!function_exists('mcc_data_attrs')) {
    /** @param array<string, mixed> $map */
    function mcc_data_attrs(array $map): string
    {
        $html = '';
        foreach ($map as $key => $value) {
            if ($value === null) {
                $value = '';
            }
            $attr = 'data-' . str_replace('_', '-', (string)$key);
            $html .= ' ' . $attr . '="' . Security::e((string)$value) . '"';
        }

        return $html;
    }
}

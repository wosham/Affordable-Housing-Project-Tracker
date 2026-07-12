<?php

declare(strict_types=1);

if (!function_exists('msr_stat')) {
    function msr_stat(string $icon, mixed $value, string $label, string $trend, ?string $href = null): void
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

if (!function_exists('msr_pagination')) {
    function msr_pagination(int $showingFrom, int $showingTo, int $total, int $page, int $totalPages, string $prevUrl, string $nextUrl, int $perPage = 15, string $label = 'records'): void
    {
        ?>
  <nav class="pagination" aria-label="Pagination">
    <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($total)) ?> <?= Security::e($label) ?> (<?= (int)$perPage ?> per page)</p>
    <div class="pagination__links">
      <a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= Security::e($prevUrl) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
      <span class="pagination__link is-active"><?= Security::e(format_number($page)) ?> / <?= Security::e(format_number(max(1, $totalPages))) ?></span>
      <a class="pagination__link<?= $page >= $totalPages ? ' is-disabled' : '' ?>" href="<?= Security::e($nextUrl) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </nav>
        <?php
    }
}

if (!function_exists('msr_data_attrs')) {
    /** @param array<string, mixed> $map */
    function msr_data_attrs(array $map): string
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

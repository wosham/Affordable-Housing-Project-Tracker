<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$csrfForm = 'superadmin_faq';

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/faq.php'));
    }

    $action = Security::cleanString((string)($_POST['action'] ?? ''));

    try {
        match ($action) {
            'save_category' => faq_admin_save_category(),
            'delete_category' => faq_admin_delete_category(),
            'save_item' => faq_admin_save_item(),
            'delete_item' => faq_admin_delete_item(),
            default => Session::flash('error', 'Unknown FAQ action.'),
        };
    } catch (Throwable $e) {
        Session::flash('error', 'FAQ update failed: ' . $e->getMessage());
    }

    Response::redirect(Url::to('admin/superadmin/faq.php' . faq_admin_redirect_query()));
}

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'category_id' => Security::cleanInt($_GET['category_id'] ?? 0),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'popular' => Security::cleanString((string)($_GET['popular'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => (string)$value !== '' && (string)$value !== '0');
if (isset($filters['status']) && !in_array($filters['status'], ['published', 'draft'], true)) {
    unset($filters['status']);
}

$perPage = 10;
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$totalItems = FAQItem::countFiltered($filters);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$items = FAQItem::filtered($filters, $perPage, $offset);
$categories = FAQCategory::ordered();
$stats = FAQItem::stats();
$publishedCategories = array_reduce($categories, static fn (int $carry, array $category): int => $carry + (($category['status'] ?? '') === 'published' ? 1 : 0), 0);
$popularCount = (int)($stats['popular'] ?? 0);
$showingFrom = $totalItems > 0 ? $offset + 1 : 0;
$showingTo = min($offset + count($items), $totalItems);

$editCategory = null;
if (Security::cleanInt($_GET['edit_category'] ?? 0) > 0) {
    $editCategory = FAQCategory::find(Security::cleanInt($_GET['edit_category']));
}

$editItem = null;
if (Security::cleanInt($_GET['edit_item'] ?? 0) > 0) {
    $editItem = FAQItem::find(Security::cleanInt($_GET['edit_item']));
}

$pageTitle = 'FAQ Manager';
$pageDescription = 'Manage FAQ categories, rich answers, popular questions and publishing state.';
$adminRole = 'superadmin';
$contentClass = 'sa-faq-admin-page';
$componentCss = ['faq-admin'];
$pageScripts = ['faq-admin'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'County Director', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'FAQ Manager'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
?>

<section class="card sa-faq-hero">
  <div>
    <span class="sa-panel-label"><i class="fa-solid fa-circle-question" aria-hidden="true"></i> FAQ Knowledge Centre</span>
    <h2>Questions, categories and answers</h2>
    <p>Maintain the public FAQ library with clean categories, popular question chips, rich answers and search-friendly keywords.</p>
  </div>
  <div class="sa-action-grid">
    <a class="btn btn--primary" href="#faq-item-form"><i class="fa-solid fa-plus" aria-hidden="true"></i> New Question</a>
    <a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/cms-page-editor.php?slug=faq')) ?>"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Edit Page Wrapper</a>
  </div>
</section>

<section class="stat-grid stat-grid--4" aria-label="FAQ summary">
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--info"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stats['total'] ?? 0)) ?></strong><span class="stat-widget__label">Total Questions</span><small class="stat-widget__trend">Across every category</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--success"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($publishedCategories)) ?></strong><span class="stat-widget__label">Live Categories</span><small class="stat-widget__trend"><?= Security::e(format_number(count($categories))) ?> configured</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--warning"><i class="fa-solid fa-fire" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($popularCount)) ?></strong><span class="stat-widget__label">Popular Questions</span><small class="stat-widget__trend">Shown as quick links</small></span></article>
  <article class="stat-widget"><span class="stat-widget__icon stat-widget__icon--primary"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i></span><span class="stat-widget__body"><strong class="stat-widget__value"><?= Security::e(format_number($stats['published'] ?? 0)) ?></strong><span class="stat-widget__label">Published Answers</span><small class="stat-widget__trend"><?= Security::e(format_number($stats['draft'] ?? 0)) ?> draft</small></span></article>
</section>

<div class="sa-faq-layout">
  <aside class="sa-faq-side">
    <section class="card sa-faq-panel">
      <div class="card__header">
        <div>
          <h2 class="card__title"><?= $editCategory ? 'Edit Category' : 'New Category' ?></h2>
          <p class="card__subtitle">Categories create the tab strip and group headings.</p>
        </div>
      </div>
      <form method="post" action="<?= Security::e(Url::to('admin/superadmin/faq.php')) ?>" class="sa-faq-form">
        <?= Csrf::field($csrfForm) ?>
        <input type="hidden" name="action" value="save_category">
        <input type="hidden" name="id" value="<?= Security::e((string)($editCategory['id'] ?? 0)) ?>">
        <label class="form-field"><span class="form-label">Category name</span><input class="form-input" name="name" value="<?= Security::e($editCategory['name'] ?? '') ?>" required></label>
        <label class="form-field"><span class="form-label">Icon</span><input class="form-input" name="icon" value="<?= Security::e($editCategory['icon'] ?? 'fa-circle-question') ?>" placeholder="fa-user-check"></label>
        <label class="form-field"><span class="form-label">Description</span><textarea class="form-textarea" name="description" rows="3"><?= Security::e($editCategory['description'] ?? '') ?></textarea></label>
        <div class="form-grid form-grid--2">
          <label class="form-field"><span class="form-label">Sort order</span><input class="form-input" type="number" min="0" name="sort_order" value="<?= Security::e((string)($editCategory['sort_order'] ?? 0)) ?>"></label>
          <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status"><option value="published" <?= (($editCategory['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>Published</option><option value="draft" <?= (($editCategory['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option></select></label>
        </div>
        <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Category</button>
      </form>
    </section>

    <section class="card sa-faq-panel">
      <div class="card__header">
        <div>
          <h2 class="card__title">Category Order</h2>
          <p class="card__subtitle">Published categories appear in this order.</p>
        </div>
      </div>
      <div class="sa-faq-category-list">
<?php foreach ($categories as $category): ?>
        <article>
          <span><i class="fa-solid <?= Security::e($category['icon'] ?: 'fa-circle-question') ?>" aria-hidden="true"></i></span>
          <div><strong><?= Security::e($category['name']) ?></strong><small><?= Security::e(format_number($category['published_count'] ?? 0)) ?> live questions</small></div>
          <div class="sa-faq-category-actions">
            <a class="btn btn--icon btn--outline" href="<?= Security::e(Url::to('admin/superadmin/faq.php?edit_category=' . (int)$category['id'])) ?>" aria-label="Edit category"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
            <form method="post" action="<?= Security::e(Url::to('admin/superadmin/faq.php')) ?>" data-faq-delete-category>
              <?= Csrf::field($csrfForm) ?>
              <input type="hidden" name="action" value="delete_category">
              <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
              <button class="btn btn--icon btn--outline" type="submit" aria-label="Delete category"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </form>
          </div>
        </article>
<?php endforeach; ?>
      </div>
    </section>
  </aside>

  <main class="sa-faq-main">
    <section class="card sa-faq-panel" id="faq-item-form">
      <div class="card__header">
        <div>
          <h2 class="card__title"><?= $editItem ? 'Edit Question' : 'Create Question' ?></h2>
          <p class="card__subtitle">Use rich answers for lists, links and explanatory guidance. Published items appear in the FAQ library.</p>
        </div>
        <?php if ($editItem): ?><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/faq.php#faq-item-form')) ?>">New Question</a><?php endif; ?>
      </div>
      <form method="post" action="<?= Security::e(Url::to('admin/superadmin/faq.php')) ?>" class="sa-faq-item-form">
        <?= Csrf::field($csrfForm) ?>
        <input type="hidden" name="action" value="save_item">
        <input type="hidden" name="id" value="<?= Security::e((string)($editItem['id'] ?? 0)) ?>">
        <div class="form-grid form-grid--2">
          <label class="form-field form-field--full"><span class="form-label">Question</span><input class="form-input" name="question" value="<?= Security::e($editItem['question'] ?? '') ?>" required></label>
          <label class="form-field"><span class="form-label">Category</span><select class="form-select" name="category_id" required><option value="">Choose category</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= (int)($editItem['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>><?= Security::e($category['name']) ?></option><?php endforeach; ?></select></label>
          <label class="form-field"><span class="form-label">Status</span><select class="form-select" name="status"><option value="published" <?= (($editItem['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>Published</option><option value="draft" <?= (($editItem['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option></select></label>
          <label class="form-field"><span class="form-label">Sort order</span><input class="form-input" type="number" min="0" name="sort_order" value="<?= Security::e((string)($editItem['sort_order'] ?? 0)) ?>"></label>
          <label class="form-field sa-faq-check"><input type="checkbox" name="is_popular" value="1" <?= (int)($editItem['is_popular'] ?? 0) === 1 ? 'checked' : '' ?>> <span>Show in Popular Questions</span></label>
          <label class="form-field form-field--full"><span class="form-label">Search keywords</span><input class="form-input" name="search_keywords" value="<?= Security::e($editItem['search_keywords'] ?? '') ?>" placeholder="eligibility, application, levy, allocation"></label>
          <div class="form-field form-field--full">
            <span class="form-label">Answer</span>
            <div class="sa-faq-quill" data-faq-quill><?= faq_admin_clean_answer((string)($editItem['answer'] ?? '')) ?></div>
            <textarea class="form-textarea sa-faq-answer-fallback" name="answer" rows="8" required><?= Security::e($editItem['answer'] ?? '') ?></textarea>
          </div>
        </div>
        <div class="sa-cms-form-actions">
          <span>Answers support headings, lists, bold text and links.</span>
          <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Question</button>
        </div>
      </form>
    </section>

    <section class="card sa-faq-panel">
      <div class="card__header">
        <div>
          <h2 class="card__title">Question Library</h2>
          <p class="card__subtitle">Search, filter and maintain the live FAQ accordion content.</p>
        </div>
        <span class="badge badge--lime"><?= Security::e(format_number($totalItems)) ?> records</span>
      </div>

      <form class="filter-bar sa-faq-filter" method="get" action="<?= Security::e(Url::to('admin/superadmin/faq.php')) ?>">
        <div class="filter-group"><label class="filter-label" for="q">Search</label><input class="form-input" type="search" id="q" name="q" value="<?= Security::e($filters['q'] ?? '') ?>" placeholder="Question, answer, keyword..."></div>
        <div class="filter-group"><label class="filter-label" for="category_id">Category</label><select class="form-select" id="category_id" name="category_id"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= (int)($filters['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>><?= Security::e($category['name']) ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label class="filter-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><option value="published" <?= (($filters['status'] ?? '') === 'published') ? 'selected' : '' ?>>Published</option><option value="draft" <?= (($filters['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option></select></div>
        <label class="sa-faq-filter-check"><input type="checkbox" name="popular" value="1" <?= (($filters['popular'] ?? '') === '1') ? 'checked' : '' ?>> Popular only</label>
        <div class="filter-actions"><button class="btn btn--primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button><a class="btn btn--outline" href="<?= Security::e(Url::to('admin/superadmin/faq.php')) ?>">Reset</a></div>
      </form>

      <div class="table-wrap">
        <table class="data-table sa-faq-table">
          <thead><tr><th class="sa-table-number">#</th><th>Question</th><th>Category</th><th>Status</th><th>Popular</th><th>Updated</th><th>Actions</th></tr></thead>
          <tbody>
<?php if ($items === []): ?>
            <tr><td colspan="7"><div class="empty-state"><span class="empty-state__icon"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span><strong class="empty-state__title">No FAQ questions match this view</strong><span class="empty-state__text">Create a question or adjust your filters.</span></div></td></tr>
<?php endif; ?>
<?php foreach ($items as $index => $item): ?>
            <tr>
              <td class="sa-table-number"><?= Security::e(format_number($offset + $index + 1)) ?></td>
              <td><strong><?= Security::e($item['question']) ?></strong><small><?= Security::e(faq_admin_excerpt((string)$item['answer'])) ?></small></td>
              <td><span class="sa-faq-category-pill"><i class="fa-solid <?= Security::e($item['category_icon'] ?: 'fa-circle-question') ?>" aria-hidden="true"></i> <?= Security::e($item['category_name'] ?: 'Uncategorised') ?></span></td>
              <td><span class="badge <?= Security::e(status_badge_class($item['status'] ?? 'draft')) ?>"><?= Security::e(status_label($item['status'] ?? 'draft')) ?></span></td>
              <td><?= (int)($item['is_popular'] ?? 0) === 1 ? '<span class="badge badge--warning">Popular</span>' : '<span class="badge badge--muted">Standard</span>' ?></td>
              <td><?= Security::e(time_ago($item['updated_at'] ?? null)) ?></td>
              <td>
                <div class="data-table__actions">
                  <a class="btn btn--icon btn--primary" href="<?= Security::e(Url::to('admin/superadmin/faq.php?edit_item=' . (int)$item['id'] . '#faq-item-form')) ?>" aria-label="Edit question"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></a>
                  <form method="post" action="<?= Security::e(Url::to('admin/superadmin/faq.php')) ?>" data-confirm="Delete this FAQ question?">
                    <?= Csrf::field($csrfForm) ?><input type="hidden" name="action" value="delete_item"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <button class="btn btn--icon btn--danger" type="submit" aria-label="Delete question"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                  </form>
                </div>
              </td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <nav class="pagination" aria-label="FAQ pagination">
        <p class="pagination__info">Showing <?= Security::e(format_number($showingFrom)) ?>-<?= Security::e(format_number($showingTo)) ?> of <?= Security::e(format_number($totalItems)) ?> questions</p>
        <div class="pagination__list">
          <a class="pagination__link <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= Security::e(faq_admin_page_url(max(1, $page - 1), $filters)) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <a class="pagination__link <?= $i === $page ? 'is-active' : '' ?>" href="<?= Security::e(faq_admin_page_url($i, $filters)) ?>"><?= Security::e((string)$i) ?></a>
<?php endfor; ?>
          <a class="pagination__link <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= Security::e(faq_admin_page_url(min($totalPages, $page + 1), $filters)) ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
        </div>
      </nav>
    </section>
  </main>
</div>

<?php include __DIR__ . '/../../app/partials/admin/shell-end.php'; ?>

<?php
function faq_admin_save_category(): void
{
    $id = Security::cleanInt($_POST['id'] ?? 0);
    $name = Security::cleanString((string)($_POST['name'] ?? ''));
    if ($name === '') {
        Session::flash('error', 'Category name is required.');
        return;
    }

    $slug = faq_admin_slug($name);
    $data = [
        'name' => $name,
        'slug' => $slug,
        'icon' => faq_admin_icon((string)($_POST['icon'] ?? 'fa-circle-question')),
        'description' => Security::cleanString((string)($_POST['description'] ?? '')),
        'sort_order' => max(0, Security::cleanInt($_POST['sort_order'] ?? 0)),
        'status' => (($_POST['status'] ?? '') === 'draft') ? 'draft' : 'published',
    ];

    if ($id > 0) {
        FAQCategory::update($id, $data);
        Logger::log('update', 'faq_categories', $id, ['name' => $name]);
    } else {
        $id = FAQCategory::create($data);
        Logger::log('create', 'faq_categories', (int)$id, ['name' => $name]);
    }

    Session::flash('status', 'FAQ category saved.');
}

function faq_admin_delete_category(): void
{
    $id = Security::cleanInt($_POST['id'] ?? 0);
    if ($id <= 0) {
        return;
    }

    if (FAQItem::count(['category_id' => $id]) > 0) {
        Session::flash('error', 'Move or delete questions in this category before deleting it.');
        return;
    }

    FAQCategory::delete($id);
    Logger::log('delete', 'faq_categories', $id);
    Session::flash('status', 'FAQ category deleted.');
}

function faq_admin_save_item(): void
{
    $id = Security::cleanInt($_POST['id'] ?? 0);
    $question = Security::cleanString((string)($_POST['question'] ?? ''));
    $answer = faq_admin_clean_answer((string)($_POST['answer'] ?? ''));

    if ($question === '' || $answer === '') {
        Session::flash('error', 'Question and answer are required.');
        return;
    }

    $data = [
        'category_id' => Security::cleanInt($_POST['category_id'] ?? 0),
        'slug' => faq_admin_slug($question),
        'question' => $question,
        'answer' => $answer,
        'category' => faq_admin_category_name(Security::cleanInt($_POST['category_id'] ?? 0)),
        'sort_order' => max(0, Security::cleanInt($_POST['sort_order'] ?? 0)),
        'is_popular' => isset($_POST['is_popular']) ? 1 : 0,
        'status' => (($_POST['status'] ?? '') === 'draft') ? 'draft' : 'published',
        'is_visible' => (($_POST['status'] ?? '') === 'draft') ? 0 : 1,
        'search_keywords' => Security::cleanString((string)($_POST['search_keywords'] ?? '')),
        'updated_by' => (int)Auth::id(),
    ];

    if ($id > 0) {
        FAQItem::update($id, $data);
        Logger::log('update', 'faq_items', $id, ['question' => $question]);
    } else {
        $data['created_by'] = (int)Auth::id();
        $id = FAQItem::create($data);
        Logger::log('create', 'faq_items', (int)$id, ['question' => $question]);
    }

    Session::flash('status', 'FAQ question saved.');
}

function faq_admin_delete_item(): void
{
    $id = Security::cleanInt($_POST['id'] ?? 0);
    if ($id > 0) {
        FAQItem::delete($id);
        Logger::log('delete', 'faq_items', $id);
        Session::flash('status', 'FAQ question deleted.');
    }
}

function faq_admin_category_name(int $id): string
{
    $row = $id > 0 ? FAQCategory::find($id) : null;
    return (string)($row['name'] ?? '');
}

function faq_admin_clean_answer(string $html): string
{
    $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? '';
    $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
    $html = preg_replace('/(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2/i', '$1="#"', $html) ?? '';
    return trim(strip_tags($html, '<p><br><strong><b><em><i><u><s><ul><ol><li><a><h2><h3><h4><blockquote>'));
}

function faq_admin_slug(string $value): string
{
    $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
    return $slug !== '' ? substr($slug, 0, 160) : 'faq-' . substr(sha1($value . microtime(true)), 0, 8);
}

function faq_admin_icon(string $icon): string
{
    $icon = preg_replace('/[^a-z0-9-]+/i', '', $icon) ?: 'fa-circle-question';
    return str_starts_with($icon, 'fa-') ? $icon : 'fa-' . $icon;
}

function faq_admin_excerpt(string $html): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    return mb_strlen($text) > 150 ? mb_substr($text, 0, 150) . '...' : $text;
}

function faq_admin_page_url(int $page, array $filters): string
{
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn ($value): bool => (string)$value !== '');
    return Url::to('admin/superadmin/faq.php' . ($query ? '?' . http_build_query($query) : ''));
}

function faq_admin_redirect_query(): string
{
    $params = [];
    foreach (['q', 'category_id', 'status', 'popular', 'page'] as $key) {
        if (isset($_GET[$key]) && trim((string)$_GET[$key]) !== '') {
            $params[$key] = Security::cleanString((string)$_GET[$key]);
        }
    }
    return $params ? '?' . http_build_query($params) : '';
}

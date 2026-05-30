<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
    'csrf_form' => 'cms_editor',
]);

$data = cms_api_payload();
$slug = cms_api_clean_slug((string)($data['slug'] ?? ''));
$page = $slug !== '' ? CmsPage::findBySlug($slug) : null;

if (!$page) {
    Response::json(['success' => false, 'message' => 'CMS page not found.'], 404);
}

$status = (string)($data['status'] ?? 'draft');
if (!in_array($status, ['published', 'draft'], true)) {
    Response::json(['success' => false, 'message' => 'Invalid page status.'], 422);
}

cms_api_revision((int)$page['id'], null, 'page', $slug, $page);

CmsPage::upsert([
    'slug' => $slug,
    'template' => $page['template'] ?? null,
    'route_path' => cms_api_text($data['route_path'] ?? ''),
    'status' => $status,
    'seo_title' => cms_api_text($data['seo_title'] ?? ''),
    'seo_description' => cms_api_textarea($data['seo_description'] ?? ''),
    'seo_keywords' => cms_api_textarea($data['seo_keywords'] ?? ''),
    'canonical_url' => cms_api_text($data['canonical_url'] ?? ''),
    'hero_image' => cms_api_text($data['hero_image'] ?? ''),
]);

Logger::log('update', 'cms_pages', (int)$page['id'], ['slug' => $slug]);

Response::json([
    'success' => true,
    'message' => 'Page settings saved.',
    'page' => CmsPage::findBySlug($slug),
]);

function cms_api_payload(): array
{
    $json = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($json) ? $json : $_POST;
}

function cms_api_clean_slug(string $slug): string
{
    return preg_replace('/[^a-z0-9_-]+/', '', strtolower(trim($slug))) ?: '';
}

function cms_api_text(mixed $value): string
{
    return Security::cleanString(trim((string)$value));
}

function cms_api_textarea(mixed $value): string
{
    return trim(strip_tags((string)$value));
}

function cms_api_revision(int $pageId, ?int $sectionId, string $type, string $target, array $snapshot): void
{
    Database::query(
        'INSERT INTO cms_revisions (page_id, section_id, revision_type, target_key, snapshot_json, created_by)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$pageId, $sectionId, $type, $target, json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), (int)Auth::id()]
    );
}

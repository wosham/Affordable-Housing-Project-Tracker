<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
    'csrf_form' => 'cms_editor',
]);

$data = cms_section_payload();
$pageId = Security::cleanInt($data['page_id'] ?? 0);
$sectionId = Security::cleanInt($data['section_id'] ?? 0);
$section = $sectionId > 0 ? Database::fetch('SELECT * FROM cms_sections WHERE id = ? AND page_id = ? LIMIT 1', [$sectionId, $pageId]) : null;

if (!$section) {
    Response::json(['success' => false, 'message' => 'CMS section not found.'], 404);
}

$content = is_array($data['content'] ?? null) ? $data['content'] : [];
$content = cms_section_clean_content($content);

Database::query(
    'INSERT INTO cms_revisions (page_id, section_id, revision_type, target_key, snapshot_json, created_by)
     VALUES (?, ?, "section", ?, ?, ?)',
    [$pageId, $sectionId, $section['section_key'], json_encode($section, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), (int)Auth::id()]
);

CmsSection::upsert($pageId, [
    'section_key' => $section['section_key'],
    'label' => $section['label'],
    'section_type' => $section['section_type'] ?? 'rich_text',
    'sort_order' => (int)($section['sort_order'] ?? 0),
    'editor_mode' => $section['editor_mode'] ?? 'structured',
    'is_visible' => (int)$section['is_visible'],
    'is_locked' => (int)($section['is_locked'] ?? 0),
    'content' => $content,
]);

Logger::log('update', 'cms_sections', $sectionId, ['section_key' => $section['section_key']]);

Response::json([
    'success' => true,
    'message' => 'Section saved.',
    'section' => CmsSection::findForPage($pageId, (string)$section['section_key']),
]);

function cms_section_payload(): array
{
    $json = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($json) ? $json : $_POST;
}

function cms_section_clean_content(array $content): array
{
    $clean = [];
    foreach ($content as $key => $value) {
        $key = preg_replace('/[^a-z0-9_]+/', '', strtolower((string)$key));
        if ($key === '') {
            continue;
        }

        if (is_array($value)) {
            $clean[$key] = cms_section_clean_content($value);
            continue;
        }

        $clean[$key] = str_contains($key, 'body') || str_contains($key, 'html')
            ? cms_section_clean_html((string)$value)
            : Security::cleanString(trim((string)$value));
    }

    return $clean;
}

function cms_section_clean_html(string $html): string
{
    $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? '';
    $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
    $html = preg_replace('/(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2/i', '$1="#"', $html) ?? '';

    return trim(strip_tags(
        $html,
        '<p><br><strong><b><em><i><u><s><ul><ol><li><a><h2><h3><h4><h5><h6><blockquote><code><pre><div><span><section><table><thead><tbody><tr><th><td>'
    ));
}

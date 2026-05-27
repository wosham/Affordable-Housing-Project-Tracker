<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
    'csrf_form' => 'cms_editor',
]);

$data = json_decode(file_get_contents('php://input') ?: '', true);
$data = is_array($data) ? $data : $_POST;
$sectionId = Security::cleanInt($data['section_id'] ?? 0);
$section = $sectionId > 0 ? Database::fetch('SELECT * FROM cms_sections WHERE id = ? LIMIT 1', [$sectionId]) : null;

if (!$section) {
    Response::json(['success' => false, 'message' => 'CMS section not found.'], 404);
}

if ((int)($section['is_locked'] ?? 0) === 1) {
    Response::json(['success' => false, 'message' => 'This section is locked and cannot be toggled.'], 423);
}

$next = (int)$section['is_visible'] === 1 ? 0 : 1;
Database::query('UPDATE cms_sections SET is_visible = ?, updated_by = ? WHERE id = ?', [$next, (int)Auth::id(), $sectionId]);

Database::query(
    'INSERT INTO cms_revisions (page_id, section_id, revision_type, target_key, snapshot_json, created_by)
     VALUES (?, ?, "section", ?, ?, ?)',
    [(int)$section['page_id'], $sectionId, $section['section_key'], json_encode($section, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), (int)Auth::id()]
);

Logger::log($next === 1 ? 'show' : 'hide', 'cms_sections', $sectionId, ['section_key' => $section['section_key']]);

Response::json([
    'success' => true,
    'message' => $next === 1 ? 'Section is visible.' : 'Section is hidden.',
    'is_visible' => $next,
]);

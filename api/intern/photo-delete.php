<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['intern'],
    'csrf_form' => 'intern_work',
]);

$photoId = Security::cleanInt($_POST['photo_id'] ?? 0);
if ($photoId <= 0) {
    Response::json(['success' => false, 'message' => 'Photo could not be found.'], 422);
}

if (!InternProjectWork::deletePhoto((int)Auth::id(), $photoId)) {
    Response::json(['success' => false, 'message' => 'This photo cannot be removed.'], 403);
}

Logger::log('delete', 'intern_site_photos', $photoId, ['user_id' => (int)Auth::id()]);
Response::json(['success' => true, 'message' => 'Photo removed from your project gallery.']);

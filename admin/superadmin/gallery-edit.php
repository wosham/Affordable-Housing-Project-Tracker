<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role('superadmin');

$csrfForm = 'superadmin_gallery';
$id = Security::cleanInt($_GET['id'] ?? ($_POST['id'] ?? 0));
$galleryItem = $id > 0 ? GalleryImage::findDetailed($id) : null;

if (!$galleryItem) {
    Session::flash('error', 'Gallery item could not be found.');
    Response::redirect(Url::to('admin/superadmin/gallery.php'));
}

if (Security::isPost()) {
    if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
        Session::flash('error', 'Your session token expired. Please try again.');
        Response::redirect(Url::to('admin/superadmin/gallery-edit.php?id=' . $id));
    }

    try {
        gallery_prepare_item_payload($_POST);
        $savedId = GalleryImage::saveFromAdmin($_POST);
        Logger::log('update', 'gallery_images', $savedId);
        Session::flash('status', 'Gallery item updated.');
        Response::redirect(Url::to('admin/superadmin/gallery.php'));
    } catch (Throwable $e) {
        Session::flash('error', $e->getMessage());
        $galleryItem = array_merge($galleryItem, $_POST);
    }
}

$galleryCategories = GalleryCategory::allOrdered();
$galleryProjects = Project::withRelations([], 200);
$galleryConstituencies = Database::fetchAll('SELECT id, name, slug FROM constituencies ORDER BY name ASC');
$galleryFormAction = Url::to('admin/superadmin/gallery-edit.php?id=' . $id);

$pageTitle = 'Edit Gallery Item';
$pageDescription = 'Update a public gallery photo or video item.';
$adminRole = 'superadmin';
$contentClass = 'sa-gallery-admin-page';
$componentCss = ['media-library', 'gallery-admin'];
$pageScripts = ['media-picker', 'gallery-admin'];
$breadcrumbs = [
    ['label' => 'Portal', 'url' => Url::to('admin/index.php')],
    ['label' => 'Super Administrator', 'url' => Url::to('admin/superadmin/dashboard.php')],
    ['label' => 'Gallery', 'url' => Url::to('admin/superadmin/gallery.php')],
    ['label' => 'Edit Item'],
];

include __DIR__ . '/../../app/partials/admin/shell-start.php';
include __DIR__ . '/../../app/partials/admin/gallery-item-form.php';
include __DIR__ . '/../../app/partials/admin/shell-end.php';

function gallery_prepare_item_payload(array &$payload): void
{
    $mediaType = (string)($payload['media_type'] ?? 'image');
    if ($mediaType === 'video') {
        $videoAssetIds = trim((string)($payload['video_media_ids'] ?? ''));
        $thumbnail = (int)($payload['thumbnail_media_id'] ?? 0);
        $firstVideo = (int)(preg_split('/[\s,]+/', $videoAssetIds)[0] ?? 0);
        $payload['image_id'] = $firstVideo > 0 ? $firstVideo : $thumbnail;
        return;
    }

    $imageIds = trim((string)($payload['primary_image_ids'] ?? ''));
    $payload['image_id'] = (int)(preg_split('/[\s,]+/', $imageIds)[0] ?? 0);
    $payload['video_urls'] = '';
    $payload['duration'] = '';
}

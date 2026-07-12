<?php

class GalleryImage extends Model
{
    protected static string $table = 'gallery_images';

    public const STATUSES = ['draft', 'published', 'hidden'];
    public const MEDIA_TYPES = ['image', 'video'];

    public static function publicItems(array $filters = [], int $limit = 0): array
    {
        $filters['status'] = 'published';
        [$where, $bindings] = self::mediaFilterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit : '';

        return Database::fetchAll(self::mediaSelectSql() . $where . '
            ORDER BY gi.sort_order ASC, gm.sort_order ASC, gi.taken_at DESC, gi.year DESC, gm.id DESC' . $limitSql, $bindings);
    }

    public static function adminList(array $filters = [], int $limit = 30, int $offset = 0): array
    {
        [$where, $bindings] = self::entryFilterSql($filters);
        return Database::fetchAll(self::entrySelectSql() . $where . '
            GROUP BY gi.id
            ORDER BY gi.sort_order ASC, gi.id DESC
            LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), $bindings);
    }

    public static function adminCount(array $filters = []): int
    {
        [$where, $bindings] = self::entryFilterSql($filters);
        $row = Database::fetch('
            SELECT COUNT(DISTINCT gi.id) AS total
            FROM gallery_images gi
            LEFT JOIN gallery_categories gc ON gc.id = gi.category_id
            LEFT JOIN projects p ON p.id = gi.project_id
            LEFT JOIN constituencies c ON c.id = gi.constituency_id
            LEFT JOIN gallery_media gm ON gm.gallery_id = gi.id
            LEFT JOIN media_library ml ON ml.id = COALESCE(gi.image_id, gm.media_id)
            ' . $where, $bindings);

        return (int)($row['total'] ?? 0);
    }

    public static function findDetailed(int $id): ?array
    {
        $entry = Database::fetch(self::entrySelectSql() . ' WHERE gi.id = ? GROUP BY gi.id LIMIT 1', [$id]);
        if (!$entry) {
            return null;
        }

        $entry['media_items'] = self::mediaForEntry($id);
        return $entry;
    }

    public static function mediaForEntry(int $entryId): array
    {
        return Database::fetchAll("
            SELECT
                gm.*,
                ml.title AS media_title,
                ml.filename AS media_filename,
                ml.path AS media_path,
                ml.url AS media_url,
                ml.type AS media_mime,
                ml.extension AS media_extension,
                thumb.title AS thumbnail_title,
                thumb.filename AS thumbnail_filename,
                thumb.path AS thumbnail_path,
                thumb.url AS thumbnail_url
            FROM gallery_media gm
            LEFT JOIN media_library ml ON ml.id = gm.media_id
            LEFT JOIN media_library thumb ON thumb.id = gm.thumbnail_media_id
            WHERE gm.gallery_id = ?
            ORDER BY gm.sort_order ASC, gm.id ASC
        ", [$entryId]);
    }

    public static function stats(): array
    {
        $row = Database::fetch("
            SELECT
                COUNT(DISTINCT gi.id) AS total,
                COUNT(DISTINCT CASE WHEN gi.status = 'published' THEN gi.id END) AS published,
                COUNT(DISTINCT CASE WHEN gi.status = 'draft' THEN gi.id END) AS drafts,
                COUNT(DISTINCT CASE WHEN gi.status = 'hidden' THEN gi.id END) AS hidden,
                COUNT(DISTINCT CASE WHEN gi.is_featured = 1 THEN gi.id END) AS featured,
                COUNT(DISTINCT CASE WHEN gm.media_type = 'video' THEN gm.id END) AS videos
            FROM gallery_images gi
            LEFT JOIN gallery_media gm ON gm.gallery_id = gi.id
        ") ?: [];

        return array_map(static fn ($value) => (int)$value, $row);
    }

    public static function years(array $filters = []): array
    {
        [$where, $bindings] = self::entryFilterSql($filters);
        $where = $where !== '' ? $where . ' AND gi.year IS NOT NULL' : ' WHERE gi.year IS NOT NULL';
        $rows = Database::fetchAll('
            SELECT DISTINCT gi.year
            FROM gallery_images gi
            LEFT JOIN gallery_categories gc ON gc.id = gi.category_id
            LEFT JOIN projects p ON p.id = gi.project_id
            LEFT JOIN constituencies c ON c.id = gi.constituency_id
            LEFT JOIN gallery_media gm ON gm.gallery_id = gi.id
            ' . $where . '
            ORDER BY gi.year DESC
        ', $bindings);
        return array_map(static fn (array $row): int => (int)$row['year'], $rows);
    }

    public static function siteSummary(array $filters = []): array
    {
        $minYear = max(0, (int)($filters['year_from'] ?? 0));
        $yearSql = '';
        $bindings = [];

        if ($minYear > 0) {
            $yearSql = ' AND COALESCE(gi.year, YEAR(gi.taken_at), ?) >= ?';
            $bindings = [$minYear, $minYear];
        }

        return Database::fetchAll("
            SELECT
                COALESCE(c.slug, gi.site_key, 'general') AS site_key,
                COALESCE(c.name, gi.location, 'General') AS site_name,
                COALESCE((
                    SELECT SUM(p2.units)
                    FROM projects p2
                    WHERE p2.constituency_id = c.id
                ), MAX(p.units), 0) AS units,
                COALESCE((
                    SELECT ROUND(AVG(NULLIF(p2.pct_complete, 0)))
                    FROM projects p2
                    WHERE p2.constituency_id = c.id
                ), ROUND(AVG(NULLIF(p.pct_complete, 0))), 0) AS pct,
                COUNT(gm.id) AS photos
            FROM gallery_images gi
            LEFT JOIN gallery_media gm ON gm.gallery_id = gi.id AND gm.media_type = 'image'
            LEFT JOIN constituencies c ON c.id = gi.constituency_id
            LEFT JOIN projects p ON p.id = gi.project_id
            WHERE gi.status = 'published'
              AND gm.id IS NOT NULL
              {$yearSql}
            GROUP BY site_key, site_name
            ORDER BY photos DESC, site_name ASC
        ", $bindings);
    }

    public static function projectMedia(int $projectId, string $mediaType = 'image', int $limit = 24): array
    {
        return self::publicItems(['project_id' => $projectId, 'media_type' => $mediaType], $limit);
    }

    public static function publicForProject(int $projectId, int $limit = 24): array
    {
        $rows = self::projectMedia($projectId, 'image', $limit);
        return array_map(static function (array $row): array {
            $url = (string)($row['thumbnail_url'] ?: $row['media_url'] ?: $row['thumbnail_path'] ?: $row['media_path'] ?: '');
            $full = (string)($row['media_url'] ?: $row['media_path'] ?: $url);

            $alt = (string)($row['alt_text'] ?? '');
            if ($alt === '') {
                $alt = (string)($row['media_alt_text'] ?? '');
            }
            if ($alt === '') {
                $alt = (string)($row['title'] ?? '');
            }

            return [
                'id' => (int)($row['gallery_media_id'] ?? $row['id'] ?? 0),
                'title' => (string)($row['title'] ?? ''),
                'caption' => (string)($row['caption'] ?? ''),
                'alt_text' => $alt,
                'url' => $url,
                'full_url' => $full,
                'taken_at' => (string)($row['taken_at'] ?? ''),
                'location' => (string)($row['location'] ?? ''),
            ];
        }, $rows);
    }

    public static function saveFromAdmin(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $title = Security::cleanString((string)($data['title'] ?? ''));
        $caption = Security::cleanString((string)($data['caption'] ?? ''));
        $altText = Security::cleanString((string)($data['alt_text'] ?? ''));
        $location = Security::cleanString((string)($data['location'] ?? ''));
        $credit = Security::cleanString((string)($data['credit'] ?? ''));
        $highlightSummary = Security::cleanString((string)($data['highlight_summary'] ?? ''));
        $duration = Security::cleanString((string)($data['duration'] ?? ''));
        $externalUrl = trim((string)($data['external_url'] ?? ''));
        $videoUrl = trim((string)($data['video_url'] ?? ''));
        $status = in_array((string)($data['status'] ?? 'draft'), self::STATUSES, true) ? (string)$data['status'] : 'draft';

        $categoryId = (int)($data['category_id'] ?? 0) ?: null;
        $projectId = (int)($data['project_id'] ?? 0) ?: null;
        $constituencyId = (int)($data['constituency_id'] ?? 0) ?: null;
        $year = (int)($data['year'] ?? 0) ?: null;
        $sortOrder = max(0, (int)($data['sort_order'] ?? 0));
        $isFeatured = !empty($data['is_featured']) ? 1 : 0;
        $isHighlight = !empty($data['is_highlight']) ? 1 : 0;
        $takenAt = trim((string)($data['taken_at'] ?? '')) ?: null;

        $imageIds = self::idList($data['primary_image_ids'] ?? ($data['primary_image_id'] ?? ''));
        $videoIds = self::idList($data['video_media_ids'] ?? ($data['video_media_id'] ?? ''));
        $thumbnailId = (int)($data['thumbnail_media_id'] ?? 0) ?: null;
        $externalVideos = self::urlList((string)($data['video_urls'] ?? $videoUrl));

        if ($title === '') {
            throw new RuntimeException('Gallery title is required.');
        }
        if ($categoryId === null) {
            throw new RuntimeException('Select a gallery category.');
        }
        if ($externalUrl !== '' && !filter_var($externalUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('External URL is invalid.');
        }
        foreach ($externalVideos as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new RuntimeException('One of the video URLs is invalid.');
            }
        }
        if (!$imageIds && !$videoIds && !$externalVideos) {
            throw new RuntimeException('Select at least one image, uploaded video, or video URL.');
        }
        if (($videoIds || $externalVideos) && $thumbnailId === null) {
            throw new RuntimeException('Video entries require a thumbnail image for public gallery cards.');
        }
        foreach ($videoIds as $videoId) {
            $media = MediaLibrary::findDetailed($videoId);
            $extension = strtolower((string)($media['extension'] ?? ''));
            $type = strtolower((string)($media['type'] ?? ''));
            if (!str_starts_with($type, 'video/') && !in_array($extension, ['mp4', 'webm', 'mov'], true)) {
                throw new RuntimeException('Uploaded video selections must be video files.');
            }
        }

        $entryMediaType = ($videoIds || $externalVideos) && !$imageIds ? 'video' : 'image';
        $coverId = $imageIds[0] ?? $thumbnailId ?? $videoIds[0] ?? null;
        $siteKey = self::siteKey($constituencyId, $location);

        Database::beginTransaction();
        try {
            $bindings = [
                $categoryId,
                $coverId,
                $thumbnailId,
                $projectId,
                $constituencyId,
                $title,
                $caption,
                $altText,
                $credit,
                $takenAt,
                $location,
                $siteKey,
                $year,
                $entryMediaType,
                $externalVideos[0] ?? null,
                $duration,
                $externalUrl !== '' ? $externalUrl : null,
                $highlightSummary,
                $isFeatured,
                $isHighlight,
                $status,
                $sortOrder,
            ];

            if ($id > 0) {
                $bindings[] = $id;
                Database::query("
                    UPDATE gallery_images SET
                        category_id = ?, image_id = ?, thumbnail_media_id = ?, project_id = ?, constituency_id = ?,
                        title = ?, caption = ?, alt_text = ?, credit = ?, taken_at = ?, location = ?, site_key = ?,
                        year = ?, media_type = ?, video_url = ?, duration = ?, external_url = ?, highlight_summary = ?,
                        is_featured = ?, is_highlight = ?, status = ?, sort_order = ?
                    WHERE id = ?
                ", $bindings);
            } else {
                Database::query("
                    INSERT INTO gallery_images
                        (category_id, image_id, thumbnail_media_id, project_id, constituency_id, title, caption, alt_text, credit,
                         taken_at, location, site_key, year, media_type, video_url, duration, external_url, highlight_summary,
                         is_featured, is_highlight, status, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", $bindings);
                $id = (int)Database::lastInsertId();
            }

            self::replaceMedia($id, $imageIds, $videoIds, $externalVideos, $thumbnailId, $caption, $altText);
            Database::commit();
            return $id;
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function quickStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new RuntimeException('Invalid gallery status.');
        }
        Database::query('UPDATE gallery_images SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function toggleFeatured(int $id): void
    {
        Database::query('UPDATE gallery_images SET is_featured = CASE WHEN is_featured = 1 THEN 0 ELSE 1 END WHERE id = ?', [$id]);
    }

    public static function toggleHighlight(int $id): void
    {
        Database::query('UPDATE gallery_images SET is_highlight = CASE WHEN is_highlight = 1 THEN 0 ELSE 1 END WHERE id = ?', [$id]);
    }

    public static function deleteItem(int $id): void
    {
        Database::query(
            "UPDATE gallery_images
             SET status = 'hidden', is_featured = 0, is_highlight = 0
             WHERE id = ?",
            [$id]
        );
    }

    private static function replaceMedia(int $entryId, array $imageIds, array $videoIds, array $externalVideos, ?int $thumbnailId, string $caption, string $altText): void
    {
        Database::query('DELETE FROM gallery_media WHERE gallery_id = ?', [$entryId]);
        $sort = 0;
        foreach ($imageIds as $mediaId) {
            Database::query("
                INSERT INTO gallery_media (gallery_id, media_id, media_type, caption, alt_text, sort_order)
                VALUES (?, ?, 'image', ?, ?, ?)
            ", [$entryId, $mediaId, $caption, $altText, $sort++]);
        }
        foreach ($videoIds as $mediaId) {
            Database::query("
                INSERT INTO gallery_media (gallery_id, media_id, thumbnail_media_id, media_type, caption, alt_text, sort_order)
                VALUES (?, ?, ?, 'video', ?, ?, ?)
            ", [$entryId, $mediaId, $thumbnailId, $caption, $altText, $sort++]);
        }
        foreach ($externalVideos as $url) {
            Database::query("
                INSERT INTO gallery_media (gallery_id, media_id, thumbnail_media_id, media_type, video_url, caption, alt_text, sort_order)
                VALUES (?, NULL, ?, 'video', ?, ?, ?, ?)
            ", [$entryId, $thumbnailId, $url, $caption, $altText, $sort++]);
        }
    }

    private static function entrySelectSql(): string
    {
        return "
            SELECT
                gi.*,
                gc.name AS category_name,
                gc.slug AS category_slug,
                p.name AS project_name,
                p.slug AS project_slug,
                p.units AS project_units,
                p.pct_complete AS project_pct_complete,
                c.name AS constituency_name,
                c.slug AS constituency_slug,
                ml.path AS media_path,
                ml.url AS media_url,
                ml.alt_text AS media_alt_text,
                ml.type AS media_mime,
                ml.extension AS media_extension,
                thumb.path AS thumbnail_path,
                thumb.url AS thumbnail_url,
                COUNT(DISTINCT gm.id) AS media_count,
                COUNT(DISTINCT CASE WHEN gm.media_type = 'image' THEN gm.id END) AS image_count,
                COUNT(DISTINCT CASE WHEN gm.media_type = 'video' THEN gm.id END) AS video_count
            FROM gallery_images gi
            LEFT JOIN gallery_categories gc ON gc.id = gi.category_id
            LEFT JOIN projects p ON p.id = gi.project_id
            LEFT JOIN constituencies c ON c.id = gi.constituency_id
            LEFT JOIN media_library ml ON ml.id = gi.image_id
            LEFT JOIN media_library thumb ON thumb.id = gi.thumbnail_media_id
            LEFT JOIN gallery_media gm ON gm.gallery_id = gi.id
        ";
    }

    private static function mediaSelectSql(): string
    {
        return "
            SELECT
                gi.*,
                gm.id AS gallery_media_id,
                gm.media_id AS gallery_media_media_id,
                gm.thumbnail_media_id AS gallery_media_thumbnail_id,
                gm.media_type AS gallery_media_type,
                COALESCE(gm.video_url, gi.video_url) AS video_url,
                COALESCE(gm.caption, gi.caption) AS caption,
                COALESCE(gm.alt_text, gi.alt_text) AS alt_text,
                gc.name AS category_name,
                gc.slug AS category_slug,
                p.name AS project_name,
                p.slug AS project_slug,
                p.units AS project_units,
                p.pct_complete AS project_pct_complete,
                c.name AS constituency_name,
                c.slug AS constituency_slug,
                ml.path AS media_path,
                ml.url AS media_url,
                ml.alt_text AS media_alt_text,
                ml.type AS media_mime,
                ml.extension AS media_extension,
                thumb.path AS thumbnail_path,
                thumb.url AS thumbnail_url
            FROM gallery_media gm
            INNER JOIN gallery_images gi ON gi.id = gm.gallery_id
            LEFT JOIN gallery_categories gc ON gc.id = gi.category_id
            LEFT JOIN projects p ON p.id = gi.project_id
            LEFT JOIN constituencies c ON c.id = gi.constituency_id
            LEFT JOIN media_library ml ON ml.id = gm.media_id
            LEFT JOIN media_library thumb ON thumb.id = COALESCE(gm.thumbnail_media_id, gi.thumbnail_media_id)
        ";
    }

    private static function entryFilterSql(array $filters): array
    {
        return self::filterSql($filters, 'entry');
    }

    private static function mediaFilterSql(array $filters): array
    {
        return self::filterSql($filters, 'media');
    }

    private static function filterSql(array $filters, string $scope): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['status'])) {
            $where[] = 'gi.status = ?';
            $bindings[] = (string)$filters['status'];
        }
        if (!empty($filters['media_type'])) {
            $where[] = $scope === 'media' ? 'gm.media_type = ?' : 'COALESCE(gm.media_type, gi.media_type) = ?';
            $bindings[] = (string)$filters['media_type'];
        }
        if (!empty($filters['category'])) {
            $where[] = 'gc.slug = ?';
            $bindings[] = (string)$filters['category'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'gi.category_id = ?';
            $bindings[] = (int)$filters['category_id'];
        }
        if (!empty($filters['year'])) {
            $where[] = 'gi.year = ?';
            $bindings[] = (int)$filters['year'];
        }
        if (!empty($filters['year_from'])) {
            $year = max(0, (int)$filters['year_from']);
            if ($year > 0) {
                $where[] = 'COALESCE(gi.year, YEAR(gi.taken_at), ?) >= ?';
                $bindings[] = $year;
                $bindings[] = $year;
            }
        }
        if (!empty($filters['site_key'])) {
            $where[] = 'gi.site_key = ?';
            $bindings[] = (string)$filters['site_key'];
        }
        if (!empty($filters['project_id'])) {
            $where[] = 'gi.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        }
        if (!empty($filters['constituency_id'])) {
            $where[] = 'gi.constituency_id = ?';
            $bindings[] = (int)$filters['constituency_id'];
        }
        if (!empty($filters['featured'])) {
            $where[] = 'gi.is_featured = 1';
        }
        if (!empty($filters['highlight'])) {
            $where[] = 'gi.is_highlight = 1';
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(gi.title LIKE ? OR gi.caption LIKE ? OR gi.location LIKE ? OR gc.name LIKE ? OR p.name LIKE ? OR c.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function idList(mixed $value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = preg_split('/[\s,]+/', (string)$value) ?: [];
        }

        return array_values(array_unique(array_filter(array_map(static fn ($id): int => (int)$id, $parts))));
    }

    private static function urlList(string $value): array
    {
        $parts = preg_split('/[\r\n,]+/', $value) ?: [];
        return array_values(array_unique(array_filter(array_map('trim', $parts))));
    }

    private static function siteKey(?int $constituencyId, string $location): string
    {
        if ($constituencyId) {
            $row = Database::fetch('SELECT slug FROM constituencies WHERE id = ? LIMIT 1', [$constituencyId]);
            if (!empty($row['slug'])) {
                return (string)$row['slug'];
            }
        }

        return strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $location), '-')) ?: 'general';
    }
}

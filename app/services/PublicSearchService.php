<?php

class PublicSearchService
{
    private const TYPES = ['projects', 'news', 'faq', 'gallery', 'constituencies'];

    public static function search(string $query, string $type = 'all', int $limit = 10, int $page = 1): array
    {
        $query = self::cleanQuery($query);
        $type = in_array($type, array_merge(['all'], self::TYPES), true) ? $type : 'all';
        $limit = max(1, min(30, $limit));
        $page = max(1, $page);
        $counts = array_fill_keys(self::TYPES, 0);

        if (self::length($query) < 2) {
            return [
                'query' => $query,
                'type' => $type,
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'has_more' => false,
                'counts' => $counts,
                'results' => [],
                'message' => 'Enter at least 2 characters to search the public site.',
            ];
        }

        $results = [];
        foreach (self::enabledTypes($type) as $source) {
            $method = 'search' . str_replace(' ', '', ucwords(str_replace('_', ' ', $source)));
            if (method_exists(self::class, $method)) {
                $items = self::{$method}($query);
                $counts[$source] = count($items);
                $results = array_merge($results, $items);
            }
        }

        usort($results, static function (array $a, array $b): int {
            $score = ((int)($b['score'] ?? 0)) <=> ((int)($a['score'] ?? 0));
            if ($score !== 0) {
                return $score;
            }

            return strcmp((string)($b['date'] ?? ''), (string)($a['date'] ?? ''));
        });

        $total = count($results);
        $offset = ($page - 1) * $limit;

        return [
            'query' => $query,
            'type' => $type,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'has_more' => ($offset + $limit) < $total,
            'counts' => $counts,
            'results' => array_slice($results, $offset, $limit),
            'message' => $total > 0 ? '' : 'No public results matched your search.',
        ];
    }

    public static function availableTypes(): array
    {
        return self::TYPES;
    }

    private static function searchProjects(string $query): array
    {
        $rows = class_exists('Project') ? Project::publicListing(['search' => $query], 80, 0) : [];
        $results = [];

        foreach ($rows as $row) {
            $title = (string)($row['name'] ?? '');
            $body = self::join([
                $row['description'] ?? '',
                $row['location_label'] ?? '',
                $row['contractor_name'] ?? '',
                $row['current_milestone'] ?? '',
                $row['constituency_name'] ?? '',
                $row['ward_name'] ?? '',
                $row['category_name'] ?? '',
            ]);

            $slug = (string)($row['slug'] ?? $row['id'] ?? '');
            $results[] = self::result(
                'projects',
                'Project',
                $title,
                self::excerpt($body !== '' ? $body : $title, $query),
                'project-detail.php?id=' . rawurlencode($slug),
                (string)($row['status_label'] ?? status_label((string)($row['status'] ?? 'Project'))),
                self::join([$row['constituency_name'] ?? '', $row['ward_name'] ?? ''], ' · '),
                (string)($row['updated_at'] ?? ''),
                self::score($query, $title, $body, (string)($row['slug'] ?? '')),
                ''
            );
        }

        return $results;
    }

    private static function searchNews(string $query): array
    {
        $like = '%' . $query . '%';
        $rows = Database::fetchAll(
            "SELECT na.id, na.title, na.slug, na.excerpt, na.body, na.published_at, na.updated_at,
                    nc.name AS category_name
             FROM news_articles na
             LEFT JOIN news_categories nc ON nc.id = na.category_id
             WHERE na.status = 'published'
               AND COALESCE(na.is_visible, 1) = 1
               AND na.deleted_at IS NULL
               AND (na.published_at IS NULL OR na.published_at <= NOW())
               AND (na.title LIKE ? OR na.slug LIKE ? OR na.excerpt LIKE ? OR na.body LIKE ? OR nc.name LIKE ?)
             ORDER BY na.published_at DESC, na.id DESC
             LIMIT 80",
            [$like, $like, $like, $like, $like]
        );

        $results = [];
        foreach ($rows as $row) {
            $title = (string)($row['title'] ?? '');
            $body = self::join([$row['excerpt'] ?? '', $row['body'] ?? '', $row['category_name'] ?? '']);
            $slug = (string)($row['slug'] ?? $row['id'] ?? '');
            $results[] = self::result(
                'news',
                'News',
                $title,
                self::excerpt($body, $query),
                'news-article.php?id=' . rawurlencode($slug),
                (string)($row['category_name'] ?? 'News'),
                self::dateLabel((string)($row['published_at'] ?? $row['updated_at'] ?? '')),
                (string)($row['published_at'] ?? $row['updated_at'] ?? ''),
                self::score($query, $title, $body, (string)($row['slug'] ?? '')),
                ''
            );
        }

        return $results;
    }

    private static function searchFaq(string $query): array
    {
        $rows = class_exists('FAQItem') ? FAQItem::publicItems() : [];
        $results = [];

        foreach ($rows as $row) {
            $title = (string)($row['question'] ?? '');
            $body = self::join([$row['answer'] ?? '', $row['search_keywords'] ?? '', $row['category_name'] ?? '']);
            if (!self::matches($query, self::join([$title, $body]))) {
                continue;
            }

            $domId = class_exists('FAQItem') ? FAQItem::publicDomId($row) : ('faq-' . (int)($row['id'] ?? 0));
            $results[] = self::result(
                'faq',
                'FAQ',
                $title,
                self::excerpt($body, $query),
                'faq.php#' . rawurlencode($domId),
                (string)($row['category_name'] ?? 'FAQ'),
                'Frequently asked question',
                (string)($row['updated_at'] ?? ''),
                self::score($query, $title, $body, (string)($row['slug'] ?? '')),
                ''
            );
        }

        return $results;
    }

    private static function searchGallery(string $query): array
    {
        $rows = class_exists('GalleryImage') ? GalleryImage::publicItems(['search' => $query], 80) : [];
        $results = [];

        foreach ($rows as $row) {
            $title = (string)($row['title'] ?? '');
            $body = self::join([
                $row['caption'] ?? '',
                $row['alt_text'] ?? '',
                $row['location'] ?? '',
                $row['category_name'] ?? '',
                $row['project_name'] ?? '',
                $row['constituency_name'] ?? '',
            ]);

            $image = (string)($row['thumbnail_url'] ?? $row['media_url'] ?? $row['thumbnail_path'] ?? $row['media_path'] ?? '');
            $results[] = self::result(
                'gallery',
                'Gallery',
                $title !== '' ? $title : 'Gallery media',
                self::excerpt($body !== '' ? $body : $title, $query),
                'gallery.php',
                (string)($row['category_name'] ?? 'Gallery'),
                self::join([$row['project_name'] ?? '', $row['constituency_name'] ?? ''], ' · '),
                (string)($row['taken_at'] ?? $row['updated_at'] ?? ''),
                self::score($query, $title, $body, (string)($row['site_key'] ?? '')),
                $image
            );
        }

        return $results;
    }

    private static function searchConstituencies(string $query): array
    {
        $rows = class_exists('Constituency') ? Constituency::publicList() : [];
        $results = [];

        foreach ($rows as $row) {
            $title = (string)($row['name'] ?? '');
            $body = self::join([
                $row['description'] ?? '',
                $row['county'] ?? '',
                $row['status'] ?? '',
                implode(' ', (array)($row['wards'] ?? [])),
            ]);
            if (!self::matches($query, self::join([$title, $body, $row['slug'] ?? '']))) {
                continue;
            }

            $url = (string)($row['link'] ?? ('constituency-detail.php?id=' . rawurlencode((string)($row['slug'] ?? ''))));
            $results[] = self::result(
                'constituencies',
                'Constituency',
                $title,
                self::excerpt($body !== '' ? $body : $title, $query),
                $url,
                (string)($row['status_label'] ?? status_label((string)($row['status'] ?? 'Constituency'))),
                trim((string)($row['project_count'] ?? '0')) . ' public projects',
                (string)($row['updated_at'] ?? ''),
                self::score($query, $title, $body, (string)($row['slug'] ?? '')),
                (string)($row['image'] ?? '')
            );
        }

        return $results;
    }

    private static function result(string $type, string $label, string $title, string $excerpt, string $url, string $badge, string $meta, string $date, int $score, string $image): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'title' => $title,
            'excerpt' => $excerpt,
            'url' => class_exists('Url') ? Url::to($url) : $url,
            'badge' => $badge,
            'meta' => $meta,
            'date' => $date,
            'score' => $score,
            'image' => self::imageUrl($image),
            'icon' => self::icon($type),
        ];
    }

    private static function enabledTypes(string $type): array
    {
        return $type === 'all' ? self::TYPES : [$type];
    }

    private static function cleanQuery(string $query): string
    {
        $query = trim((string)preg_replace('/\s+/', ' ', strip_tags($query)));
        return self::length($query) > 120 ? substr($query, 0, 120) : $query;
    }

    private static function matches(string $query, string $haystack): bool
    {
        return str_contains(strtolower(self::plain($haystack)), strtolower($query));
    }

    private static function score(string $query, string $title, string $body, string $slug = ''): int
    {
        $needle = strtolower($query);
        $title = strtolower(self::plain($title));
        $body = strtolower(self::plain($body));
        $slug = strtolower($slug);

        if ($title === $needle) {
            return 100;
        }
        if (str_starts_with($title, $needle)) {
            return 85;
        }
        if (str_contains($title, $needle)) {
            return 70;
        }
        if ($slug !== '' && str_contains($slug, $needle)) {
            return 55;
        }
        if (str_contains($body, $needle)) {
            return 35;
        }

        return 10;
    }

    private static function excerpt(string $text, string $query, int $limit = 190): string
    {
        $plain = self::plain($text);
        if ($plain === '') {
            return '';
        }

        $pos = stripos($plain, $query);
        if ($pos !== false && $pos > 35) {
            $plain = '...' . substr($plain, max(0, $pos - 35));
        }

        if (strlen($plain) <= $limit) {
            return $plain;
        }

        return rtrim(substr($plain, 0, $limit - 3)) . '...';
    }

    private static function plain(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string)preg_replace('/\s+/', ' ', $text));
    }

    private static function join(array $parts, string $glue = ' '): string
    {
        return implode($glue, array_values(array_filter(array_map(
            static fn ($part): string => is_scalar($part) ? trim((string)$part) : '',
            $parts
        ), static fn (string $part): bool => $part !== '')));
    }

    private static function imageUrl(string $image): string
    {
        $image = trim($image);
        if ($image === '') {
            return '';
        }
        if (preg_match('#^(?:https?:)?//#i', $image)) {
            return $image;
        }

        return class_exists('Url') ? Url::asset($image) : $image;
    }

    private static function icon(string $type): string
    {
        return [
            'projects' => 'fa-building-columns',
            'news' => 'fa-newspaper',
            'faq' => 'fa-circle-question',
            'gallery' => 'fa-images',
            'constituencies' => 'fa-map-location-dot',
        ][$type] ?? 'fa-magnifying-glass';
    }

    private static function dateLabel(string $date): string
    {
        if ($date === '') {
            return '';
        }
        $time = strtotime($date);
        return $time ? date('j M Y', $time) : '';
    }

    private static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}

<?php
class MediaLibrary extends Model
{
    protected static string $table = 'media_library';

    public const FOLDERS = [
        'backgrounds' => 'Backgrounds',
        'banners' => 'Banners',
        'favicons' => 'Favicons',
        'gallery' => 'Gallery Images',
        'gallery-thumbnails' => 'Gallery Thumbnails',
        'gallery-videos' => 'Gallery Videos',
        'heroes' => 'Hero Images',
        'icons' => 'Icons',
        'leadership' => 'Leadership Photos',
        'logos' => 'Logos & Branding',
        'news' => 'News Images',
        'partners' => 'Partner Logos',
        'placeholders' => 'Placeholders',
        'profiles' => 'Profile Photos',
        'projects' => 'Project Photos',
        'publications' => 'Publications',
        'site-photos' => 'Site Photos',
        'tenders' => 'Tender Documents',
        'videos' => 'Videos',
        'cms' => 'CMS Assets',
        'general' => 'General Files',
    ];

    public static function folders(): array
    {
        return self::FOLDERS;
    }

    public static function normaliseFolder(string $folder): string
    {
        $folder = preg_replace('/[^a-z0-9_-]+/', '', strtolower(trim($folder))) ?: 'general';
        return array_key_exists($folder, self::FOLDERS) ? $folder : 'general';
    }

    public static function typeGroup(string $mime, string $extension = ''): string
    {
        $mime = strtolower($mime);
        $extension = strtolower($extension);
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/') || in_array($extension, ['mp4', 'webm', 'mov'], true)) {
            return 'video';
        }
        if ($mime === 'application/pdf' || $extension === 'pdf') {
            return 'pdf';
        }
        return 'document';
    }

    public static function stats(): array
    {
        $row = Database::fetch("
            SELECT
                COUNT(*) AS total_files,
                SUM(CASE WHEN type LIKE 'image/%' THEN 1 ELSE 0 END) AS total_images,
                SUM(CASE WHEN type LIKE 'video/%' THEN 1 ELSE 0 END) AS total_videos,
                SUM(CASE WHEN type = 'application/pdf' OR extension = 'pdf' THEN 1 ELSE 0 END) AS total_pdfs,
                COALESCE(SUM(size), 0) AS total_size
            FROM media_library
        ") ?: [];

        return array_map(static fn ($value) => (int)$value, $row);
    }

    public static function query(array $filters = [], int $limit = 48, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        return Database::fetchAll(
            self::selectSql() . $where . ' GROUP BY ml.id ORDER BY ml.created_at DESC, ml.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    public static function count(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch('SELECT COUNT(*) AS total FROM media_library ml ' . $where, $bindings);
        return (int)($row['total'] ?? 0);
    }

    public static function findDetailed(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE ml.id = ? GROUP BY ml.id LIMIT 1', [$id]);
    }

    public static function usage(int $mediaId): array
    {
        return Database::fetchAll(
            'SELECT * FROM media_usage WHERE media_id = ? ORDER BY created_at DESC',
            [$mediaId]
        );
    }

    public static function syncUploads(): array
    {
        $root = dirname(__DIR__, 2);
        $uploads = $root . DIRECTORY_SEPARATOR . 'uploads';
        $inserted = 0;
        $skipped = 0;
        $scanned = 0;

        foreach (self::folders() as $folder => $label) {
            $dir = $uploads . DIRECTORY_SEPARATOR . $folder;
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $scanned++;
                $extension = strtolower($file->getExtension());
                if (!in_array($extension, self::allowedExtensions(), true)) {
                    $skipped++;
                    continue;
                }

                $absolute = $file->getPathname();
                $relative = str_replace('\\', '/', substr($absolute, strlen($root) + 1));
                if (Database::fetch('SELECT id FROM media_library WHERE path = ? LIMIT 1', [$relative])) {
                    $skipped++;
                    continue;
                }

                $image = @getimagesize($absolute);
                $mime = $image ? (string)$image['mime'] : (mime_content_type($absolute) ?: self::mimeFromExtension($extension));
                Database::query(
                    'INSERT INTO media_library
                        (filename, original_name, title, path, url, type, size, width, height, extension, alt_text, uploaded_by, folder, source)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $file->getFilename(),
                        $file->getFilename(),
                        self::titleFromFilename($file->getBasename('.' . $extension)),
                        $relative,
                        Url::asset($relative),
                        $mime,
                        (int)$file->getSize(),
                        $image ? (int)$image[0] : null,
                        $image ? (int)$image[1] : null,
                        $extension,
                        self::titleFromFilename($file->getBasename('.' . $extension)),
                        (int)(Auth::id() ?: 1),
                        $folder,
                        'filesystem_scan',
                    ]
                );
                $inserted++;
            }
        }

        return ['scanned' => $scanned, 'inserted' => $inserted, 'skipped' => $skipped];
    }

    public static function allowedExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'mp4', 'webm', 'mov'];
    }

    public static function titleFromFilename(string $name): string
    {
        return ucwords(trim(str_replace(['-', '_'], ' ', $name))) ?: 'Untitled media';
    }

    private static function selectSql(): string
    {
        return "
            SELECT ml.*,
                   CONCAT(u.first_name, ' ', u.last_name) AS uploaded_by_name,
                   COUNT(mu.id) AS usage_count
            FROM media_library ml
            LEFT JOIN users u ON u.id = ml.uploaded_by
            LEFT JOIN media_usage mu ON mu.media_id = ml.id
        ";
    }

    private static function filterSql(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['folder'])) {
            $where[] = 'ml.folder = ?';
            $bindings[] = self::normaliseFolder((string)$filters['folder']);
        }

        if (!empty($filters['type'])) {
            $type = (string)$filters['type'];
            if ($type === 'image') {
                $where[] = "ml.type LIKE 'image/%'";
            } elseif ($type === 'video') {
                $where[] = "(ml.type LIKE 'video/%' OR ml.extension IN ('mp4','webm','mov'))";
            } elseif ($type === 'pdf') {
                $where[] = "(ml.type = 'application/pdf' OR ml.extension = 'pdf')";
            } elseif ($type === 'document') {
                $where[] = "(ml.type NOT LIKE 'image/%' AND ml.type NOT LIKE 'video/%' AND COALESCE(ml.extension, '') <> 'pdf')";
            }
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(ml.title LIKE ? OR ml.filename LIKE ? OR ml.original_name LIKE ? OR ml.alt_text LIKE ? OR ml.caption LIKE ? OR ml.folder LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function mimeFromExtension(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }
}

<?php

class Migration_156_PublicGalleryCurrentRecords
{
    public function up(\PDO $pdo): void
    {
        $rows = $pdo->query("
            SELECT gi.id AS gallery_id, gm.media_id, gi.title
            FROM gallery_images gi
            INNER JOIN gallery_media gm ON gm.gallery_id = gi.id
            INNER JOIN media_library ml ON ml.id = gm.media_id
            WHERE COALESCE(ml.url, '') LIKE 'https://picsum.photos/%'
               OR COALESCE(ml.path, '') LIKE 'https://picsum.photos/%'
            ORDER BY gi.id ASC, gm.sort_order ASC, gm.id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        if (!$rows) {
            return;
        }

        $assets = [
            ['uploads/gallery/maili-tatu-1.jpg', 'maili-tatu-1.jpg', 'image/jpeg'],
            ['uploads/gallery/maili-tatu-2.jpg', 'maili-tatu-2.jpg', 'image/jpeg'],
            ['uploads/gallery/maili-tatu-3.jpg', 'maili-tatu-3.jpg', 'image/jpeg'],
            ['uploads/gallery/kitale-ex-prison.jpeg', 'kitale-ex-prison.jpeg', 'image/jpeg'],
            ['uploads/gallery/suam-ahp.jpg', 'suam-ahp.jpg', 'image/jpeg'],
            ['uploads/gallery/matunda-ahp-1.jpg', 'matunda-ahp-1.jpg', 'image/jpeg'],
            ['uploads/gallery/matunda-ahp-2.jpg', 'matunda-ahp-2.jpg', 'image/jpeg'],
            ['uploads/gallery/modern-market.jpg', 'modern-market.jpg', 'image/jpeg'],
        ];

        $dates = [
            '2026-01-15',
            '2026-01-30',
            '2026-02-12',
            '2026-02-28',
            '2026-03-14',
            '2026-03-29',
            '2026-04-10',
            '2026-04-25',
            '2026-05-06',
            '2026-05-18',
            '2026-05-26',
            '2026-06-03',
        ];

        $updateMedia = $pdo->prepare("
            UPDATE media_library
            SET path = ?,
                url = ?,
                filename = ?,
                original_name = ?,
                title = COALESCE(NULLIF(title, ''), ?),
                alt_text = COALESCE(NULLIF(alt_text, ''), ?),
                type = ?,
                extension = ?
            WHERE id = ?
        ");

        $updateGallery = $pdo->prepare("
            UPDATE gallery_images
            SET year = 2026,
                taken_at = ?,
                caption = COALESCE(NULLIF(caption, ''), title),
                alt_text = COALESCE(NULLIF(alt_text, ''), title),
                status = 'published'
            WHERE id = ?
        ");

        foreach ($rows as $index => $row) {
            $asset = $assets[$index % count($assets)];
            $date = $dates[$index % count($dates)];
            $title = trim((string)($row['title'] ?? 'AHP project progress'));
            $filename = $asset[1];
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            $updateMedia->execute([
                $asset[0],
                $asset[0],
                $filename,
                $filename,
                $title,
                $title,
                $asset[2],
                $extension,
                (int)$row['media_id'],
            ]);

            $updateGallery->execute([$date, (int)$row['gallery_id']]);
        }
    }

    public function down(\PDO $pdo): void
    {
        // Data-normalisation migration; no destructive rollback.
    }
}

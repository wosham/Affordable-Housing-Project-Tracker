<?php

class FrontendContentSeeder
{
    private int $systemUserId = 1;
    private array $mediaCache = [];

    public function run(\PDO $pdo): void
    {
        $this->systemUserId = (int)($pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);
        $this->cleanupLegacyDemoRows($pdo);

        $site = $this->dataFile('site.php');
        $projects = $this->dataFile('projects.php');
        $constituencies = $this->dataFile('constituencies.php');
        $news = $this->dataFile('news.php');

        $this->seedSettings($pdo, $site);
        $this->seedNavigation($pdo, $site);
        $this->seedConstituencies($pdo, $constituencies);
        $this->seedProjects($pdo, $projects);
        $this->seedNews($pdo, $news);
        $this->seedFaqFromPage($pdo);
        $this->seedGalleryFromPage($pdo);
        $this->seedLeadershipFromPage($pdo);
        $this->seedStakeholders($pdo);
        $this->seedContactDepartments($pdo);
        $this->seedCmsSnapshots($pdo, $site);
        $this->seedAnnouncements($pdo, $news);

        echo "✓ Frontend content imported into backend tables.\n";
    }

    private function dataFile(string $file): array
    {
        $path = dirname(__DIR__, 2) . '/data/' . $file;
        return file_exists($path) ? require $path : [];
    }

    private function cleanupLegacyDemoRows(\PDO $pdo): void
    {
        $demoProjectSlugs = [
            'cherangany-affordable-housing-project',
            'saboti-affordable-housing-project',
            'kiminini-affordable-housing-project',
            'kwanza-affordable-housing-project',
            'endebess-affordable-housing-project',
            'trans-nzoia-west-affordable-housing-project',
            'trans-nzoia-east-affordable-housing-project',
        ];

        $placeholders = implode(',', array_fill(0, count($demoProjectSlugs), '?'));
        $projectIds = $pdo->prepare("SELECT id FROM projects WHERE slug IN ({$placeholders})");
        $projectIds->execute($demoProjectSlugs);
        $ids = array_map('intval', $projectIds->fetchAll(\PDO::FETCH_COLUMN));

        if ($ids) {
            $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));
            foreach (['milestones', 'boq_items', 'project_assignments', 'programme_tasks'] as $table) {
                $pdo->prepare("DELETE FROM {$table} WHERE project_id IN ({$idPlaceholders})")->execute($ids);
            }
            $pdo->prepare("DELETE FROM projects WHERE id IN ({$idPlaceholders})")->execute($ids);
        }

        $pdo->prepare("DELETE FROM constituencies WHERE slug IN (?, ?)")->execute(['trans-nzoia-east', 'trans-nzoia-west']);
        $pdo->exec("DELETE FROM wards");
        $pdo->exec("DELETE FROM stakeholders");
    }

    private function rootFile(string $file): string
    {
        return dirname(__DIR__, 3) . '/' . ltrim($file, '/');
    }

    private function seedSettings(\PDO $pdo, array $site): void
    {
        $settings = [
            ['site_name', $site['site_name'] ?? '', 'text', 'Site Name', 'global'],
            ['site_short_name', $site['short_name'] ?? '', 'text', 'Short Name', 'global'],
            ['site_brand_label', $site['brand_label'] ?? '', 'text', 'Brand Label', 'global'],
            ['county_name', $site['county_name'] ?? '', 'text', 'County Name', 'global'],
            ['department_name', $site['department_name'] ?? '', 'text', 'Department Name', 'global'],
            ['base_url', $site['base_url'] ?? '', 'text', 'Base URL', 'global'],
            ['theme_color', $site['theme_color'] ?? '', 'text', 'Theme Color', 'global'],
            ['contact_phone', $site['contact']['phone'] ?? '', 'text', 'Contact Phone', 'contact'],
            ['contact_email', $site['contact']['email'] ?? '', 'text', 'Contact Email', 'contact'],
            ['contact_office', $site['contact']['office'] ?? '', 'text', 'Office', 'contact'],
            ['contact_postal_address', $site['contact']['postal_address'] ?? '', 'text', 'Postal Address', 'contact'],
            ['asset_logo', $site['assets']['logo'] ?? '', 'text', 'Logo', 'assets'],
            ['asset_boma_yangu_logo', $site['assets']['boma_yangu_logo'] ?? '', 'text', 'Boma Yangu Logo', 'assets'],
            ['asset_favicon', $site['assets']['favicon'] ?? '', 'text', 'Favicon', 'assets'],
            ['asset_hero_main', $site['assets']['hero_main'] ?? '', 'text', 'Main Hero Image', 'assets'],
            ['external_ecitizen', $site['external_links']['ecitizen'] ?? '', 'text', 'eCitizen URL', 'links'],
            ['external_boma_yangu', $site['external_links']['boma_yangu'] ?? '', 'text', 'Boma Yangu URL', 'links'],
            ['frontend_stats_json', json_encode($site['stats'] ?? [], JSON_UNESCAPED_SLASHES), 'json', 'Frontend Stats', 'stats'],
            ['ticker_items_json', json_encode($site['ticker_items'] ?? [], JSON_UNESCAPED_SLASHES), 'json', 'Ticker Items', 'content'],
            ['page_meta_json', json_encode($site['page_meta'] ?? [], JSON_UNESCAPED_SLASHES), 'json', 'Page Metadata', 'seo'],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO cms_settings (`key`, value, type, label, `group`)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value), type = VALUES(type), label = VALUES(label), `group` = VALUES(`group`)
        ");

        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
    }

    private function seedNavigation(\PDO $pdo, array $site): void
    {
        $stmt = $pdo->prepare("
            INSERT INTO navigation_links (area, label, href, page_key, is_external, sort_order, is_visible)
            VALUES (?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE label = VALUES(label), page_key = VALUES(page_key), is_external = VALUES(is_external),
                                    sort_order = VALUES(sort_order), is_visible = 1
        ");

        foreach (['navigation' => 'main', 'mobile_navigation' => 'mobile', 'legal_navigation' => 'legal'] as $key => $area) {
            $i = 1;
            foreach (($site[$key] ?? []) as $link) {
                $stmt->execute([$area, $link['label'], $link['href'], $link['id'] ?? null, 0, $i++]);
            }
        }

        foreach (($site['footer_columns'] ?? []) as $column => $links) {
            $i = 1;
            foreach ($links as $link) {
                $stmt->execute(['footer_' . strtolower(str_replace(' ', '_', $column)), $link['label'], $link['href'], null, 0, $i++]);
            }
        }
    }

    private function seedConstituencies(\PDO $pdo, array $constituencies): void
    {
        $stmt = $pdo->prepare("
            INSERT INTO constituencies
                (name, slug, description, population, total_units, total_projects, avg_completion, status, hero_image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name), description = VALUES(description), population = VALUES(population),
                total_units = VALUES(total_units), total_projects = VALUES(total_projects),
                avg_completion = VALUES(avg_completion), status = VALUES(status), hero_image = VALUES(hero_image)
        ");

        $wardStmt = $pdo->prepare("
            INSERT IGNORE INTO wards (constituency_id, name, slug)
            VALUES (?, ?, ?)
        ");

        foreach ($constituencies as $item) {
            $stmt->execute([
                $item['name'],
                $item['id'],
                $item['description'] ?? null,
                (int)str_replace([',', '~'], '', (string)($item['population'] ?? 0)),
                (int)($item['totalUnits'] ?? 0),
                (int)($item['projectCount'] ?? 0),
                (int)($item['avgCompletion'] ?? 0),
                $item['status'] ?? 'planning',
                $item['heroImage'] ?? null,
            ]);

            $cid = $this->idBySlug($pdo, 'constituencies', $item['id']);
            foreach (($item['wards'] ?? []) as $ward) {
                $wardStmt->execute([$cid, $ward, $this->slug($ward)]);
            }
        }
    }

    private function seedProjects(\PDO $pdo, array $projects): void
    {
        $catId = $this->ensureProjectCategory($pdo);

        $stmt = $pdo->prepare("
            INSERT INTO projects
                (category_id, constituency_id, ward_id, name, slug, location_label, status, pct_complete,
                 start_date, est_delivery, current_milestone, contractor_name, contractor_id, consultant_id, description,
                 hero_image, images_json, funding_source, lead_agency, site_engineer, units, is_featured)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                 category_id = VALUES(category_id), constituency_id = VALUES(constituency_id), ward_id = VALUES(ward_id),
                 location_label = VALUES(location_label), status = VALUES(status), pct_complete = VALUES(pct_complete),
                 current_milestone = VALUES(current_milestone), contractor_name = VALUES(contractor_name), description = VALUES(description),
                 hero_image = VALUES(hero_image), images_json = VALUES(images_json), funding_source = VALUES(funding_source),
                 lead_agency = VALUES(lead_agency), site_engineer = VALUES(site_engineer), units = VALUES(units),
                 is_featured = VALUES(is_featured)
        ");

        foreach ($projects as $index => $project) {
            $cid = $this->idBySlug($pdo, 'constituencies', $project['constituency']);
            $wardId = $this->wardId($pdo, $cid, $project['ward'] ?? null);
            $images = $project['images'] ?? [];
            $hero = $images[0] ?? null;
            foreach ($images as $image) {
                $this->ensureMedia($pdo, $image, $project['name']);
            }

            $stmt->execute([
                $catId,
                $cid,
                $wardId,
                $project['name'],
                $project['id'],
                trim(($project['constituencyName'] ?? '') . ', ' . ($project['ward'] ?? ''), ' ,'),
                $this->mapProjectStatus($project['status'] ?? 'planning'),
                (int)($project['pct'] ?? 0),
                $this->dateOrNull($project['startDate'] ?? null),
                $this->dateOrNull($project['estDelivery'] ?? null),
                $project['milestone'] ?? null,
                $project['contractor'] ?? null,
                $project['description'] ?? null,
                $hero,
                json_encode($images, JSON_UNESCAPED_SLASHES),
                $project['funding'] ?? null,
                $project['leadAgency'] ?? null,
                $project['siteEngineer'] ?? null,
                (int)($project['units'] ?? 0),
                $index < 3 ? 1 : 0,
            ]);

            $this->seedProjectMilestones($pdo, $project['id'], $project['milestones'] ?? []);
        }
    }

    private function seedProjectMilestones(\PDO $pdo, string $projectSlug, array $milestones): void
    {
        $projectId = $this->idBySlug($pdo, 'projects', $projectSlug);
        $delete = $pdo->prepare("DELETE FROM milestones WHERE project_id = ?");
        $delete->execute([$projectId]);

        $stmt = $pdo->prepare("
            INSERT INTO milestones (project_id, label, status, target_date, sequence)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($milestones as $i => $milestone) {
            $stmt->execute([
                $projectId,
                $milestone['label'] ?? ('Milestone ' . ($i + 1)),
                ($milestone['done'] ?? false) ? 'done' : 'pending',
                $this->dateOrNull($milestone['date'] ?? null),
                $i + 1,
            ]);
        }
    }

    private function seedNews(\PDO $pdo, array $news): void
    {
        $categoryIds = [];
        $catStmt = $pdo->prepare("
            INSERT INTO news_categories (name, slug, sort_order)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order)
        ");
        $catOrder = 1;
        $frontendCategorySlugs = [];
        foreach (($news['categories'] ?? []) as $slug => $label) {
            if (is_array($label)) {
                $slug = $label['id'] ?? $label['slug'] ?? $slug;
                $label = $label['label'] ?? $label['name'] ?? $slug;
            }
            $frontendCategorySlugs[] = $slug;
            $catStmt->execute([$label, $slug, $catOrder++]);
            $categoryIds[$slug] = $this->idBySlug($pdo, 'news_categories', $slug);
        }
        if ($frontendCategorySlugs) {
            $placeholders = implode(',', array_fill(0, count($frontendCategorySlugs), '?'));
            $pdo->prepare("DELETE FROM news_categories WHERE slug NOT IN ({$placeholders})")->execute($frontendCategorySlugs);
        }

        $articles = $news['articles'] ?? [];
        if (!empty($news['featured_article'])) {
            array_unshift($articles, $news['featured_article']);
        } elseif (!empty($news['featured'])) {
            array_unshift($articles, $news['featured']);
        }

        $articleStmt = $pdo->prepare("
            INSERT INTO news_articles
                (category_id, author_id, title, slug, excerpt, read_time, body, featured_image_id,
                 image_caption, is_featured, status, published_at, seo_title, seo_description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                 category_id = VALUES(category_id), title = VALUES(title), excerpt = VALUES(excerpt),
                 read_time = VALUES(read_time), body = VALUES(body), featured_image_id = VALUES(featured_image_id),
                 image_caption = VALUES(image_caption), is_featured = VALUES(is_featured),
                 status = VALUES(status), published_at = VALUES(published_at),
                 seo_title = VALUES(seo_title), seo_description = VALUES(seo_description)
        ");

        foreach ($articles as $article) {
            $mediaId = $this->ensureMedia($pdo, $article['image'] ?? '', $article['imageAlt'] ?? $article['title']);
            $catId = $categoryIds[$article['category'] ?? ''] ?? null;
            $articleStmt->execute([
                $catId,
                $this->systemUserId,
                $article['title'],
                $article['slug'] ?? $article['id'],
                $article['excerpt'] ?? '',
                $article['readTime'] ?? null,
                $article['body'] ?? ($article['excerpt'] ?? ''),
                $mediaId,
                $article['caption'] ?? null,
                !empty($article['featured']) || (($news['featured_article']['id'] ?? $news['featured']['id'] ?? null) === ($article['id'] ?? null)) ? 1 : 0,
                ($article['date'] ?? date('Y-m-d')) . ' 08:00:00',
                $article['title'],
                $article['excerpt'] ?? '',
            ]);
            $this->seedNewsTags($pdo, $article['slug'] ?? $article['id'], $article['tags'] ?? []);
        }
    }

    private function seedNewsTags(\PDO $pdo, string $articleSlug, array $tags): void
    {
        if (!$tags) {
            return;
        }

        $articleId = $this->idBySlug($pdo, 'news_articles', $articleSlug);
        $tagStmt = $pdo->prepare("INSERT IGNORE INTO news_tags (name, slug) VALUES (?, ?)");
        $pivotStmt = $pdo->prepare("INSERT IGNORE INTO news_article_tags (article_id, tag_id) VALUES (?, ?)");

        foreach ($tags as $tag) {
            $slug = $this->slug($tag);
            $tagStmt->execute([$tag, $slug]);
            $pivotStmt->execute([$articleId, $this->idBySlug($pdo, 'news_tags', $slug)]);
        }
    }

    private function seedFaqFromPage(\PDO $pdo): void
    {
        $html = file_get_contents($this->rootFile('faq.php')) ?: '';
        preg_match_all('/<div class="fq-item"[^>]*data-cat="([^"]+)"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/s', $html, $matches, PREG_SET_ORDER);

        $stmt = $pdo->prepare("
            INSERT INTO faq_items (question, answer, category, sort_order, is_visible)
            VALUES (?, ?, ?, ?, 1)
        ");
        $pdo->exec("DELETE FROM faq_items");

        $i = 1;
        foreach ($matches as $match) {
            if (!preg_match('/<span class="fq-q-text">(.*?)<\/span>/s', $match[2], $question)) {
                continue;
            }
            if (!preg_match('/<div class="fq-panel-inner">(.*?)(?:<div class="fq-helpful"|$)/s', $match[2], $answer)) {
                continue;
            }

            $stmt->execute([
                trim(html_entity_decode(strip_tags($question[1]), ENT_QUOTES, 'UTF-8')),
                trim($answer[1]),
                $match[1],
                $i++,
            ]);
        }
    }

    private function seedGalleryFromPage(\PDO $pdo): void
    {
        $html = file_get_contents($this->rootFile('gallery.php')) ?: '';
        preg_match_all('/<button class="gl-photo-item"[^>]*data-cat="([^"]+)"[^>]*data-year="([^"]+)"[^>]*data-site="([^"]+)"[^>]*>.*?<img src="([^"]+)" alt="([^"]*)"/s', $html, $matches, PREG_SET_ORDER);

        $pdo->exec("DELETE FROM gallery_images");
        $pdo->exec("DELETE FROM gallery_categories");

        $catStmt = $pdo->prepare("
            INSERT INTO gallery_categories (name, slug, sort_order)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order)
        ");
        $imageStmt = $pdo->prepare("
            INSERT INTO gallery_images (category_id, image_id, title, caption, taken_at, location, site_key, year, media_type, sort_order)
            VALUES (?, ?, ?, ?, NULL, ?, ?, ?, 'image', ?)
            ON DUPLICATE KEY UPDATE title = VALUES(title), caption = VALUES(caption), site_key = VALUES(site_key), year = VALUES(year)
        ");

        $seenCats = [];
        foreach ($matches as $i => $match) {
            [$all, $cat, $year, $site, $src, $alt] = $match;
            if (!isset($seenCats[$cat])) {
                $catStmt->execute([ucwords(str_replace('-', ' ', $cat)), $cat, count($seenCats) + 1]);
                $seenCats[$cat] = $this->idBySlug($pdo, 'gallery_categories', $cat);
            }
            $mediaId = $this->ensureMedia($pdo, $src, html_entity_decode($alt, ENT_QUOTES, 'UTF-8'));
            $imageStmt->execute([
                $seenCats[$cat],
                $mediaId,
                html_entity_decode($alt, ENT_QUOTES, 'UTF-8'),
                html_entity_decode($alt, ENT_QUOTES, 'UTF-8'),
                ucwords(str_replace('-', ' ', $site)),
                $site,
                (int)$year,
                $i + 1,
            ]);
        }
    }

    private function seedLeadershipFromPage(\PDO $pdo): void
    {
        $html = file_get_contents($this->rootFile('leadership.php')) ?: '';
        preg_match_all('/<h3 class="ld-(?:national|contractor|partner)-name">(.*?)<\/h3>(?:\s*<p class="ld-[^"]+">(.*?)<\/p>)?/s', $html, $matches, PREG_SET_ORDER);

        $stmt = $pdo->prepare("
            INSERT INTO leadership_profiles (name, title, organisation, bio, sort_order, is_visible)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $pdo->exec("DELETE FROM leadership_profiles");

        foreach ($matches as $i => $match) {
            $name = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES, 'UTF-8'));
            $bio = trim(html_entity_decode(strip_tags($match[2] ?? ''), ENT_QUOTES, 'UTF-8'));
            $stmt->execute([$name, 'Programme Leadership', 'AHPTC', $bio, $i + 1]);
        }
    }

    private function seedStakeholders(\PDO $pdo): void
    {
        $stakeholders = [
            ['State Department of Housing', 'National Government', 'Programme lead and secretariat', 'https://www.housing.go.ke'],
            ['Affordable Housing Board', 'National Government', 'Statutory fund and levy administrator', 'https://www.affordablehousing.go.ke'],
            ['Trans-Nzoia County Government', 'County Government', 'Land allocation, local facilitation and citizen engagement', 'https://transnzoia.go.ke'],
            ['National Construction Authority', 'Oversight & Regulation', 'Contractor registration and site standards', 'https://www.nca.go.ke'],
            ['NEMA', 'Oversight & Regulation', 'Environmental impact assessment and compliance', 'https://www.nema.go.ke'],
            ['Kenya Mortgage Refinance Company', 'Financial Partners', 'Mortgage refinancing and subsidy support', 'https://www.kmrc.co.ke'],
            ['Boma Yangu / eCitizen', 'Financial Partners', 'Application, savings and ballot platform', 'https://www.bomayangu.go.ke'],
            ['Community & Beneficiaries', 'Community & Beneficiaries', 'Applicants, ward committees and resident representatives', null],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO stakeholders (organisation, category, role, description, website, sort_order, is_visible)
            VALUES (?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE category = VALUES(category), role = VALUES(role), description = VALUES(description),
                                    website = VALUES(website), sort_order = VALUES(sort_order), is_visible = 1
        ");

        foreach ($stakeholders as $i => $s) {
            $stmt->execute([$s[0], $s[1], $s[2], $s[2], $s[3], $i + 1]);
        }
    }

    private function seedContactDepartments(\PDO $pdo): void
    {
        $departments = [
            ['Field Operations', 'field_operations', 'Construction progress, site visits, contractor oversight', 'fieldops@transnzoia.go.ke', '+254 53 000 0001', 'fa-hard-hat', 'a'],
            ['Legal & Allocation', 'legal_allocation', 'Applications, balloting, title deeds, legal enquiries', 'legal@transnzoia.go.ke', '+254 53 000 0002', 'fa-scale-balanced', 'b'],
            ['Finance & Levy', 'finance_levy', 'Housing Levy, mortgage, refunds, payment queries', 'finance@transnzoia.go.ke', '+254 53 000 0003', 'fa-coins', 'c'],
            ['Communications', 'communications', 'Media, press, events, public announcements', 'comms@transnzoia.go.ke', '+254 53 000 0004', 'fa-bullhorn', 'd'],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO contact_departments (name, subject_key, role, email, phone, icon, accent, sort_order, is_visible)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE subject_key = VALUES(subject_key), role = VALUES(role), email = VALUES(email),
                                    phone = VALUES(phone), icon = VALUES(icon), accent = VALUES(accent),
                                    sort_order = VALUES(sort_order), is_visible = 1
        ");

        foreach ($departments as $i => $department) {
            $stmt->execute([$department[0], $department[1], $department[2], $department[3], $department[4], $department[5], $department[6], $i + 1]);
        }
    }

    private function seedCmsSnapshots(\PDO $pdo, array $site): void
    {
        $files = [
            'home' => 'index.php',
            'about' => 'about.php',
            'projects' => 'projects.php',
            'project-detail' => 'project-detail.php',
            'constituencies' => 'constituencies.php',
            'constituency-detail' => 'constituency-detail.php',
            'news' => 'news.php',
            'news-article' => 'news-article.php',
            'gallery' => 'gallery.php',
            'faq' => 'faq.php',
            'leadership' => 'leadership.php',
            'stakeholders' => 'stakeholders.php',
            'contact' => 'contact.php',
            'sitemap' => 'sitemap.php',
            'privacy' => 'legal/privacy.php',
            'terms' => 'legal/terms.php',
            'disclaimer' => 'legal/disclaimer.php',
            'not-found' => '404.php',
            'server-error' => '500.php',
            'maintenance' => 'maintenance.php',
            'offline' => 'offline.php',
        ];

        $pageStmt = $pdo->prepare("
            INSERT INTO cms_pages (slug, status, seo_title, seo_description, seo_keywords, canonical_url)
            VALUES (?, 'published', ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE seo_title = VALUES(seo_title), seo_description = VALUES(seo_description),
                                    seo_keywords = VALUES(seo_keywords), canonical_url = VALUES(canonical_url)
        ");
        $sectionStmt = $pdo->prepare("
            INSERT INTO cms_sections (page_id, section_key, label, is_visible, content_json)
            VALUES (?, 'source_snapshot', 'Frontend Source Snapshot', 1, ?)
            ON DUPLICATE KEY UPDATE content_json = VALUES(content_json), is_visible = 1
        ");

        foreach ($files as $slug => $file) {
            $path = $this->rootFile($file);
            if (!file_exists($path)) {
                continue;
            }

            $meta = $site['page_meta'][$slug] ?? [];
            $source = file_get_contents($path) ?: '';
            $pageStmt->execute([
                $slug,
                $meta['title'] ?? ucwords(str_replace('-', ' ', $slug)),
                $meta['description'] ?? null,
                $meta['keywords'] ?? null,
                $meta['canonical'] ?? null,
            ]);

            $pageId = $this->idBySlug($pdo, 'cms_pages', $slug);
            $sectionStmt->execute([$pageId, json_encode([
                'source_file' => $file,
                'headings' => $this->extractHeadings($source),
                'html_snapshot' => $source,
            ], JSON_UNESCAPED_SLASHES)]);
        }
    }

    private function seedAnnouncements(\PDO $pdo, array $news): void
    {
        $stmt = $pdo->prepare("
            INSERT INTO announcements (author_id, title, body, type, status, is_pinned, published_at)
            VALUES (?, ?, ?, ?, 'published', ?, NOW())
            ON DUPLICATE KEY UPDATE body = VALUES(body), type = VALUES(type), status = VALUES(status), is_pinned = VALUES(is_pinned)
        ");

        foreach (($news['home_alerts'] ?? []) as $i => $alert) {
            $stmt->execute([
                $this->systemUserId,
                $alert['title'] ?? $alert['label'] ?? 'Programme Alert',
                $alert['text'] ?? $alert['title'] ?? '',
                strtolower($alert['label'] ?? 'info'),
                $i === 0 ? 1 : 0,
            ]);
        }
    }

    private function ensureMedia(\PDO $pdo, ?string $path, string $alt = ''): ?int
    {
        if (!$path) {
            return null;
        }
        if (isset($this->mediaCache[$path])) {
            return $this->mediaCache[$path];
        }

        $lookup = $pdo->prepare("SELECT id FROM media_library WHERE path = ? OR url = ? LIMIT 1");
        $lookup->execute([$path, $path]);
        $existingId = (int)$lookup->fetchColumn();
        if ($existingId) {
            $this->mediaCache[$path] = $existingId;
            return $existingId;
        }

        $filename = basename(parse_url($path, PHP_URL_PATH) ?: $path);
        if ($filename === '' || $filename === '/' || !str_contains($filename, '.')) {
            $filename = 'frontend-' . substr(sha1($path), 0, 12) . '.jpg';
        }

        $stmt = $pdo->prepare("
            INSERT INTO media_library (filename, original_name, path, url, type, size, alt_text, uploaded_by, folder)
            VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?)
            ON DUPLICATE KEY UPDATE alt_text = VALUES(alt_text), folder = VALUES(folder)
        ");

        $folder = str_contains($path, 'news') ? 'news' : (str_contains($path, 'gallery') || str_contains($path, 'picsum') ? 'gallery' : 'site');
        $stmt->execute([$filename, $filename, $path, $path, $this->mediaType($filename), $alt, $this->systemUserId, $folder]);

        $id = (int)$pdo->lastInsertId();
        if (!$id) {
            $lookup->execute([$path, $path]);
            $id = (int)$lookup->fetchColumn();
        }
        $this->mediaCache[$path] = $id ?: null;

        return $this->mediaCache[$path];
    }

    private function ensureProjectCategory(\PDO $pdo): int
    {
        $pdo->prepare("
            INSERT IGNORE INTO project_categories (name, slug, icon, color)
            VALUES ('Affordable Housing', 'affordable-housing', 'fa-house-chimney-window', '#163300')
        ")->execute();

        return $this->idBySlug($pdo, 'project_categories', 'affordable-housing');
    }

    private function idBySlug(\PDO $pdo, string $table, string $slug): int
    {
        $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return (int)$stmt->fetchColumn();
    }

    private function wardId(\PDO $pdo, int $constituencyId, ?string $name): ?int
    {
        if (!$name) {
            return null;
        }

        $slug = $this->slug($name);
        $stmt = $pdo->prepare("SELECT id FROM wards WHERE constituency_id = ? AND slug = ? LIMIT 1");
        $stmt->execute([$constituencyId, $slug]);

        return (int)$stmt->fetchColumn() ?: null;
    }

    private function extractHeadings(string $html): array
    {
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/s', $html, $matches, PREG_SET_ORDER);
        return array_map(static fn ($m) => [
            'level' => (int)$m[1],
            'text' => trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES, 'UTF-8')),
        ], $matches);
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
        return $slug ?: 'item-' . substr(sha1($value), 0, 8);
    }

    private function dateOrNull(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        if (preg_match('/(20\d{2})/', $value, $match)) {
            return $match[1] . '-01-01';
        }
        return null;
    }

    private function mapProjectStatus(string $status): string
    {
        return match ($status) {
            'on-hold', 'on_hold' => 'on_hold',
            'cancelled' => 'cancelled',
            'completed' => 'completed',
            'active' => 'active',
            'stalled' => 'stalled',
            default => 'planning',
        };
    }

    private function mediaType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return match ($ext) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            default => 'image/jpeg',
        };
    }
}

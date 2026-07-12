<?php

class Migration_157_LeadershipPublicVisibilityCleanup
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            "UPDATE leadership_profiles
             SET show_in_org_chart = 0,
                 show_in_cards = 0,
                 show_in_spotlight = 0,
                 is_visible = 0,
                 status = 'draft'
             WHERE (slug IS NULL OR slug = '')
               AND title = 'Programme Leadership'
               AND organisation = 'AHPTC'"
        );

        $hierarchySlugs = [
            'william-ruto',
            'alice-wahome',
            'charles-hinga',
            'affordable-housing-board',
            'county-directors',
            'moses-owuor',
            'project-monitoring-officers',
            'site-supervisors',
            'community-liaison-officers',
        ];

        $placeholders = implode(',', array_fill(0, count($hierarchySlugs), '?'));
        $stmt = $pdo->prepare(
            "UPDATE leadership_profiles
             SET show_in_org_chart = 1,
                 status = 'published',
                 is_visible = 1
             WHERE slug IN ({$placeholders})"
        );
        $stmt->execute($hierarchySlugs);

        $cardSlugs = [
            'william-ruto',
            'alice-wahome',
            'charles-hinga',
            'affordable-housing-board',
            'moses-owuor',
        ];

        $cardPlaceholders = implode(',', array_fill(0, count($cardSlugs), '?'));
        $stmt = $pdo->prepare(
            "UPDATE leadership_profiles
             SET show_in_cards = CASE WHEN slug IN ({$cardPlaceholders}) THEN 1 ELSE show_in_cards END"
        );
        $stmt->execute($cardSlugs);
    }

    public function down(PDO $pdo): void
    {
        // Visibility cleanup is intentionally not reversed.
    }
}

<?php

class Migration_158_LeadershipOrgChartCopyCleanup
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            "UPDATE leadership_profiles
             SET bio = REPLACE(bio, 'Boma Yangu', 'beneficiary coordination'),
                 quote = REPLACE(quote, 'Boma Yangu', 'beneficiary coordination'),
                 appointment_source = REPLACE(appointment_source, 'Boma Yangu', 'beneficiary coordination'),
                 responsibilities_json = REPLACE(responsibilities_json, 'Boma Yangu', 'beneficiary coordination')
             WHERE show_in_org_chart = 1
               AND status = 'published'"
        );
    }

    public function down(PDO $pdo): void
    {
        // Public copy cleanup is intentionally not reversed.
    }
}

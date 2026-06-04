<?php

class Migration_116_ReportFooterWording
{
    public function up(PDO $pdo): void
    {
        $old = 'Generated from live AHPTC database records. Verify figures against approved source documents before statutory filing.';
        $new = 'Generated from live AHPTC information. Verify figures against approved source documents before statutory filing.';

        $pdo->prepare(
            'UPDATE system_settings
             SET value = CASE WHEN value = ? THEN ? ELSE value END,
                 default_value = ?
             WHERE setting_key = ?'
        )->execute([$old, $new, $new, 'reports.footer_note']);
    }

    public function down(PDO $pdo): void
    {
        $old = 'Generated from live AHPTC database records. Verify figures against approved source documents before statutory filing.';
        $new = 'Generated from live AHPTC information. Verify figures against approved source documents before statutory filing.';

        $pdo->prepare(
            'UPDATE system_settings
             SET value = CASE WHEN value = ? THEN ? ELSE value END,
                 default_value = ?
             WHERE setting_key = ?'
        )->execute([$new, $old, $old, 'reports.footer_note']);
    }
}

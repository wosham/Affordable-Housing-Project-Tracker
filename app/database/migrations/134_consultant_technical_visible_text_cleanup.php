<?php

class Migration134ConsultantTechnicalVisibleTextCleanup
{
    public function up(PDO $pdo): void
    {
        $replacements = [
            'Demo seed: mobilisation and site setup' => 'Mobilisation and site setup',
            'Demo seed: foundation works' => 'Foundation works',
            'Demo seed: superstructure frame' => 'Superstructure frame',
            'Demo seed: roofing and finishes' => 'Roofing and finishes',
        ];

        $stmt = $pdo->prepare('UPDATE programme_tasks SET task_name = ? WHERE task_name = ?');
        foreach ($replacements as $old => $new) {
            $stmt->execute([$new, $old]);
        }

        $pdo->exec("
            UPDATE programme_tasks
            SET notes = 'Programme verification record.'
            WHERE notes LIKE '%Demo seed%' OR notes LIKE '%demo%' OR notes LIKE '%seed%'
        ");
    }

    public function down(PDO $pdo): void
    {
    }
}

<?php
class FAQSeeder
{
    public function run(\PDO $pdo): void
    {
        $faqs = [
            [1, 'What is the Trans-Nzoia Affordable Housing Programme?',
             'The programme is a county government initiative under the Affordable Housing Programme (AHP) to provide decent, affordable housing to residents of Trans-Nzoia County.',
             'General'],
            [2, 'Who is eligible to apply for affordable housing units?',
             'Kenyan citizens residing in Trans-Nzoia County who meet the income threshold set by the national government. Priority is given to civil servants, low-income earners, and persons with disabilities.',
             'Eligibility'],
            [3, 'How can I track the progress of a project near me?',
             'Visit the Projects section on this website and select your constituency to see all projects, their current status, and completion percentage.',
             'General'],
            [4, 'Who are the key stakeholders in this programme?',
             'The programme involves Trans-Nzoia County Government, the State Department for Housing and Urban Development (SDHUD), contractors, and supervising consultants.',
             'General'],
            [5, 'How are contractors selected for projects?',
             'Contractors are selected through competitive tendering processes in accordance with the Public Procurement and Asset Disposal Act.',
             'Procurement'],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO faq_items (sort_order, question, answer, category) VALUES (?, ?, ?, ?)");
        foreach ($faqs as $f) { $stmt->execute($f); }
        echo "✓ FAQ items seeded.\n";
    }
}

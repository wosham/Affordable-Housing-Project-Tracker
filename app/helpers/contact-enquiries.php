<?php

if (!function_exists('contact_enquiries_date')) {
    function contact_enquiries_date(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
}

if (!function_exists('contact_enquiries_stat')) {
    function contact_enquiries_stat(string $icon, mixed $value, string $label, string $trend, string $tone = ''): void
    {
        $class = $tone !== '' ? ' stat-widget--' . $tone : '';
        ?>
  <article class="stat-widget<?= Security::e($class) ?>">
    <span class="stat-widget__icon"><i class="fa-solid <?= Security::e($icon) ?>" aria-hidden="true"></i></span>
    <span class="stat-widget__body">
      <strong class="stat-widget__value"><?= Security::e(format_number($value)) ?></strong>
      <span class="stat-widget__label"><?= Security::e($label) ?></span>
      <small class="stat-widget__trend"><?= Security::e($trend) ?></small>
    </span>
  </article>
        <?php
    }
}

if (!function_exists('contact_enquiries_page_url')) {
    function contact_enquiries_page_url(string $path, array $filters, int $page): string
    {
        $query = array_filter(
            array_merge($filters, ['page' => $page]),
            static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null
        );
        $base = explode('?', $path, 2)[0];
        return Url::to($base . '?' . http_build_query($query));
    }
}

if (!function_exists('contact_enquiries_templates')) {
    /**
     * @return list<array{id:string,label:string,subject:string,body:string}>
     */
    function contact_enquiries_templates(): array
    {
        return [
            [
                'id' => 'ack',
                'label' => 'Acknowledgement',
                'subject' => 'Acknowledgement of your enquiry — Trans-Nzoia AHP',
                'body' => "Dear {name},\n\nThank you for contacting the Trans-Nzoia Affordable Housing Programme.\n\nWe have received your enquiry regarding \"{subject}\" and it has been assigned for follow-up. We will respond with further guidance shortly.\n\nKind regards,\nTrans-Nzoia AHP Team",
            ],
            [
                'id' => 'docs',
                'label' => 'Documents required',
                'subject' => 'Documents required for your AHP enquiry',
                'body' => "Dear {name},\n\nThank you for your enquiry on \"{subject}\".\n\nTo proceed, kindly share the following documents (where applicable):\n1. National ID / passport copy\n2. Proof of residence / constituency\n3. Any application reference number you hold\n\nYou may reply to this email with the attachments.\n\nKind regards,\nTrans-Nzoia AHP Team",
            ],
            [
                'id' => 'site',
                'label' => 'Refer to site office',
                'subject' => 'Next step — site office follow-up',
                'body' => "Dear {name},\n\nRegarding your enquiry \"{subject}\", please visit the relevant project site office during working hours for verification and further assistance. Bring your original identification documents.\n\nIf you need directions or a contact person, reply to this email and we will guide you.\n\nKind regards,\nTrans-Nzoia AHP Team",
            ],
            [
                'id' => 'closed',
                'label' => 'Resolved / closed',
                'subject' => 'Closure of your AHP enquiry',
                'body' => "Dear {name},\n\nWe are writing to confirm that your enquiry \"{subject}\" has been addressed from our side.\n\nIf you still require further assistance, please reply to this email within 7 days and we will reopen the matter.\n\nThank you for engaging with the Trans-Nzoia Affordable Housing Programme.\n\nKind regards,\nTrans-Nzoia AHP Team",
            ],
            [
                'id' => 'more_info',
                'label' => 'Request more information',
                'subject' => 'Additional information needed',
                'body' => "Dear {name},\n\nThank you for contacting us about \"{subject}\".\n\nTo assist you accurately, please provide more detail about your request (location/project, application status, and preferred contact time).\n\nKind regards,\nTrans-Nzoia AHP Team",
            ],
        ];
    }
}

if (!function_exists('contact_enquiries_unread_count')) {
    function contact_enquiries_unread_count(int $userId, string $role): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $filters = ContactSubmission::scopedFilters(['read_state' => 'unread'], $userId, $role);
        // Exclude archived from badge noise
        $filters['exclude_archived'] = 1;
        return ContactSubmission::countItems($filters);
    }
}

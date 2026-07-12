<?php

class ContactDepartment extends Model
{
    protected static string $table = 'contact_departments';

    public static function visible(): array
    {
        return Database::fetchAll(
            'SELECT id, name, COALESCE(subject_key, "") AS subject_key, role, email, phone,
                    COALESCE(icon, "fa-building") AS icon, COALESCE(accent, "a") AS accent,
                    sort_order, is_visible
             FROM contact_departments
             WHERE is_visible = 1
             ORDER BY sort_order ASC, name ASC'
        );
    }

    public static function subjectOptions(bool $includeGeneral = true): array
    {
        $options = [];
        if ($includeGeneral) {
            $options['general'] = 'General Enquiry';
        }

        foreach (self::visible() as $department) {
            $key = trim((string)($department['subject_key'] ?? ''));
            if ($key === '') {
                $key = self::keyFromName((string)($department['name'] ?? ''));
            }
            if ($key !== '') {
                $options[$key] = (string)($department['name'] ?? $key);
            }
        }

        $options['other'] = 'Other';
        return $options;
    }

    public static function validSubject(string $subject): bool
    {
        return array_key_exists($subject, self::subjectOptions(true));
    }

    public static function labelForSubject(string $subject): string
    {
        $options = self::subjectOptions(true);
        return $options[$subject] ?? $subject;
    }

    public static function keyFromName(string $name): string
    {
        $key = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '_', $name), '_'));
        return $key !== '' ? $key : 'general';
    }
}

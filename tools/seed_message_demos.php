<?php

/**
 * Safe demo seed for Message Centre.
 * Creates welcome / project threads for managers (and leadership) without duplicating subjects.
 */
require dirname(__DIR__) . '/app/core/bootstrap.php';

echo "=== Message Centre demo seed ===\n";

$sa = Database::fetch(
    "SELECT u.id, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS name
     FROM users u JOIN roles r ON r.id = u.role_id
     WHERE r.slug = 'superadmin' AND u.status = 'active' LIMIT 1"
);
if (!$sa) {
    fwrite(STDERR, "No superadmin found.\n");
    exit(1);
}
$saId = (int)$sa['id'];

$managers = Database::fetchAll(
    "SELECT u.id, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS name
     FROM users u JOIN roles r ON r.id = u.role_id
     WHERE r.slug = 'manager' AND u.status = 'active'
     ORDER BY u.id ASC"
);

function alreadyHasSubjectForUser(int $userId, string $subject): bool
{
    $row = Database::fetch(
        "SELECT t.id
         FROM message_threads t
         INNER JOIN message_participants mp ON mp.thread_id = t.id AND mp.user_id = ?
         WHERE t.subject = ?
         LIMIT 1",
        [$userId, $subject]
    );
    return $row !== null;
}

function seedDirect(int $fromId, string $fromRole, int $toId, string $toRole, string $subject, string $body, string $reply = ''): bool
{
    // Only skip if the sender already has this subject (allows same subject for each manager).
    if (alreadyHasSubjectForUser($fromId, $subject)) {
        echo "  skip existing: {$subject}\n";
        return false;
    }
    if (!MessageThread::canMessageUser($fromId, $fromRole, $toId, null)) {
        echo "  skip policy block: {$subject}\n";
        return false;
    }

    $threadId = MessageThread::createThread($subject, 'direct', $fromId, null, 'normal');
    MessageParticipant::add($threadId, $fromId, $fromRole, true);
    MessageParticipant::add($threadId, $toId, $toRole, false);
    $mid = Message::createForThread($threadId, $fromId, $body);
    MessageThread::touchLastMessage($threadId, $mid);
    MessageRead::markThread($threadId, $fromId);

    if ($reply !== '') {
        $rid = Message::createForThread($threadId, $toId, $reply);
        MessageThread::touchLastMessage($threadId, $rid);
        MessageRead::markThread($threadId, $toId);
    }

    echo "  + direct #{$threadId}: {$subject}\n";
    return true;
}

function seedProjectChannel(int $managerId, int $saId, ?int $clerkId): bool
{
    $project = Database::fetch(
        "SELECT p.id, p.name
         FROM projects p
         INNER JOIN project_assignments pa ON pa.project_id = p.id AND pa.user_id = ? AND pa.status = 'active'
         ORDER BY p.name ASC LIMIT 1",
        [$managerId]
    );
    if (!$project) {
        echo "  no project for manager #{$managerId}\n";
        return false;
    }
    $projectId = (int)$project['id'];
    $projectName = (string)$project['name'];
    $subject = $projectName . ' — site coordination channel';
    if (alreadyHasSubjectForUser($managerId, $subject)) {
        echo "  skip existing channel: {$subject}\n";
        return false;
    }

    $threadId = MessageThread::createThread($subject, 'project-channel', $managerId, $projectId, 'normal');
    MessageParticipant::add($threadId, $managerId, 'manager', true);
    if ($clerkId && MessageThread::canMessageUser($managerId, 'manager', $clerkId, $projectId)) {
        MessageParticipant::add($threadId, $clerkId, 'clerk', false);
    }
    if (MessageThread::canMessageUser($managerId, 'manager', $saId, $projectId)) {
        MessageParticipant::add($threadId, $saId, 'superadmin', false);
    }
    $body = "Team,\n\nThis is the project coordination channel for {$projectName}.\nUse it for site updates, attendance, and IPC walkthrough notes.\nOnly assigned project staff can participate.";
    $mid = Message::createForThread($threadId, $managerId, $body);
    MessageThread::touchLastMessage($threadId, $mid);
    MessageRead::markThread($threadId, $managerId);
    echo "  + channel #{$threadId}: {$subject}\n";
    return true;
}

try {
    Database::beginTransaction();
    $created = 0;

    foreach ($managers as $manager) {
        $managerId = (int)$manager['id'];
        $managerName = trim((string)$manager['name']) ?: 'Manager';
        echo "Manager #{$managerId} {$managerName}\n";

        if (seedDirect(
            $managerId,
            'manager',
            $saId,
            'superadmin',
            'Welcome to Message Centre — portfolio check-in',
            "Good morning,\n\nThis is a demo conversation for {$managerName}.\nManagers can message county leadership and staff on their assigned projects only.\n\nPlease reply here to confirm Message Centre is working for your account.",
            "Acknowledged, {$managerName}. Message Centre is live. Non-directors only see staff on shared projects; Super Administrators retain the full staff directory."
        )) {
            $created++;
        }

        // Optional second thread with another manager if allowed
        $peer = Database::fetch(
            "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.slug = 'manager' AND u.status = 'active' AND u.id <> ?
             ORDER BY u.id ASC LIMIT 1",
            [$managerId]
        );
        if ($peer && MessageThread::canMessageUser($managerId, 'manager', (int)$peer['id'], null)) {
            if (seedDirect(
                $managerId,
                'manager',
                (int)$peer['id'],
                'manager',
                'Manager coordination — weekly priorities',
                "Hi colleague,\n\nSharing a demo note between project managers. Use Message Centre for cross-portfolio coordination when you share project teams or when policy allows.",
                ''
            )) {
                $created++;
            }
        }

        $clerk = Database::fetch(
            "SELECT u.id
             FROM users u
             JOIN roles r ON r.id = u.role_id
             JOIN project_assignments pa ON pa.user_id = u.id AND pa.status = 'active'
             JOIN project_assignments mine ON mine.project_id = pa.project_id AND mine.user_id = ? AND mine.status = 'active'
             WHERE r.slug = 'clerk' AND u.status = 'active'
             LIMIT 1",
            [$managerId]
        );
        $clerkId = $clerk ? (int)$clerk['id'] : null;
        if (seedProjectChannel($managerId, $saId, $clerkId)) {
            $created++;
        }
    }

    // Superadmin broadcast-style demo (direct to first finance if allowed)
    $finance = Database::fetch(
        "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'finance' AND u.status = 'active' LIMIT 1"
    );
    if ($finance) {
        if (seedDirect(
            $saId,
            'superadmin',
            (int)$finance['id'],
            'finance',
            'Finance desk — Message Centre ready',
            "Hello Finance,\n\nThis is a demo thread from county leadership. Use Message Centre for payment and certification discussions with managers and contractors on active projects.",
            "Noted. Finance will use Message Centre for payment queries and certification follow-ups."
        )) {
            $created++;
        }
    }

    Database::commit();
    echo "Done. New items created this run: {$created}\n";
    echo 'Threads total: ' . (int)(Database::fetch('SELECT COUNT(*) c FROM message_threads')['c'] ?? 0) . "\n";
    echo 'Messages total: ' . (int)(Database::fetch('SELECT COUNT(*) c FROM messages')['c'] ?? 0) . "\n";
} catch (Throwable $e) {
    Database::rollBack();
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}

<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin'],
    'csrf' => false,
]);

$userId = (int)(Auth::id() ?? 0);

try {
    $contactSummary = ContactSubmission::summary([]);
    $unreadContacts = array_map(
        [ContactSubmission::class, 'payload'],
        ContactSubmission::items(['read_state' => 'unread'], 5)
    );

    Response::json([
        'success' => true,
        'unreadContacts' => (int)($contactSummary['unread'] ?? 0),
        'unreadNotifications' => Notification::unreadCount($userId),
        'contacts' => array_map(static fn (array $contact): array => [
            'id' => (int)$contact['id'],
            'name' => (string)$contact['name'],
            'subject' => (string)($contact['subject'] ?: 'No subject'),
            'createdAt' => (string)$contact['created_at'],
            'timeAgo' => time_ago($contact['created_at']),
            'url' => Url::to('admin/superadmin/contact-inbox.php?read_state=unread'),
        ], $unreadContacts),
    ]);
} catch (Throwable $exception) {
    Logger::error('Superadmin dashboard summary failed', ['error' => $exception->getMessage()]);
    Response::json(['success' => false, 'message' => 'Dashboard summary could not be loaded.'], 500);
}

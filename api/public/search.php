<?php

require_once __DIR__ . '/../../app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'csrf' => false,
]);

try {
    $query = Security::cleanString((string)($_GET['q'] ?? ''));
    $type = strtolower(Security::cleanString((string)($_GET['type'] ?? 'all')));
    $limit = max(1, min(30, (int)($_GET['limit'] ?? 10)));
    $page = max(1, min(100, (int)($_GET['page'] ?? 1)));

    PublicApi::sessionRateLimit('public_search_' . PublicApi::clientIp(), SystemConfig::int('public.search_rate_limit_1m', 30) ?: 30, 60, 'Please wait a moment before searching again.');

    $payload = PublicSearchService::search($query, $type, $limit, $page);

    PublicApi::ok([
        'results' => $payload['results'],
        'counts' => $payload['counts'],
    ], $payload['message'], [
        'query' => $payload['query'],
        'type' => $payload['type'],
        'page' => $payload['page'],
        'limit' => $payload['limit'],
        'total' => $payload['total'],
        'has_more' => $payload['has_more'],
    ]);
} catch (Throwable $e) {
    PublicApi::log('Public search failed', ['error' => $e->getMessage()]);
    PublicApi::fail('Search is temporarily unavailable. Please try again shortly.', 500);
}

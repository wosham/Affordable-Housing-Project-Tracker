<?php
// Base Controller — shared utilities for all admin page controllers
class Controller
{
    protected array $data = [];

    protected function view(string $template, array $data = []): void
    {
        // TODO: Phase 2 — Include view template with extracted data
        extract($data);
        // include VIEWS_PATH . '/' . $template . '.php';
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }
}

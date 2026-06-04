<?php

class Controller
{
    protected array $data = [];

    protected function view(string $template, array $data = []): void
    {
        $template = trim($template, " \t\n\r\0\x0B/");
        if ($template === '' || str_contains($template, '..')) {
            throw new InvalidArgumentException('Invalid view template.');
        }

        $paths = $GLOBALS['paths'] ?? require dirname(__DIR__) . '/config/paths.php';
        $candidates = [
            ($paths['app'] ?? dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $template . '.php',
            ($paths['partials'] ?? dirname(__DIR__) . DIRECTORY_SEPARATOR . 'partials') . DIRECTORY_SEPARATOR . $template . '.php',
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                extract($data, EXTR_SKIP);
                include $file;
                return;
            }
        }

        throw new RuntimeException('View template not found: ' . $template);
    }

    protected function json(array $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }
}

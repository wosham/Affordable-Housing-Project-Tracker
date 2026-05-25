<?php

$root = dirname(__DIR__, 2);

return [
    'root' => $root,
    'app' => $root . DIRECTORY_SEPARATOR . 'app',
    'config' => $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config',
    'core' => $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'core',
    'helpers' => $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'helpers',
    'partials' => $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'partials',
    'data' => $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'data',
    'uploads' => $root . DIRECTORY_SEPARATOR . 'uploads',
    'assets' => $root . DIRECTORY_SEPARATOR . 'assets',
];

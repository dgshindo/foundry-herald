<?php

declare(strict_types=1);

use FoundryHerald\Config;
use FoundryHerald\Repositories\PostRepository;

define('APP_ROOT', dirname(__DIR__, 2));

require APP_ROOT . '/vendor/autoload.php';

Config::load(APP_ROOT);

header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond([
        'success' => false,
        'error' => 'Method not allowed.',
    ], 405);
}

try {
    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        throw new RuntimeException(
            'Invalid post ID.'
        );
    }

    $repository = new PostRepository();

    $post = $repository->findById($id);

    if ($post === null) {
        respond([
            'success' => false,
            'error' => 'Post not found.',
        ], 404);
    }

    respond([
        'success' => true,
        'post' => $post,
    ]);

} catch (Throwable $e) {
    respond([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}
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
    $limit = isset($_GET['limit'])
        ? (int) $_GET['limit']
        : 20;

    $repository = new PostRepository();

    $posts = $repository->findRecent($limit);

    respond([
        'success' => true,
        'posts' => $posts,
    ]);

} catch (Throwable $e) {
    respond([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}
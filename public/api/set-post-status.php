<?php

declare(strict_types=1);

use FoundryHerald\Config;
use FoundryHerald\Repositories\PostRepository;
use FoundryHerald\Services\AuthService;

define('APP_ROOT', dirname(__DIR__, 2));

require APP_ROOT . '/vendor/autoload.php';

Config::load(APP_ROOT);

AuthService::requireApi();

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond([
        'success' => false,
        'error' => 'Method not allowed.',
    ], 405);
}

try {
    $id = (int) ($_POST['id'] ?? 0);

    $status = trim(
        (string) ($_POST['status'] ?? '')
    );

    if ($id <= 0) {
        throw new RuntimeException(
            'A saved post is required.'
        );
    }

    if (!in_array(
        $status,
        ['approved', 'rejected'],
        true
    )) {
        throw new RuntimeException(
            'Invalid post status.'
        );
    }

    $repository = new PostRepository();

    $repository->setStatus(
        $id,
        $status
    );

    respond([
        'success' => true,
        'id' => $id,
        'status' => $status,
        'message' =>
            $status === 'approved'
                ? 'Post approved.'
                : 'Post rejected.',
    ]);

} catch (Throwable $e) {
    respond([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}
<?php

declare(strict_types=1);

use FoundryHerald\Config;
use FoundryHerald\Repositories\PostRepository;
use FoundryHerald\Services\FacebookPublisher;

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond([
        'success' => false,
        'error' => 'Method not allowed.',
    ], 405);
}

$postId = (int) ($_POST['id'] ?? 0);

try {
    if ($postId <= 0) {
        throw new RuntimeException(
            'Invalid post ID.'
        );
    }

    $repository = new PostRepository();

    $post = $repository->findById($postId);

    if ($post === null) {
        throw new RuntimeException(
            'Post not found.'
        );
    }

    if ($post['status'] !== 'approved') {
        throw new RuntimeException(
            'Only approved posts may be published.'
        );
    }

    $publisher =
        new FacebookPublisher();

    $facebookPostId =
        $publisher->publishText(
            (string) $post['content']
        );

    $repository->markPublished(
        $postId,
        $facebookPostId
    );

    respond([
        'success' => true,
        'id' => $postId,
        'facebook_post_id' =>
            $facebookPostId,
        'message' =>
            'Published to House Dainislaav.',
    ]);

} catch (Throwable $e) {

    if ($postId > 0) {
        try {
            $repository =
                $repository
                ?? new PostRepository();

            $repository->recordPublishError(
                $postId,
                $e->getMessage()
            );
        } catch (Throwable) {
            // Preserve the original publishing error.
        }
    }

    respond([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}
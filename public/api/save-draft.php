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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond([
        'success' => false,
        'error' => 'Method not allowed.',
    ], 405);
}

try {
    $idRaw = $_POST['id'] ?? null;

    $id =
        ($idRaw !== null && $idRaw !== '')
            ? (int) $idRaw
            : null;

    $postType = trim(
        (string) ($_POST['post_type'] ?? '')
    );

    $topic = trim(
        (string) ($_POST['topic'] ?? '')
    );

    $imagePreference = trim(
        (string) ($_POST['image_preference'] ?? 'auto')
    );

    $content = trim(
        (string) ($_POST['content'] ?? '')
    );

    if ($postType === '') {
        throw new RuntimeException(
            'Post type is required.'
        );
    }

    if ($content === '') {
        throw new RuntimeException(
            'Draft content cannot be empty.'
        );
    }

    if (
        !in_array(
            $imagePreference,
            ['auto', 'yes', 'no'],
            true
        )
    ) {
        throw new RuntimeException(
            'Invalid image preference.'
        );
    }

    $repository = new PostRepository();

    $savedId = $repository->saveDraft(
        $id,
        $postType,
        $topic,
        $imagePreference,
        $content
    );

    respond([
        'success' => true,
        'id' => $savedId,
        'message' => 'Draft saved.',
    ]);

} catch (Throwable $e) {

    respond([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}
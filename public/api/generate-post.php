<?php

declare(strict_types=1);

use FoundryHerald\Config;
use FoundryHerald\Database;
use FoundryHerald\Services\ContentGenerator;
use FoundryHerald\Services\KnowledgeLoader;

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
    Database::connection();

    $postType = trim(
        (string) ($_POST['post_type'] ?? 'Let Herald Decide')
    );

    $topic = trim(
        (string) ($_POST['topic'] ?? '')
    );

    $imagePreference = trim(
        (string) ($_POST['image_preference'] ?? 'auto')
    );

    if ($topic === '') {
        $topic =
            'Choose a relevant topic based on the House Dainislaav '
            . 'knowledge and content rules.';
    }

    $knowledgeLoader = new KnowledgeLoader(
        APP_ROOT . '/knowledge'
    );

    $context = $knowledgeLoader->buildContext([
        'house-dainislaav.md',
        'voice-and-tone.md',
        'content-rules.md',
        'content-voices.md',
    ]);

    $generator = new ContentGenerator();

    $post = $generator->generate(
        $context,
        $postType,
        $topic
    );

    respond([
        'success' => true,
        'post' => $post,
        'imagePreference' => $imagePreference,
    ]);

} catch (Throwable $e) {

    respond([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}
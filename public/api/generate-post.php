<?php

declare(strict_types=1);

use FoundryHerald\Config;
use FoundryHerald\Database;
use FoundryHerald\Services\ContentGenerator;
use FoundryHerald\Services\KnowledgeLoader;
use FoundryHerald\Repositories\PostRepository;
use FoundryHerald\Services\ContentMemory;
use FoundryHerald\Repositories\PublishingDestinationRepository;

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

    $destinationId = (int) (
        $_POST['destination_id'] ?? 0
    );

    if ($destinationId <= 0) {
        throw new RuntimeException(
            'Please select a brand/page.'
        );
    }

    $destinationRepository =
        new PublishingDestinationRepository();

    $destination =
        $destinationRepository->findById(
            $destinationId
        );

    if ($destination === null) {
        throw new RuntimeException(
            'The selected brand/page is not available.'
        );
    }

    if ($topic === '') {
        $topic =
        'Choose a fresh topic appropriate to the selected content type. '
        . 'Do not reuse or closely paraphrase any recent central topic. '
        . 'Prefer an idea that has not appeared in recent content.';
        }

    $knowledgePath = trim(
        (string) ($destination['knowledge_path'] ?? '')
    );

    if ($knowledgePath === '') {
        throw new RuntimeException(
            'No knowledge path is configured for '
            . $destination['name']
            . '.'
        );
    }

    $knowledgeLoader = new KnowledgeLoader(
        APP_ROOT . '/' . trim($knowledgePath, '/\\')
    );

    $context = $knowledgeLoader->buildContext([
        'house-dainislaav.md',
        'voice-and-tone.md',
        'content-rules.md',
        'content-voices.md',
    ]);

    $postRepository = new PostRepository();

    $contentMemory = new ContentMemory(
        $postRepository
    );

    $memory = $contentMemory->build(
        $destinationId,
        (string) $destination['name'],
        12
    );


    $generator = new ContentGenerator();

    $post = $generator->generate(
        $context,
        $memory,
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
<?php

declare(strict_types=1);

use FoundryHerald\Config;
use FoundryHerald\Repositories\PublishingDestinationRepository;
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond([
        'success' => false,
        'error' => 'Method not allowed.',
    ], 405);
}

try {
    $destinationId = (int) (
        $_GET['destination_id'] ?? 0
    );

    if ($destinationId <= 0) {
        throw new RuntimeException(
            'Invalid destination ID.'
        );
    }

    $repository =
        new PublishingDestinationRepository();

    $destination =
        $repository->findById(
            $destinationId
        );

    if ($destination === null) {
        throw new RuntimeException(
            'Publishing destination not found.'
        );
    }

    $knowledgePath = trim(
        (string) (
            $destination['knowledge_path']
            ?? ''
        )
    );

    if ($knowledgePath === '') {
        throw new RuntimeException(
            'No knowledge path is configured for '
            . $destination['name']
            . '.'
        );
    }

    $profileDirectory =
        APP_ROOT
        . DIRECTORY_SEPARATOR
        . trim(
            $knowledgePath,
            '/\\'
        );

    $configFile =
        $profileDirectory
        . DIRECTORY_SEPARATOR
        . 'post-types.php';

    if (!is_file($configFile)) {
        throw new RuntimeException(
            'Post type configuration not found for '
            . $destination['name']
            . '.'
        );
    }

    $postTypes = require $configFile;

    if (!is_array($postTypes)) {
        throw new RuntimeException(
            'Invalid post type configuration for '
            . $destination['name']
            . '.'
        );
    }

    $result = [];

    foreach ($postTypes as $value => $label) {
        if (
            !is_string($value) ||
            !is_string($label) ||
            trim($value) === '' ||
            trim($label) === ''
        ) {
            continue;
        }

        $result[] = [
            'value' => $value,
            'label' => $label,
        ];
    }

    if ($result === []) {
        throw new RuntimeException(
            'No valid post types are configured for '
            . $destination['name']
            . '.'
        );
    }

    respond([
        'success' => true,
        'destination_id' => $destinationId,
        'post_types' => $result,
    ]);

} catch (Throwable $e) {

    respond([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}

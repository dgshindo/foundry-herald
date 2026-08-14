<?php

declare(strict_types=1);

use FoundryHerald\Config;
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

try {
    $repository =
        new PublishingDestinationRepository();

    $destinations =
        $repository->findActive();

    respond([
        'success' => true,
        'destinations' => array_map(
            static function (array $destination): array {
                return [
                    'id' =>
                        (int) $destination['id'],

                    'name' =>
                        $destination['name'],

                    'slug' =>
                        $destination['slug'],

                    'is_default' =>
                        (bool) $destination['is_default'],
                ];
            },
            $destinations
        ),
    ]);

} catch (Throwable $e) {
    respond([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}
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
        JSON_UNESCAPED_UNICODE |
        JSON_PRETTY_PRINT
    );

    exit;
}

try {
    $version = (string) Config::get(
        'FACEBOOK_GRAPH_VERSION',
        'v26.0'
    );

    $destinationRepository =
        new PublishingDestinationRepository();

    $destination =
        $destinationRepository->findDefault();

    if ($destination === null) {
        throw new RuntimeException(
            'No active publishing destination is configured.'
        );
    }

    if (
        ($destination['platform'] ?? '') !== 'facebook'
        || ($destination['destination_type'] ?? '') !== 'page'
    ) {
        throw new RuntimeException(
            'The default publishing destination is not a Facebook Page.'
        );
    }

    $pageId = trim(
        (string) ($destination['external_id'] ?? '')
    );

    $tokenEnvKey = trim(
        (string) ($destination['token_env_key'] ?? '')
    );

    if ($pageId === '') {
        throw new RuntimeException(
            'Facebook Page ID is missing from the publishing destination.'
        );
    }

    if ($tokenEnvKey === '') {
        throw new RuntimeException(
            'Facebook token environment key is missing.'
        );
    }

    $token = (string) Config::get(
        $tokenEnvKey,
        ''
    );

    if ($token === '') {
        throw new RuntimeException(
            sprintf(
                'Facebook access token is not configured for %s.',
                $destination['name'] ?? 'the default destination'
            )
        );
    }

    $url =
        'https://graph.facebook.com/'
        . rawurlencode($version)
        . '/'
        . rawurlencode($pageId)
        . '?fields=id,name'
        . '&access_token='
        . rawurlencode($token);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);

        throw new RuntimeException(
            'Facebook request failed: ' . $error
        );
    }

    $status = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    $data = json_decode(
        $response,
        true
    );

    if ($status < 200 || $status >= 300) {
        throw new RuntimeException(
            $data['error']['message']
            ?? 'Unknown Facebook API error.'
        );
    }

    respond([
        'success' => true,
        'destination' => $destination['name'],
        'page' => [
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? null,
        ],
    ]);

} catch (Throwable $e) {
    respond([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}
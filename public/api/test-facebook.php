<?php

declare(strict_types=1);

use FoundryHerald\Config;

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

    $pageId = (string) Config::get(
        'FACEBOOK_PAGE_ID',
        ''
    );

    $token = (string) Config::get(
        'FACEBOOK_PAGE_ACCESS_TOKEN',
        ''
    );

    if ($pageId === '') {
        throw new RuntimeException(
            'FACEBOOK_PAGE_ID is not configured.'
        );
    }

    if ($token === '') {
        throw new RuntimeException(
            'FACEBOOK_PAGE_ACCESS_TOKEN is not configured.'
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
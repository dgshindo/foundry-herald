<?php

declare(strict_types=1);

namespace FoundryHerald\Services;

use FoundryHerald\Config;
use RuntimeException;

final class FacebookPublisher
{
    private string $graphVersion;

    public function __construct()
    {
        $this->graphVersion = (string) Config::get(
            'FACEBOOK_GRAPH_VERSION',
            'v26.0'
        );
    }

    public function publishText(
        array $destination,
        string $message
    ): string {
        $message = trim($message);

        if ($message === '') {
            throw new RuntimeException(
                'Facebook post content cannot be empty.'
            );
        }

        if (
            ($destination['platform'] ?? '') !== 'facebook'
            || ($destination['destination_type'] ?? '') !== 'page'
        ) {
            throw new RuntimeException(
                'Unsupported publishing destination.'
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
                'Facebook Page ID is missing.'
            );
        }

        if ($tokenEnvKey === '') {
            throw new RuntimeException(
                'Facebook token environment key is missing.'
            );
        }

        $pageAccessToken = (string) Config::get(
            $tokenEnvKey,
            ''
        );

        if ($pageAccessToken === '') {
            throw new RuntimeException(
                sprintf(
                    'Facebook access token is not configured for %s.',
                    $destination['name'] ?? 'this destination'
                )
            );
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/feed',
            $this->graphVersion,
            $pageId
        );

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'message' => $message,
                'access_token' => $pageAccessToken,
            ]),
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException(
                'Facebook request failed: ' . $error
            );
        }

        $httpStatus = curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        curl_close($ch);

        $data = json_decode(
            $response,
            true
        );

        if (
            $httpStatus < 200 ||
            $httpStatus >= 300
        ) {
            throw new RuntimeException(
                $data['error']['message']
                ?? 'Unknown Facebook API error.'
            );
        }

        $postId = (string) (
            $data['id'] ?? ''
        );

        if ($postId === '') {
            throw new RuntimeException(
                'Facebook did not return a post ID.'
            );
        }

        return $postId;
    }
}
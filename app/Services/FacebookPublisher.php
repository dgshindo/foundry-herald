<?php

declare(strict_types=1);

namespace FoundryHerald\Services;

use FoundryHerald\Config;
use RuntimeException;

final class FacebookPublisher
{
    private string $graphVersion;
    private string $pageId;
    private string $pageAccessToken;

    public function __construct()
    {
        $this->graphVersion = (string) Config::get(
            'FACEBOOK_GRAPH_VERSION',
            'v26.0'
        );

        $this->pageId = (string) Config::get(
            'FACEBOOK_PAGE_ID',
            ''
        );

        $this->pageAccessToken = (string) Config::get(
            'FACEBOOK_PAGE_ACCESS_TOKEN',
            ''
        );

        if ($this->pageId === '') {
            throw new RuntimeException(
                'FACEBOOK_PAGE_ID is not configured.'
            );
        }

        if ($this->pageAccessToken === '') {
            throw new RuntimeException(
                'FACEBOOK_PAGE_ACCESS_TOKEN is not configured.'
            );
        }
    }

    public function publishText(string $message): string
    {
        $message = trim($message);

        if ($message === '') {
            throw new RuntimeException(
                'Facebook post content cannot be empty.'
            );
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/feed',
            $this->graphVersion,
            $this->pageId
        );

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'message' => $message,
                'access_token' => $this->pageAccessToken,
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
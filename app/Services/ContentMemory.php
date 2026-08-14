<?php

declare(strict_types=1);

namespace FoundryHerald\Services;

use FoundryHerald\Repositories\PostRepository;

final class ContentMemory
{
    public function __construct(
        private readonly PostRepository $posts
    ) {
    }

    public function build(
        int $destinationId,
        string $destinationName,
        int $limit = 12
    ): string
    {
        $posts = $this->posts->findRecentForMemory(
            $destinationId,
            $limit
        );

        if ($posts === []) {
            return sprintf(
                'No recent %s posts exist yet.',
                $destinationName
            );
        }

        $memory = [
            'RECENT ' . strtoupper($destinationName) . ' CONTENT',
            '',
            'Use this only to avoid unnecessary repetition.',
            'Do not copy wording from these posts.',
            '',
        ];

        foreach ($posts as $post) {
            $content = preg_replace(
                '/\s+/',
                ' ',
                trim((string) $post['content'])
            );

            /*
             * We do not need to send entire historical posts
             * back to the model.
             */
            if (mb_strlen($content) > 400) {
                $content =
                    mb_substr($content, 0, 400)
                    . '...';
            }

            $memory[] = sprintf(
                "Status: %s\nType: %s\nTopic: %s\nExcerpt: %s",
                $post['status'],
                $post['post_type'],
                $post['topic'] ?: 'No explicit topic',
                $content
            );

            $memory[] = '';
        }

        return trim(
            implode("\n", $memory)
        );
    }
}
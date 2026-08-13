<?php

declare(strict_types=1);

namespace FoundryHerald\Repositories;

use FoundryHerald\Database;
use PDO;

final class PostRepository
{
    public function saveDraft(
        ?int $id,
        string $postType,
        ?string $topic,
        string $imagePreference,
        string $content
    ): int {
        $db = Database::connection();

        if ($id !== null) {
            $statement = $db->prepare(
                '
                UPDATE posts
                SET
                    post_type = :post_type,
                    topic = :topic,
                    image_preference = :image_preference,
                    content = :content,
                    status = \'draft\'
                WHERE id = :id
                '
            );

            $statement->execute([
                'id' => $id,
                'post_type' => $postType,
                'topic' => $topic !== '' ? $topic : null,
                'image_preference' => $imagePreference,
                'content' => $content,
            ]);

            return $id;
        }

        $statement = $db->prepare(
            '
            INSERT INTO posts (
                post_type,
                topic,
                image_preference,
                content,
                status
            )
            VALUES (
                :post_type,
                :topic,
                :image_preference,
                :content,
                \'draft\'
            )
            '
        );

        $statement->execute([
            'post_type' => $postType,
            'topic' => $topic !== '' ? $topic : null,
            'image_preference' => $imagePreference,
            'content' => $content,
        ]);

        return (int) $db->lastInsertId();
    }
}
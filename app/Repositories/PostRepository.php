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

    public function setStatus(
        int $id,
        string $status
    ): void {
        $db = Database::connection();

        if (!in_array(
            $status,
            ['draft', 'approved', 'rejected'],
            true
        )) {
            throw new \InvalidArgumentException(
                'Invalid post status.'
            );
        }

        if ($status === 'approved') {
            $statement = $db->prepare(
                '
                UPDATE posts
                SET
                    status = :status,
                    approved_at = CURRENT_TIMESTAMP
                WHERE id = :id
                '
            );
        } else {
            $statement = $db->prepare(
                '
                UPDATE posts
                SET
                    status = :status,
                    approved_at = NULL
                WHERE id = :id
                '
            );
        }

        $statement->execute([
            'id' => $id,
            'status' => $status,
        ]);

        if ($statement->rowCount() === 0) {
            throw new \RuntimeException(
                'Post could not be updated.'
            );
        }
    }

    public function findRecent(int $limit = 20): array
    {
        $db = Database::connection();

        $limit = max(1, min($limit, 100));

        $statement = $db->prepare(
            '
            SELECT
                id,
                post_type,
                topic,
                image_preference,
                content,
                status,
                created_at,
                updated_at,
                approved_at,
                published_at
            FROM posts
            ORDER BY updated_at DESC, id DESC
            LIMIT :limit
            '
        );

        $statement->bindValue(
            ':limit',
            $limit,
            \PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $db = Database::connection();

        $statement = $db->prepare(
            '
            SELECT
                id,
                post_type,
                topic,
                image_preference,
                content,
                status,
                created_at,
                updated_at,
                approved_at,
                published_at
            FROM posts
            WHERE id = :id
            LIMIT 1
            '
        );

        $statement->execute([
            'id' => $id,
        ]);

        $post = $statement->fetch();

        return $post !== false
            ? $post
            : null;
    }

    public function findRecentForMemory(int $limit = 12): array
    {
        $db = Database::connection();

        $limit = max(1, min($limit, 30));

        $statement = $db->prepare(
            '
            SELECT
                id,
                post_type,
                topic,
                content,
                status,
                created_at,
                updated_at
            FROM posts
            WHERE status IN (
                \'draft\',
                \'approved\',
                \'rejected\',
                \'published\'
            )
            ORDER BY updated_at DESC, id DESC
            LIMIT :limit
            '
        );

        $statement->bindValue(
            ':limit',
            $limit,
            \PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->fetchAll();
    }

    public function markPublished(
        int $id,
        string $facebookPostId
    ): void {
        $db = Database::connection();

        $statement = $db->prepare(
            '
            UPDATE posts
            SET
                status = \'published\',
                published_at = CURRENT_TIMESTAMP,
                facebook_post_id = :facebook_post_id,
                publish_error = NULL
            WHERE id = :id
            AND status = \'approved\'
            '
        );

        $statement->execute([
            'id' => $id,
            'facebook_post_id' => $facebookPostId,
        ]);

        if ($statement->rowCount() === 0) {
            throw new \RuntimeException(
                'Only approved posts can be published.'
            );
        }
    }

    public function recordPublishError(
        int $id,
        string $error
    ): void {
        $db = Database::connection();

        $statement = $db->prepare(
            '
            UPDATE posts
            SET publish_error = :publish_error
            WHERE id = :id
            '
        );

        $statement->execute([
            'id' => $id,
            'publish_error' => $error,
        ]);
    }
}
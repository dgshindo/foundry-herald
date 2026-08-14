<?php

declare(strict_types=1);

namespace FoundryHerald\Repositories;

use FoundryHerald\Database;

final class PublishingDestinationRepository
{
    public function findActive(): array
    {
        $db = Database::connection();

        $statement = $db->query(
            '
            SELECT
                id,
                name,
                slug,
                platform,
                destination_type,
                external_id,
                token_env_key,
                knowledge_path,
                memory_scope,
                is_default,
                is_active
            FROM publishing_destinations
            WHERE is_active = 1
            ORDER BY is_default DESC, name ASC
            '
        );

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $db = Database::connection();

        $statement = $db->prepare(
            '
            SELECT
                id,
                name,
                slug,
                platform,
                destination_type,
                external_id,
                token_env_key,
                knowledge_path,
                memory_scope,
                is_default,
                is_active
            FROM publishing_destinations
            WHERE id = :id
              AND is_active = 1
            LIMIT 1
            '
        );

        $statement->execute([
            'id' => $id,
        ]);

        $destination = $statement->fetch();

        return $destination !== false
            ? $destination
            : null;
    }

    public function findDefault(): ?array
    {
        $db = Database::connection();

        $statement = $db->query(
            '
            SELECT
                id,
                name,
                slug,
                platform,
                destination_type,
                external_id,
                token_env_key,
                knowledge_path,
                memory_scope,
                is_default,
                is_active
            FROM publishing_destinations
            WHERE is_active = 1
            ORDER BY is_default DESC, id ASC
            LIMIT 1
            '
        );

        $destination = $statement->fetch();

        return $destination !== false
            ? $destination
            : null;
    }
}
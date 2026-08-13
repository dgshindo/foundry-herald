<?php

declare(strict_types=1);

namespace FoundryHerald;

use Dotenv\Dotenv;

final class Config
{
    public static function load(string $root): void
    {
        $dotenv = Dotenv::createImmutable($root);
        $dotenv->safeLoad();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}
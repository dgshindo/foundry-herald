<?php

declare(strict_types=1);

namespace FoundryHerald\Services;

use RuntimeException;

final class KnowledgeLoader
{
    private string $knowledgePath;

    public function __construct(string $knowledgePath)
    {
        $this->knowledgePath = rtrim($knowledgePath, DIRECTORY_SEPARATOR);
    }

    public function load(array $files): array
    {
        $knowledge = [];

        foreach ($files as $file) {
            $path = $this->knowledgePath . DIRECTORY_SEPARATOR . $file;

            if (!is_file($path)) {
                throw new RuntimeException(
                    sprintf('Knowledge file not found: %s', $file)
                );
            }

            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new RuntimeException(
                    sprintf('Unable to read knowledge file: %s', $file)
                );
            }

            $knowledge[$file] = trim($contents);
        }

        return $knowledge;
    }

    public function buildContext(array $files): string
    {
        $knowledge = $this->load($files);

        $sections = [];

        foreach ($knowledge as $filename => $content) {
            $sections[] =
                "SOURCE: {$filename}\n\n" .
                $content;
        }

        return implode(
            "\n\n==============================\n\n",
            $sections
        );
    }
}
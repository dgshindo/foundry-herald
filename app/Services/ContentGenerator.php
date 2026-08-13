<?php

declare(strict_types=1);

namespace FoundryHerald\Services;

use FoundryHerald\Config;
use RuntimeException;

final class ContentGenerator
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) Config::get('OPENAI_API_KEY', '');
        $this->model = (string) Config::get('OPENAI_MODEL', 'gpt-5.4');

        if ($this->apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }
    }

    public function generate(
        string $knowledge,
        string $postType,
        string $topic
    ): string {
        $instructions = <<<PROMPT
You are The Herald, the content agent for House Dainislaav.

Your job is to create Facebook posts that sound authentically like the House Dainislaav creator.

Use the supplied House knowledge as authoritative context.

Important rules:

- Write in natural paragraphs.
- Do not use generic motivational language.
- Do not sound like a social media marketing agency.
- Do not overuse forge, fire, steel, or warrior imagery.
- Do not manufacture fake profundity.
- Do not invent facts, lyrics, songs, events, or personal experiences.
- Do not use excessive emojis.
- Do not use excessive hashtags.
- Do not explain your reasoning.
- Return ONLY the Facebook post itself.
- Prefer ordinary, concrete language over polished philosophical language.
- It is okay for the writing to be slightly rough, funny, blunt, or imperfect.
- Prefer ordinary, concrete language over polished philosophical language.
- It is okay for the writing to be slightly rough, funny, blunt, or imperfect.
- Do not force every content type into the same rhythm or emotional tone.
- Let the requested content type influence pacing, humor, seriousness, sentence length, and vocabulary.
- Preserve the distinct content voices described in the House knowledge.

HOUSE KNOWLEDGE:

{$knowledge}
PROMPT;

        $userPrompt = <<<PROMPT
Create a House Dainislaav Facebook post.

Post type: {$postType}

Topic or idea:
{$topic}
PROMPT;

        $payload = [
            'model' => $this->model,
            'instructions' => $instructions,
            'input' => $userPrompt,
        ];

        $ch = curl_init('https://api.openai.com/v1/responses');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(
                $payload,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException(
                'OpenAI request failed: ' . $error
            );
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $data = json_decode($response, true);

        if ($status < 200 || $status >= 300) {
            $message = $data['error']['message']
                ?? 'Unknown OpenAI API error.';

            throw new RuntimeException($message);
        }

        foreach ($data['output'] ?? [] as $output) {
            foreach ($output['content'] ?? [] as $content) {
                if (
                    ($content['type'] ?? '') === 'output_text'
                    && isset($content['text'])
                ) {
                    return trim($content['text']);
                }
            }
        }

        throw new RuntimeException(
            'No text was returned by the OpenAI API.'
        );
    }
}
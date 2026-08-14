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
        string $memory,
        string $postType,
        string $topic
    ): string {
        $instructions = <<<PROMPT
You are The Herald, a content agent that writes for the currently selected
brand or publishing destination.

Your job is to create a Facebook post that sounds authentically like the
selected brand.

The supplied BRAND KNOWLEDGE is authoritative. It defines the brand's identity,
voice, subject matter, boundaries, and content styles. Do not import identity,
language, mythology, products, facts, or stylistic habits from another brand.

Important rules:

- Write in natural paragraphs.
- Follow the selected brand's voice and tone rather than a generic Herald voice.
- Do not use generic motivational language unless the brand knowledge and topic genuinely call for it.
- Do not sound like a social media marketing agency.
- Do not manufacture fake profundity.
- Do not invent facts, lyrics, songs, products, features, events, metrics, customer experiences, release dates, or personal experiences.
- Do not use excessive emojis or hashtags.
- Prefer ordinary, concrete language over unnecessarily polished language.
- It is okay for the writing to be slightly rough, funny, blunt, technical, reflective, or imperfect when that fits the selected brand.
- Do not force every content type into the same rhythm or emotional tone.
- Let the requested content type and brand knowledge influence pacing, humor, seriousness, sentence length, vocabulary, and perspective.
- Preserve the distinct content voices described in the brand knowledge.
- Treat brand-specific metaphors and imagery as optional tools, not mandatory decorations.
- Review the recent-content memory before choosing a subject.
- If the user did not provide an explicit topic, DO NOT reuse a recent central topic.
- Treat semantically similar ideas as repetition even if the wording is different.
- If a topic family appears in recent memory, choose a substantially different subject.
- Repetition avoidance is more important than choosing the most obvious topic for the content type.
- A rejected post is a strong signal to avoid that topic family entirely.
- Recent-content memory is for repetition avoidance and continuity. Do not imitate its wording, structure, hooks, metaphors, or conclusions merely because they appeared in previous posts.
- If the supplied topic conflicts with brand knowledge, preserve factual accuracy and brand boundaries rather than inventing a connection.
- Do not mention the knowledge files or recent-content memory in the post.
- Do not explain your reasoning.
- Return ONLY the Facebook post itself.

BRAND KNOWLEDGE:

{$knowledge}

RECENT CONTENT MEMORY:

{$memory}
PROMPT;

        $userPrompt = <<<PROMPT
Create a Facebook post for the selected brand.

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
<?php

declare(strict_types=1);

namespace App\Infrastructure\Gemini;

use App\Application\Ports\ScriptWriter;
use App\Domain\Article;
use App\Domain\Script;
use App\Infrastructure\Prompts\PromptLoader;

final class GeminiScriptWriter implements ScriptWriter
{
    private const MODEL = 'gemini-2.5-flash';

    public function __construct(
        private readonly GeminiHttpClient $client,
        private readonly PromptLoader $prompts,
    ) {}

    public function write(array $articles, string $language, int $maxChars): Script
    {
        $list = [];
        foreach ($articles as $article) {
            $list[] = [
                'source' => $article->source,
                'language' => $article->language,
                'title' => $article->title,
                'body' => mb_substr($article->body ?? $article->description, 0, 4000),
            ];
        }

        $prompt = $this->prompts->load('script_writer', [
            'today' => date('d.m.Y'),
            'language' => $this->languageName($language),
            'max_chars' => $maxChars,
            'articles_json' => json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);

        $response = $this->client->generateContent(self::MODEL, [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.7,
                // Cyrillic tokenizes at ~1.5 chars/token in Gemini's BPE — be generous.
                // Cap is a runaway-safety ceiling, not a length target; prompt enforces length.
                'maxOutputTokens' => 8192,
            ],
        ]);

        $text = trim((string) ($response['candidates'][0]['content']['parts'][0]['text'] ?? ''));

        return new Script($text, $language);
    }

    private function languageName(string $code): string
    {
        return match ($code) {
            'uk' => 'Ukrainian',
            'en' => 'English',
            default => $code,
        };
    }
}

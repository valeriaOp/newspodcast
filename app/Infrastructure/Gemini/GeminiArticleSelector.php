<?php

declare(strict_types=1);

namespace App\Infrastructure\Gemini;

use App\Application\Ports\ArticleSelector;
use App\Domain\Article;
use App\Infrastructure\Prompts\PromptLoader;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class GeminiArticleSelector implements ArticleSelector
{
    private const MODEL = 'gemini-2.5-flash';

    public function __construct(
        private readonly GeminiHttpClient $client,
        private readonly PromptLoader $prompts,
    ) {}

    public function select(array $candidates, int $max): array
    {
        if ($candidates === []) {
            return [];
        }

        $list = [];
        foreach ($candidates as $i => $article) {
            $list[] = [
                'id' => $i,
                'source' => $article->source,
                'language' => $article->language,
                'title' => $article->title,
                'description' => mb_substr($article->description, 0, 500),
                'published_at' => $article->publishedAt->format(DATE_ATOM),
            ];
        }

        $minTarget = min(5, max(3, (int) floor(count($candidates) / 3)));

        $prompt = $this->prompts->load('article_selector', [
            'min_target' => $minTarget,
            'max_target' => $max,
            'candidates_json' => json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);

        $response = $this->client->generateContent(self::MODEL, [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.3,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'selected_ids' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                        ],
                    ],
                    'required' => ['selected_ids'],
                ],
            ],
        ]);

        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        $parsed = json_decode($text, true) ?: [];
        $ids = $parsed['selected_ids'] ?? [];

        Log::info('article selection', [
            'candidates' => count($candidates),
            'selected' => count($ids),
            'selected_ids' => $ids,
            'target_range' => "{$minTarget}-{$max}",
        ]);

        $selected = [];
        foreach ($ids as $id) {
            if (isset($candidates[$id])) {
                $selected[] = $candidates[$id];
            }
        }

        if ($selected === []) {
            throw new RuntimeException('Gemini selector returned no valid ids');
        }

        return $selected;
    }
}

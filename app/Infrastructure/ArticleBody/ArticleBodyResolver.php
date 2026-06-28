<?php

declare(strict_types=1);

namespace App\Infrastructure\ArticleBody;

use App\Domain\Article;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\Readability;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ArticleBodyResolver
{
    public function __construct(
        private readonly Client $http,
        private readonly JinaReaderClient $jina,
    ) {}

    public function resolve(Article $article): Article
    {
        if ($article->hasBody()) {
            return $article;
        }

        $body = $this->tryReadability($article->url);
        if ($body !== null) {
            return $article->withBody($body);
        }

        $body = $this->jina->extract($article->url);
        if ($body !== null) {
            return $article->withBody($body);
        }

        Log::warning('article body resolution failed', ['url' => $article->url]);

        return $article;
    }

    private function tryReadability(string $url): ?string
    {
        try {
            $response = $this->http->get($url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; newspodcast/1.0; +https://example.com/bot)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ],
                'timeout' => 12,
                'http_errors' => false,
                'allow_redirects' => true,
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $html = (string) $response->getBody();
            if ($html === '') {
                return null;
            }

            $readability = new Readability(new Configuration);
            $readability->parse($html);

            $content = $readability->getContent();
            if (! $content) {
                return null;
            }

            $text = trim(strip_tags($content));

            return mb_strlen($text) > 200 ? $text : null;
        } catch (Throwable $e) {
            Log::info('readability failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }
}

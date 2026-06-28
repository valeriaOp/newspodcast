<?php

declare(strict_types=1);

namespace App\Infrastructure\Rss;

use App\Application\Filtering\KeywordFilter;
use App\Application\Ports\ContentSource;
use App\Domain\Article;
use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use SimplePie\SimplePie;

final class RssContentSource implements ContentSource
{
    /**
     * @param  list<RssFeedConfig>  $feeds
     */
    public function __construct(
        private readonly array $feeds,
        private readonly Client $http,
        private readonly KeywordFilter $filter,
    ) {}

    public function fetchSince(DateTimeImmutable $since): array
    {
        $bodies = $this->fetchBodies();

        $articles = [];
        foreach ($this->feeds as $idx => $feed) {
            if (! isset($bodies[$idx])) {
                continue;
            }

            $sp = new SimplePie;
            $sp->enable_cache(false);
            $sp->set_raw_data($bodies[$idx]);
            $sp->init();

            foreach ($sp->get_items() as $item) {
                $publishedTs = $item->get_date('U');
                if (! $publishedTs) {
                    continue;
                }

                $publishedAt = (new DateTimeImmutable)->setTimestamp((int) $publishedTs);
                if ($publishedAt < $since) {
                    continue;
                }

                $article = new Article(
                    source: $feed->name,
                    language: $feed->language,
                    title: $this->clean((string) $item->get_title()),
                    description: $this->clean(strip_tags((string) $item->get_description())),
                    url: (string) $item->get_link(),
                    publishedAt: $publishedAt,
                    body: $this->extractBody($item),
                );

                if (! $this->filter->matches($article, $feed->includeKeywords, $feed->excludeKeywords)) {
                    continue;
                }

                $articles[] = $article;
            }
        }

        return $articles;
    }

    /**
     * @return array<int, string>
     */
    private function fetchBodies(): array
    {
        $bodies = [];
        $feeds = $this->feeds;

        $requests = function () use ($feeds) {
            foreach ($feeds as $idx => $feed) {
                yield $idx => new Request('GET', $feed->url, [
                    'User-Agent' => 'newspodcast/1.0',
                    'Accept' => 'application/rss+xml, application/atom+xml, application/xml;q=0.9, */*;q=0.8',
                ]);
            }
        };

        $pool = new Pool($this->http, $requests(), [
            'concurrency' => 5,
            'fulfilled' => function (ResponseInterface $response, int $idx) use (&$bodies, $feeds) {
                if ($response->getStatusCode() === 200) {
                    $bodies[$idx] = (string) $response->getBody();
                } else {
                    Log::warning('rss feed non-200', [
                        'feed' => $feeds[$idx]->name,
                        'status' => $response->getStatusCode(),
                    ]);
                }
            },
            'rejected' => function ($reason, int $idx) use ($feeds) {
                Log::warning('rss feed fetch failed', [
                    'feed' => $feeds[$idx]->name ?? $idx,
                    'reason' => (string) $reason,
                ]);
            },
        ]);

        $pool->promise()->wait();

        return $bodies;
    }

    private function extractBody(\SimplePie\Item $item): ?string
    {
        $content = $item->get_content();
        if (! $content) {
            return null;
        }

        $text = $this->clean(strip_tags($content));

        return mb_strlen($text) > 500 ? $text : null;
    }

    private function clean(string $s): string
    {
        return trim(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}

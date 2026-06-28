<?php

declare(strict_types=1);

namespace App\Infrastructure\Rss;

final class RssFeedConfig
{
    /**
     * @param  list<string>  $includeKeywords
     * @param  list<string>  $excludeKeywords
     */
    public function __construct(
        public readonly string $name,
        public readonly string $url,
        public readonly string $language,
        public readonly array $includeKeywords = [],
        public readonly array $excludeKeywords = [],
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            name: (string) $raw['name'],
            url: (string) $raw['url'],
            language: (string) $raw['language'],
            includeKeywords: array_values($raw['filters']['include_keywords'] ?? []),
            excludeKeywords: array_values($raw['filters']['exclude_keywords'] ?? []),
        );
    }
}

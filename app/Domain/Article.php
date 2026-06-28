<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

final class Article
{
    public function __construct(
        public readonly string $source,
        public readonly string $language,
        public readonly string $title,
        public readonly string $description,
        public readonly string $url,
        public readonly DateTimeImmutable $publishedAt,
        public readonly ?string $body = null,
    ) {}

    public function hasBody(): bool
    {
        return $this->body !== null && mb_strlen(trim($this->body)) > 100;
    }

    public function withBody(string $body): self
    {
        return new self(
            $this->source,
            $this->language,
            $this->title,
            $this->description,
            $this->url,
            $this->publishedAt,
            $body,
        );
    }
}

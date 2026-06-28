<?php

declare(strict_types=1);

namespace App\Application\Ports;

use App\Domain\Article;
use DateTimeImmutable;

interface ContentSource
{
    /**
     * Return articles published at or after $since.
     *
     * @return list<Article>
     */
    public function fetchSince(DateTimeImmutable $since): array;
}

<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

final class Episode
{
    public function __construct(
        public readonly DateTimeImmutable $date,
        public readonly Script $script,
        public readonly AudioBlob $audio,
    ) {}
}

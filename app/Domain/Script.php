<?php

declare(strict_types=1);

namespace App\Domain;

final class Script
{
    public function __construct(
        public readonly string $text,
        public readonly string $language,
    ) {}

    public function charCount(): int
    {
        return mb_strlen($this->text);
    }
}

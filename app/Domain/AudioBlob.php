<?php

declare(strict_types=1);

namespace App\Domain;

final class AudioBlob
{
    public function __construct(
        public readonly string $bytes,
        public readonly string $format,
        public readonly int $sampleRate,
        public readonly int $channels,
        public readonly int $bitsPerSample,
    ) {}
}

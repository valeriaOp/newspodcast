<?php

declare(strict_types=1);

namespace App\Infrastructure\PodcastPublisher;

use App\Domain\AudioBlob;
use InvalidArgumentException;

final class WavWriter
{
    /**
     * Wrap raw PCM bytes in a WAV container header.
     */
    public static function wrap(AudioBlob $blob): string
    {
        if ($blob->format !== 'pcm') {
            throw new InvalidArgumentException("WavWriter expects PCM input, got: {$blob->format}");
        }

        $dataSize = strlen($blob->bytes);
        $byteRate = (int) ($blob->sampleRate * $blob->channels * $blob->bitsPerSample / 8);
        $blockAlign = (int) ($blob->channels * $blob->bitsPerSample / 8);
        $fileSize = 36 + $dataSize;

        $header = 'RIFF';
        $header .= pack('V', $fileSize);
        $header .= 'WAVE';
        $header .= 'fmt ';
        $header .= pack('V', 16);                      // fmt chunk size
        $header .= pack('v', 1);                       // PCM format
        $header .= pack('v', $blob->channels);
        $header .= pack('V', $blob->sampleRate);
        $header .= pack('V', $byteRate);
        $header .= pack('v', $blockAlign);
        $header .= pack('v', $blob->bitsPerSample);
        $header .= 'data';
        $header .= pack('V', $dataSize);

        return $header.$blob->bytes;
    }
}

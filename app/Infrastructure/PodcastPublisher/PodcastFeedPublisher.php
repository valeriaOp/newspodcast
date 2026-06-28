<?php

declare(strict_types=1);

namespace App\Infrastructure\PodcastPublisher;

use App\Application\Ports\EpisodePublisher;
use App\Domain\Episode;
use RuntimeException;

final class PodcastFeedPublisher implements EpisodePublisher
{
    public function __construct(
        private readonly string $episodesDir,
        private readonly string $feedXmlPath,
        private readonly int $retention,
        private readonly PodcastRssXmlGenerator $xmlGenerator,
    ) {}

    public function publish(Episode $episode): void
    {
        $this->ensureDir($this->episodesDir);
        $this->ensureDir(dirname($this->feedXmlPath));

        $date = $episode->date->format('Y-m-d');
        $wavPath = "{$this->episodesDir}/{$date}.wav";
        $txtPath = "{$this->episodesDir}/{$date}.txt";

        file_put_contents($txtPath, $episode->script->text);

        $wavBytes = $episode->audio->format === 'pcm'
            ? WavWriter::wrap($episode->audio)
            : $episode->audio->bytes;

        $tmpPath = $wavPath.'.tmp';
        if (file_put_contents($tmpPath, $wavBytes) === false) {
            throw new RuntimeException("Failed to write audio to {$tmpPath}");
        }
        if (! rename($tmpPath, $wavPath)) {
            throw new RuntimeException("Failed to rename {$tmpPath} to {$wavPath}");
        }

        $this->rotate();
        $this->regenerateFeedXml();
    }

    private function rotate(): void
    {
        $wavFiles = glob("{$this->episodesDir}/*.wav") ?: [];
        usort($wavFiles, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        $toDelete = array_slice($wavFiles, $this->retention);
        foreach ($toDelete as $wav) {
            $txt = preg_replace('/\.wav$/', '.txt', $wav);
            @unlink($wav);
            if (is_string($txt) && file_exists($txt)) {
                @unlink($txt);
            }
        }
    }

    private function regenerateFeedXml(): void
    {
        $wavFiles = glob("{$this->episodesDir}/*.wav") ?: [];
        usort($wavFiles, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        $episodes = [];
        foreach ($wavFiles as $wav) {
            $date = basename($wav, '.wav');
            $txt = preg_replace('/\.wav$/', '.txt', $wav);
            $script = (is_string($txt) && file_exists($txt)) ? (string) file_get_contents($txt) : '';

            $episodes[] = [
                'date' => $date,
                'audioFilename' => basename($wav),
                'audioSize' => (int) filesize($wav),
                'scriptText' => $script,
            ];
        }

        $xml = $this->xmlGenerator->generate($episodes);
        file_put_contents($this->feedXmlPath, $xml);
    }

    private function ensureDir(string $path): void
    {
        if (! is_dir($path) && ! @mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException("Failed to create directory: {$path}");
        }
    }
}

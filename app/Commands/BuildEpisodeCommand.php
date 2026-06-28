<?php

declare(strict_types=1);

namespace App\Commands;

use App\Application\BuildEpisodeUseCase;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use LaravelZero\Framework\Commands\Command;
use Throwable;

final class BuildEpisodeCommand extends Command
{
    protected $signature = 'podcast:build';

    protected $description = "Build today's podcast episode from configured RSS feeds";

    public function handle(BuildEpisodeUseCase $useCase): int
    {
        $start = microtime(true);

        try {
            $episode = $useCase->execute(
                now: new DateTimeImmutable,
                lookbackHours: (int) config('feeds.lookback_hours', 24),
                maxArticles: (int) config('feeds.max_articles_per_episode', 10),
                language: (string) config('feeds.output_language', 'uk'),
                scriptMaxChars: (int) config('feeds.script_max_chars', 4500),
            );

            $elapsed = round(microtime(true) - $start, 1);
            $this->info(sprintf(
                'Episode built: %s — script %d chars, audio %.1f MB (%.1fs)',
                $episode->date->format('Y-m-d'),
                $episode->script->charCount(),
                strlen($episode->audio->bytes) / 1024 / 1024,
                $elapsed,
            ));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Build failed: '.$e->getMessage());
            Log::error('podcast build failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}

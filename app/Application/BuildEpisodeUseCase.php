<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Ports\ArticleSelector;
use App\Application\Ports\ContentSource;
use App\Application\Ports\EpisodePublisher;
use App\Application\Ports\ScriptWriter;
use App\Application\Ports\SpeechSynthesizer;
use App\Domain\Episode;
use App\Infrastructure\ArticleBody\ArticleBodyResolver;
use DateTimeImmutable;
use RuntimeException;

final class BuildEpisodeUseCase
{
    public function __construct(
        private readonly ContentSource $source,
        private readonly ArticleSelector $selector,
        private readonly ArticleBodyResolver $bodyResolver,
        private readonly ScriptWriter $writer,
        private readonly SpeechSynthesizer $synthesizer,
        private readonly EpisodePublisher $publisher,
    ) {}

    public function execute(
        DateTimeImmutable $now,
        int $lookbackHours,
        int $maxArticles,
        string $language,
        int $scriptMaxChars,
    ): Episode {
        $since = $now->modify("-{$lookbackHours} hours");

        $candidates = $this->source->fetchSince($since);
        if ($candidates === []) {
            throw new RuntimeException('No articles found within the lookback window');
        }

        $selected = $this->selector->select($candidates, $maxArticles);
        if ($selected === []) {
            throw new RuntimeException('Selector returned no articles');
        }

        foreach ($selected as $i => $article) {
            if (! $article->hasBody()) {
                $selected[$i] = $this->bodyResolver->resolve($article);
            }
        }
        $selected = array_values(array_filter($selected, fn ($a) => $a->hasBody()));

        if ($selected === []) {
            throw new RuntimeException('Body resolution failed for all selected articles');
        }

        $script = $this->writer->write($selected, $language, $scriptMaxChars);
        $audio = $this->synthesizer->synthesize($script);

        $episode = new Episode($now, $script, $audio);
        $this->publisher->publish($episode);

        return $episode;
    }
}

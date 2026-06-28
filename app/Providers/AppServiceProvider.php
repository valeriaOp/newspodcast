<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Filtering\KeywordFilter;
use App\Application\Ports\ArticleSelector;
use App\Application\Ports\ContentSource;
use App\Application\Ports\EpisodePublisher;
use App\Application\Ports\ScriptWriter;
use App\Application\Ports\SpeechSynthesizer;
use App\Infrastructure\Gemini\GeminiArticleSelector;
use App\Infrastructure\Gemini\GeminiHttpClient;
use App\Infrastructure\Gemini\GeminiScriptWriter;
use App\Infrastructure\Gemini\GeminiSpeechSynthesizer;
use App\Infrastructure\PodcastPublisher\PodcastFeedPublisher;
use App\Infrastructure\PodcastPublisher\PodcastRssXmlGenerator;
use App\Infrastructure\Prompts\PromptLoader;
use App\Infrastructure\Rss\RssContentSource;
use App\Infrastructure\Rss\RssFeedConfig;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, fn () => new Client([
            'timeout' => 15,
            'http_errors' => false,
        ]));

        $this->app->singleton(GeminiHttpClient::class, fn ($app) => new GeminiHttpClient(
            $app->make(Client::class),
            (string) config('podcast.gemini_api_key', ''),
        ));

        $this->app->singleton(PromptLoader::class, fn () => new PromptLoader(
            (string) config('podcast.prompts_dir', base_path('storage/prompts')),
        ));

        $this->app->bind(ContentSource::class, function ($app) {
            $feeds = array_map(
                fn (array $f) => RssFeedConfig::fromArray($f),
                (array) config('feeds.feeds', []),
            );

            return new RssContentSource(
                $feeds,
                $app->make(Client::class),
                new KeywordFilter,
            );
        });

        $this->app->bind(ArticleSelector::class, GeminiArticleSelector::class);
        $this->app->bind(ScriptWriter::class, GeminiScriptWriter::class);

        $this->app->bind(SpeechSynthesizer::class, fn ($app) => new GeminiSpeechSynthesizer(
            $app->make(GeminiHttpClient::class),
            (string) config('podcast.tts_voice_1', 'Puck'),
            (string) config('podcast.tts_voice_2', 'Aoede'),
            (string) config('podcast.tts_style', ''),
        ));

        $this->app->bind(EpisodePublisher::class, fn ($app) => new PodcastFeedPublisher(
            (string) config('podcast.episodes_dir', base_path('storage/episodes')),
            (string) config('podcast.feed_xml_path', base_path('storage/episodes/feed.xml')),
            (int) config('podcast.retention', 14),
            new PodcastRssXmlGenerator(
                title: (string) config('podcast.title', 'News Digest'),
                description: (string) config('podcast.description', 'Daily AI-summarized news'),
                baseUrl: (string) config('podcast.base_url', 'https://example.com/podcast/'),
                author: (string) config('podcast.author', 'newspodcast'),
                language: (string) config('feeds.output_language', 'uk'),
            ),
        ));
    }

    public function boot(): void {}
}

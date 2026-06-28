<?php

declare(strict_types=1);

return [
    'lookback_hours' => (int) env('PODCAST_LOOKBACK_HOURS', 24),
    'max_articles_per_episode' => (int) env('PODCAST_MAX_ARTICLES', 10),
    'output_language' => env('PODCAST_OUTPUT_LANGUAGE', 'uk'),
    'script_max_chars' => (int) env('PODCAST_SCRIPT_MAX_CHARS', 4500),

    'feeds' => [
        [
            'name' => 'Уніан',
            'url' => 'https://rss.unian.net/site/news_ukr.rss',
            'language' => 'uk',
            'filters' => [
                'include_keywords' => [],
                'exclude_keywords' => [],
            ],
        ],
        [
            'name' => 'BBC World News',
            'url' => 'http://feeds.bbci.co.uk/news/world/rss.xml',
            'language' => 'en',
            'filters' => [
                'include_keywords' => [],
                'exclude_keywords' => [],
            ],
        ],
    ],
];

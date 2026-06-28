<?php

declare(strict_types=1);

return [
    'gemini_api_key' => env('GEMINI_API_KEY', ''),
    'tts_voice_1' => env('PODCAST_TTS_VOICE_1', 'Puck'),
    'tts_voice_2' => env('PODCAST_TTS_VOICE_2', 'Aoede'),
    'tts_style' => env('PODCAST_TTS_STYLE', 'Read aloud in a casual, affirmative, business-like tone.'),

    'prompts_dir' => env('PODCAST_PROMPTS_DIR', base_path('storage/prompts')),

    'episodes_dir' => env('PODCAST_EPISODES_DIR', base_path('storage/episodes')),
    'feed_xml_path' => env('PODCAST_FEED_XML', base_path('storage/episodes/feed.xml')),
    'retention' => (int) env('PODCAST_RETENTION', 14),

    'title' => env('PODCAST_TITLE', 'News Digest'),
    'description' => env('PODCAST_DESCRIPTION', 'Daily AI-summarized news'),
    'base_url' => env('PODCAST_BASE_URL', 'https://example.com/podcast/'),
    'author' => env('PODCAST_AUTHOR', 'newspodcast'),
];

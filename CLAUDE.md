# newspodcast

Personal pet project: AI-summarized daily news podcast. Pulls multiple RSS feeds (Ukrainian + English), uses an LLM to pick the most important stories and write a Ukrainian-language podcast script, runs TTS, and exposes the result as a public podcast RSS feed for subscription in Snipd.

## Stack decisions

- **Framework**: Laravel Zero (PHP 8.2+). CLI-only — no web admin needed in v1.
- **LLM**: Google Gemini 2.5 Flash via REST (`generativelanguage.googleapis.com`). Used for both article selection and Ukrainian script generation. Free tier covers all usage with large margin.
- **TTS**: Google Gemini 2.5 Flash TTS preview (`gemini-2.5-flash-preview-tts`). Single call per episode, no chunking. Default voice: **Charon** (male, informative tone) — adjustable later.
- **Audio format**: WAV (PCM-in-WAV header wrapped in PHP). The hosting provider does NOT support ffmpeg, so MP3 conversion is off the table for v1. ~14 MB per 5-minute episode.
- **Storage**: Local disk only. No database in v1 — file-based state (`.wav` + `.txt` sidecar per episode). May add SQLite later for cross-run article dedup.
- **Hosting**: cPanel shared hosting (free via Namecheap employee discount). Constraints: no ffmpeg, Python availability unconfirmed, cron job runtime limit unconfirmed.

## Hard constraints

- **$0/month operating cost.** All choices must stay within free tiers.
- **No ffmpeg** on host — anything requiring audio transcoding/concatenation is out.
- **One TTS call per episode** — don't split the script into chunks.
- **Public RSS feed**, no auth (random URL slug is fine for obscurity).

## Schedule + scope

- Cron runs **once per 24h**.
- Episode target: **≤ 5 minutes** (~4500 chars Ukrainian script).
- Article count per episode: **1–10**, LLM decides based on what's important.
- Retention: **14 episodes** rolling window.
- Languages: pulls UA + EN sources, output is always Ukrainian.

## File layout

```
~/podcast/                          # outside public_html
├── app/                            # Laravel Zero code
├── config/feeds.php                # feed URLs + per-feed filters (PHP array)
├── storage/logs/
└── .env                            # GEMINI_API_KEY etc.

~/public_html/podcast/              # web-accessible
├── feed.xml                        # podcast RSS, regenerated each run
└── episodes/
    ├── 2026-05-30.wav              # audio
    └── 2026-05-30.txt              # script (used as RSS <description>)
```

`feed.xml` is regenerated from a directory listing each run — no DB table needed for episode metadata.

## Workflow (one cron invocation)

1. Load `config/feeds.php`
2. Fetch all RSS feeds in parallel (Guzzle pool)
3. Filter: published in last 24h + per-feed include/exclude rules
4. LLM call #1 — selection: pass candidate titles+descriptions, get back IDs of 1–10 important articles
5. For each selected article: use `<content:encoded>` if present, else fetch URL + Readability; fallback to `r.jina.ai` reader proxy on block/empty
6. LLM call #2 — script generation: full articles in → Ukrainian podcast script out (≤4500 chars enforced via prompt)
7. Save script as `YYYY-MM-DD.txt`
8. TTS call — Gemini Flash TTS returns base64 PCM → wrap in WAV header → write `YYYY-MM-DD.wav.tmp` → rename on success
9. Rotation: keep newest 14 `.wav` + `.txt` pairs, delete the rest
10. Regenerate `public_html/podcast/feed.xml` from directory listing

## Dependencies

Beyond Laravel Zero itself, three extras:

```bash
composer require guzzlehttp/guzzle simplepie/simplepie fivefilters/readability.php
```

| Package | Purpose |
|---|---|
| `guzzlehttp/guzzle` | HTTP client — Gemini API calls, parallel RSS fetches (`Pool`), article URL fetches |
| `simplepie/simplepie` | RSS/Atom parsing |
| `fivefilters/readability.php` | Strip article HTML to plain text body when the RSS feed doesn't include full content |

Config is a plain PHP array (`config/feeds.php`), not YAML — so no `symfony/yaml` dependency.

## Host capabilities (verified)

- [x] PHP 8.2 available (host supports up to 8.5)
- [x] Cron job max runtime: 900s (well above our ~60s flow)
- [x] Disk quota: 20 GB (vs ~200 MB needed for 14 episodes)
- [x] Outbound HTTPS to `generativelanguage.googleapis.com` works (verified — returns 403 as expected)
- [x] HTTPS configured on the domain that will serve `feed.xml`

## User preferences (learned during planning)

- Prefers the leanest viable solution. Symfony was rejected as "excessive" → Laravel Zero. SQLite was deferred → file-based state. Audio chunking + ffmpeg was rejected → single TTS call + WAV.
- Don't propose unnecessary infrastructure. If a feature can be added later when actually needed, defer it.
- Has strong PHP/Symfony + Node.js background — explanations can assume framework-level fluency.
- All AI services must have a free tier path; paid services are only acceptable as documented fallbacks.

## Known risks / future swaps

- **Gemini 2.5 Flash TTS is in preview** — if it changes/breaks, fall back to Microsoft Edge TTS (free, requires Python on host) or Azure Speech (free tier 500k chars/month, requires Azure signup).
- **WAV file size** — if 14 MB/episode becomes a storage problem, options are: shorter episodes, fewer retained, or installing a static ffmpeg binary in `~/bin` to transcode to MP3.
- **No article dedup across runs** in v1 — acceptable because cron runs daily and filter window matches. If we move to multiple runs/day, add SQLite `seen_articles` table.

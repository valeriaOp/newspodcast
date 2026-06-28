<?php

declare(strict_types=1);

namespace App\Infrastructure\PodcastPublisher;

use DateTimeImmutable;
use DateTimeZone;

final class PodcastRssXmlGenerator
{
    public function __construct(
        private readonly string $title,
        private readonly string $description,
        private readonly string $baseUrl,
        private readonly string $author,
        private readonly string $language = 'uk',
    ) {}

    /**
     * @param  list<array{date: string, audioFilename: string, audioSize: int, scriptText: string}>  $episodes
     */
    public function generate(array $episodes): string
    {
        $buildDate = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_RSS);
        $title = $this->esc($this->title);
        $description = $this->esc($this->description);
        $author = $this->esc($this->author);
        $base = rtrim($this->baseUrl, '/').'/';

        $itemsXml = '';
        foreach ($episodes as $ep) {
            $pubDate = (new DateTimeImmutable($ep['date'].'T07:00:00', new DateTimeZone('UTC')))->format(DATE_RSS);
            $url = $base.'episodes/'.$ep['audioFilename'];
            $itemTitle = $this->esc('News digest '.$ep['date']);
            $itemDesc = $this->esc($ep['scriptText']);

            $itemsXml .= "    <item>\n";
            $itemsXml .= "      <title>{$itemTitle}</title>\n";
            $itemsXml .= "      <description>{$itemDesc}</description>\n";
            $itemsXml .= "      <pubDate>{$pubDate}</pubDate>\n";
            $itemsXml .= "      <enclosure url=\"{$url}\" length=\"{$ep['audioSize']}\" type=\"audio/wav\"/>\n";
            $itemsXml .= "      <guid isPermaLink=\"false\">{$this->esc($ep['date'])}</guid>\n";
            $itemsXml .= "    </item>\n";
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
  <channel>
    <title>{$title}</title>
    <link>{$base}</link>
    <description>{$description}</description>
    <language>{$this->language}</language>
    <itunes:author>{$author}</itunes:author>
    <itunes:explicit>false</itunes:explicit>
    <lastBuildDate>{$buildDate}</lastBuildDate>
{$itemsXml}  </channel>
</rss>
XML;
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}

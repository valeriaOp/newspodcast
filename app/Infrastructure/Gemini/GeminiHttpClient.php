<?php

declare(strict_types=1);

namespace App\Infrastructure\Gemini;

use GuzzleHttp\Client;
use RuntimeException;

final class GeminiHttpClient
{
    public function __construct(
        private readonly Client $http,
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta',
    ) {
        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not configured');
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function generateContent(string $model, array $body, int $timeout = 60): array
    {
        $response = $this->http->post(
            "{$this->baseUrl}/models/{$model}:generateContent",
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ],
                'json' => $body,
                'timeout' => $timeout,
                'http_errors' => false,
            ],
        );

        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();

        if ($status >= 400) {
            throw new RuntimeException("Gemini API error (HTTP {$status}): {$raw}");
        }

        try {
            return json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('Gemini API returned invalid JSON: '.$e->getMessage());
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\ArticleBody;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

final class JinaReaderClient
{
    public function __construct(private readonly Client $http) {}

    public function extract(string $url): ?string
    {
        try {
            $response = $this->http->get('https://r.jina.ai/'.$url, [
                'timeout' => 20,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                Log::info('jina reader non-200', ['url' => $url, 'status' => $response->getStatusCode()]);

                return null;
            }

            $body = trim((string) $response->getBody());

            return $body !== '' ? $body : null;
        } catch (Throwable $e) {
            Log::info('jina reader failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }
}

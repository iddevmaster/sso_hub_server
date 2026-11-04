<?php

namespace App\Integrations\Dtms;

use App\Integrations\Dtms\Exceptions\CursorExpiredException;
use App\Integrations\Dtms\Exceptions\DtmsAuthException;
use App\Integrations\Dtms\Exceptions\DtmsUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DtmsClient implements FeedClient
{
    public function changes(string $after = '0', int $limit = 200): array
    {
        $baseUrl = rtrim((string) config('dtms.url'), '/');
        $token = (string) config('dtms.token');

        if ($baseUrl === '' || $token === '') {
            throw new DtmsAuthException('DTMS_SYNC_URL / DTMS_SYNC_TOKEN not configured');
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(20)
                ->connectTimeout(5)
                ->get($baseUrl.'/api/integrations/sso/changes', [
                    'after' => $after,
                    'limit' => $limit,
                ]);
        } catch (ConnectionException $e) {
            throw new DtmsUnavailableException('DTMS unreachable (connection error)');
        }

        $status = $response->status();

        if ($status === 200) {
            return $this->normalize($response->json());
        }

        if ($status === 404) {
            throw new DtmsAuthException('DTMS returned 404: the SSO changes feed is disabled on DTMS');
        }

        if (in_array($status, [400, 401, 403], true)) {
            throw new DtmsAuthException("DTMS rejected the request (HTTP {$status})");
        }

        if ($status === 410) {
            throw new CursorExpiredException('DTMS cursor expired (HTTP 410)');
        }

        if ($status === 429 || $status >= 500) {
            throw new DtmsUnavailableException("DTMS unavailable (HTTP {$status})");
        }

        throw new DtmsUnavailableException("Unexpected DTMS response (HTTP {$status})");
    }

    private function normalize(mixed $body): array
    {
        if (
            ! is_array($body)
            || ! isset($body['items']) || ! is_array($body['items'])
            || ! isset($body['next_cursor']) || ! (is_string($body['next_cursor']) || is_int($body['next_cursor']))
            || ! isset($body['has_more']) || ! is_bool($body['has_more'])
        ) {
            throw new DtmsUnavailableException('Malformed DTMS response body');
        }

        return [
            'items' => array_values($body['items']),
            'next_cursor' => (string) $body['next_cursor'],
            'has_more' => $body['has_more'],
        ];
    }
}

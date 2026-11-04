<?php

namespace Tests\Feature\Dtms;

use App\Integrations\Dtms\DtmsClient;
use App\Integrations\Dtms\Exceptions\CursorExpiredException;
use App\Integrations\Dtms\Exceptions\DtmsAuthException;
use App\Integrations\Dtms\Exceptions\DtmsUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DtmsClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['dtms.url' => 'https://dtms.test/', 'dtms.token' => 'secret-test-token']);
    }

    public function test_success_sends_query_and_bearer_header(): void
    {
        Http::fake(['*' => Http::response([
            'items' => [['id' => 1]],
            'next_cursor' => 'abc',
            'has_more' => true,
        ], 200)]);

        $result = (new DtmsClient)->changes('10', 50);

        $this->assertSame([['id' => 1]], $result['items']);
        $this->assertSame('abc', $result['next_cursor']);
        $this->assertTrue($result['has_more']);

        Http::assertSent(function (Request $r) {
            return str_starts_with($r->url(), 'https://dtms.test/api/integrations/sso/changes?')
                && str_contains($r->url(), 'after=10')
                && str_contains($r->url(), 'limit=50')
                && $r->hasHeader('Authorization', 'Bearer secret-test-token')
                && $r->hasHeader('Accept', 'application/json');
        });
    }

    public function test_cursor_is_cast_to_string(): void
    {
        Http::fake(['*' => Http::response(['items' => [], 'next_cursor' => 48213, 'has_more' => false], 200)]);

        $result = (new DtmsClient)->changes();

        $this->assertSame('48213', $result['next_cursor']);
        $this->assertFalse($result['has_more']);
    }

    /** @dataProvider authStatuses */
    public function test_auth_statuses_throw_auth_exception(int $status): void
    {
        Http::fake(['*' => Http::response(['message' => 'x'], $status)]);

        $this->expectException(DtmsAuthException::class);
        (new DtmsClient)->changes();
    }

    public static function authStatuses(): array
    {
        return [[400], [401], [403], [404]];
    }

    public function test_404_message_mentions_disabled_feed(): void
    {
        Http::fake(['*' => Http::response('', 404)]);

        try {
            (new DtmsClient)->changes();
            $this->fail('expected exception');
        } catch (DtmsAuthException $e) {
            $this->assertStringContainsString('disabled', $e->getMessage());
            $this->assertStringNotContainsString('secret-test-token', $e->getMessage());
        }
    }

    public function test_410_throws_cursor_expired(): void
    {
        Http::fake(['*' => Http::response('', 410)]);

        $this->expectException(CursorExpiredException::class);
        (new DtmsClient)->changes('5');
    }

    /** @dataProvider unavailableStatuses */
    public function test_unavailable_statuses(int $status): void
    {
        Http::fake(['*' => Http::response('', $status)]);

        $this->expectException(DtmsUnavailableException::class);
        (new DtmsClient)->changes();
    }

    public static function unavailableStatuses(): array
    {
        return [[429], [500], [502], [503], [302], [418]];
    }

    /** @dataProvider malformedBodies */
    public function test_malformed_body_throws_unavailable(mixed $body): void
    {
        Http::fake(['*' => Http::response($body, 200)]);

        $this->expectException(DtmsUnavailableException::class);
        (new DtmsClient)->changes();
    }

    public static function malformedBodies(): array
    {
        return [
            'not json' => ['<html>oops</html>'],
            'missing items' => [['next_cursor' => '1', 'has_more' => false]],
            'items not array' => [['items' => 'x', 'next_cursor' => '1', 'has_more' => false]],
            'missing cursor' => [['items' => [], 'has_more' => false]],
            'missing has_more' => [['items' => [], 'next_cursor' => '1']],
            'has_more not bool' => [['items' => [], 'next_cursor' => '1', 'has_more' => 'yes']],
        ];
    }

    public function test_connection_error_throws_unavailable(): void
    {
        Http::fake(function () {
            throw new ConnectionException('boom');
        });

        $this->expectException(DtmsUnavailableException::class);
        (new DtmsClient)->changes();
    }

    public function test_missing_config_throws_auth_exception_without_calling_http(): void
    {
        Http::fake();

        foreach ([['dtms.url', ''], ['dtms.token', null]] as [$key, $value]) {
            config(['dtms.url' => 'https://dtms.test', 'dtms.token' => 't', $key => $value]);

            try {
                (new DtmsClient)->changes();
                $this->fail('expected exception');
            } catch (DtmsAuthException $e) {
                $this->assertStringContainsString('not configured', $e->getMessage());
            }
        }

        Http::assertNothingSent();
    }
}

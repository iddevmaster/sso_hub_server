<?php

namespace Tests\Feature\Dtms;

use App\Integrations\Dtms\ApplyResult;
use App\Integrations\Dtms\Exceptions\CursorExpiredException;
use App\Integrations\Dtms\Exceptions\DtmsAuthException;
use App\Integrations\Dtms\Exceptions\DtmsUnavailableException;
use App\Integrations\Dtms\FeedClient;
use App\Integrations\Dtms\ItemApplier;
use App\Integrations\Dtms\SyncReport;
use App\Integrations\Dtms\SyncRunner;
use App\Models\DtmsSyncFailure;
use App\Models\DtmsSyncState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SyncRunnerTest extends TestCase
{
    use RefreshDatabase;

    private function client(array $queue): object
    {
        return new class($queue) implements FeedClient {
            public array $calls = [];

            public function __construct(public array $queue)
            {
            }

            public function changes(string $after = '0', int $limit = 200): array
            {
                $this->calls[] = [$after, $limit];
                $next = array_shift($this->queue);
                if ($next instanceof \Throwable) {
                    throw $next;
                }

                return $next;
            }
        };
    }

    private function applier(array $outcomes = []): object
    {
        return new class($outcomes) implements ItemApplier {
            public array $applied = [];

            public function __construct(public array $outcomes)
            {
            }

            public function apply(array $item, bool $dryRun = false): ApplyResult
            {
                $this->applied[] = [$item, $dryRun];
                $o = $this->outcomes[$item['id']] ?? ApplyResult::CREATED;

                return $o === ApplyResult::FAILED
                    ? ApplyResult::failed($item, 'boom')
                    : ApplyResult::of($o, $item);
            }
        };
    }

    private function page(array $ids, string $next, bool $more, string $type = 'student'): array
    {
        return [
            'items' => array_map(fn ($id) => ['id' => $id, 'type' => $type], $ids),
            'next_cursor' => $next,
            'has_more' => $more,
        ];
    }

    private function runner(object $client, object $applier): SyncRunner
    {
        return new SyncRunner($client, $applier);
    }

    private function cursor(): string
    {
        return (string) DtmsSyncState::first()->cursor;
    }

    public function test_multi_page_loop_persists_cursor_and_counts(): void
    {
        $client = $this->client([
            $this->page(['a', 'b'], '10', true),
            $this->page(['c'], '20', false, 'course'),
        ]);
        $applier = $this->applier(['b' => ApplyResult::UPDATED]);

        $report = $this->runner($client, $applier)->run(false, 2);

        $this->assertSame([['0', 2], ['10', 2]], $client->calls);
        $this->assertSame('20', $this->cursor());
        $this->assertNotNull(DtmsSyncState::first()->last_success_at);
        $this->assertSame(2, $report->pages);
        $this->assertSame(2, $report->counts[ApplyResult::CREATED]);
        $this->assertSame(1, $report->counts[ApplyResult::UPDATED]);
        $this->assertSame(1, $report->byType['course'][ApplyResult::CREATED]);
        $this->assertSame(1, $report->byType['student'][ApplyResult::CREATED]);
        $this->assertSame(1, $report->byType['student'][ApplyResult::UPDATED]);
        $this->assertTrue($report->isSuccessful());
    }

    public function test_uses_configured_page_size_by_default(): void
    {
        config(['dtms.page_size' => 7]);
        $client = $this->client([$this->page([], '0', false)]);

        $this->runner($client, $this->applier())->run();

        $this->assertSame([['0', 7]], $client->calls);
    }

    public function test_cursor_not_persisted_when_second_page_throws(): void
    {
        $client = $this->client([
            $this->page(['a'], '10', true),
            new DtmsUnavailableException('down'),
        ]);

        $report = $this->runner($client, $this->applier())->run();

        $this->assertSame('10', $this->cursor()); // first page was complete
        $this->assertSame(SyncReport::ERROR_UNAVAILABLE, $report->error);
        $this->assertFalse($report->isSuccessful());
    }

    public function test_first_page_exception_leaves_cursor_untouched(): void
    {
        DtmsSyncState::create(['cursor' => '5']);

        foreach ([
            [new DtmsAuthException('x'), SyncReport::ERROR_AUTH],
            [new CursorExpiredException('x'), SyncReport::ERROR_CURSOR_EXPIRED],
            [new DtmsUnavailableException('x'), SyncReport::ERROR_UNAVAILABLE],
        ] as [$e, $kind]) {
            $applier = $this->applier();
            $report = $this->runner($this->client([$e]), $applier)->run();
            $this->assertSame($kind, $report->error);
            $this->assertSame('5', $this->cursor());
            $this->assertSame([], $applier->applied);
        }
    }

    public function test_cursor_persisted_only_after_whole_page_applied(): void
    {
        $seen = [];
        $applier = new class($seen) implements ItemApplier {
            public function __construct(public array &$seen)
            {
            }

            public function apply(array $item, bool $dryRun = false): ApplyResult
            {
                $this->seen[] = DtmsSyncState::first()->cursor;

                return ApplyResult::of(ApplyResult::CREATED, $item);
            }
        };

        $this->runner($this->client([$this->page(['a', 'b', 'c'], '9', false)]), $applier)->run();

        $this->assertSame(['0', '0', '0'], $seen);
        $this->assertSame('9', $this->cursor());
    }

    public function test_dry_run_never_persists(): void
    {
        $client = $this->client([
            $this->page(['a'], '10', true),
            $this->page(['b'], '20', false),
        ]);
        $applier = $this->applier();

        $report = $this->runner($client, $applier)->run(true);

        $this->assertSame([['0', 200], ['10', 200]], $client->calls);
        $this->assertSame('0', $this->cursor());
        $this->assertNull(DtmsSyncState::first()->last_success_at);
        $this->assertSame(2, $report->counts[ApplyResult::CREATED]);
        $this->assertTrue($applier->applied[0][1]);
    }

    public function test_failed_items_do_not_stop_run(): void
    {
        $applier = $this->applier(['a' => ApplyResult::FAILED]);
        $client = $this->client([$this->page(['a', 'b'], '3', false)]);

        $report = $this->runner($client, $applier)->run();

        $this->assertCount(2, $applier->applied);
        $this->assertSame(1, $report->failedCount());
        $this->assertSame('a', $report->failures[0]->entityId);
        $this->assertSame('3', $this->cursor());
        $this->assertFalse($report->isSuccessful());
    }

    public function test_lock_contention_skips(): void
    {
        $lock = Cache::lock('dtms-sync', 60);
        $this->assertTrue($lock->get());

        $client = $this->client([$this->page(['a'], '1', false)]);
        $report = $this->runner($client, $this->applier())->run();

        $this->assertTrue($report->skipped);
        $this->assertSame([], $client->calls);

        $lock->release();
        $this->assertFalse($this->runner($client, $this->applier())->run()->skipped);
    }

    public function test_lock_released_after_exception(): void
    {
        $this->runner($this->client([new DtmsAuthException('x')]), $this->applier())->run();

        $lock = Cache::lock('dtms-sync', 5);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_stall_guard_stops_when_cursor_does_not_advance(): void
    {
        $client = $this->client([
            $this->page(['a'], '0', true),
            $this->page(['b'], '0', true),
        ]);

        $report = $this->runner($client, $this->applier())->run();

        $this->assertTrue($report->stalled);
        $this->assertCount(1, $client->calls);
        $this->assertFalse($report->isSuccessful());
    }

    public function test_retry_failures_reapplies_unresolved_payloads_only(): void
    {
        DtmsSyncState::create(['cursor' => '4']);
        DtmsSyncFailure::create(['event_type' => 'student', 'entity_id' => 'x', 'error' => 'e', 'payload' => ['id' => 'x', 'type' => 'student']]);
        DtmsSyncFailure::create(['event_type' => 'student', 'entity_id' => 'y', 'error' => 'e', 'payload' => ['id' => 'y', 'type' => 'student']]);
        DtmsSyncFailure::create(['event_type' => 'student', 'entity_id' => 'z', 'error' => 'e', 'payload' => ['id' => 'z', 'type' => 'student'], 'resolved_at' => now()]);

        $applier = $this->applier(['y' => ApplyResult::FAILED, 'x' => ApplyResult::UPDATED]);
        $client = $this->client([]);

        $report = $this->runner($client, $applier)->retryFailures();

        $this->assertSame(['x', 'y'], array_map(fn ($a) => $a[0]['id'], $applier->applied));
        $this->assertSame(1, $report->counts[ApplyResult::UPDATED]);
        $this->assertSame(1, $report->failedCount());
        $this->assertSame([], $client->calls);
        $this->assertSame('4', $this->cursor());

        $this->runner($client, $applier)->retryFailures(true);
        $this->assertTrue(end($applier->applied)[1]);
    }
}

<?php

namespace Tests\Feature\Dtms;

use App\Integrations\Dtms\ApplyResult;
use App\Integrations\Dtms\Exceptions\CursorExpiredException;
use App\Integrations\Dtms\Exceptions\DtmsAuthException;
use App\Integrations\Dtms\FeedClient;
use App\Integrations\Dtms\ItemApplier;
use App\Models\DtmsSyncState;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DtmsSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    private function bindFakes(array $queue, array $outcomes = []): void
    {
        $client = new class($queue) implements FeedClient {
            public function __construct(public array $queue)
            {
            }

            public function changes(string $after = '0', int $limit = 200): array
            {
                $next = array_shift($this->queue);
                if ($next instanceof \Throwable) {
                    throw $next;
                }

                return $next;
            }
        };
        $applier = new class($outcomes) implements ItemApplier {
            public function __construct(public array $outcomes)
            {
            }

            public function apply(array $item, bool $dryRun = false): ApplyResult
            {
                $o = $this->outcomes[$item['id']] ?? ApplyResult::CREATED;

                return $o === ApplyResult::FAILED ? ApplyResult::failed($item, 'boom') : ApplyResult::of($o, $item);
            }
        };
        $this->app->instance(FeedClient::class, $client);
        $this->app->instance(ItemApplier::class, $applier);
    }

    private function configure(): void
    {
        config(['dtms.url' => 'https://dtms.example.test', 'dtms.token' => 'test-token']);
    }

    private function page(array $ids, string $next = '5'): array
    {
        return [
            'items' => array_map(fn ($id) => ['id' => $id, 'type' => 'student'], $ids),
            'next_cursor' => $next,
            'has_more' => false,
        ];
    }

    public function test_success_exit_zero_and_cursor_saved(): void
    {
        $this->configure();
        $this->bindFakes([$this->page(['a'])]);

        $this->artisan('dtms:sync')->assertExitCode(0);

        $this->assertSame('5', DtmsSyncState::first()->cursor);
    }

    public function test_failed_item_exits_one(): void
    {
        $this->configure();
        $this->bindFakes([$this->page(['a', 'b'])], ['a' => ApplyResult::FAILED]);

        $this->artisan('dtms:sync')->assertExitCode(1);
    }

    public function test_client_errors_exit_one(): void
    {
        $this->configure();
        $this->bindFakes([new DtmsAuthException('no')]);
        $this->artisan('dtms:sync')->assertExitCode(1);

        $this->bindFakes([new CursorExpiredException('gone')]);
        $this->artisan('dtms:sync')->assertExitCode(1);
    }

    public function test_dry_run_does_not_save_cursor(): void
    {
        $this->configure();
        $this->bindFakes([$this->page(['a'])]);

        $this->artisan('dtms:sync --dry-run')->assertExitCode(0);

        $this->assertSame('0', DtmsSyncState::first()->cursor);
    }

    public function test_missing_config_fails_fast(): void
    {
        config(['dtms.url' => null, 'dtms.token' => null]);
        $this->bindFakes([$this->page(['a'])]);

        $this->artisan('dtms:sync')->assertExitCode(1);

        $this->assertSame(0, DtmsSyncState::count());
    }

    public function test_lock_contention_is_success_with_warning(): void
    {
        $this->configure();
        $this->bindFakes([$this->page(['a'])]);
        $lock = Cache::lock('dtms-sync', 60);
        $lock->get();

        $this->artisan('dtms:sync')->assertExitCode(0);

        $lock->release();
    }

    public function test_retry_failures_option_runs(): void
    {
        $this->configure();
        $this->bindFakes([]);

        $this->artisan('dtms:sync --retry-failures')->assertExitCode(0);
    }

    public function test_scheduler_registers_every_minute_without_overlapping(): void
    {
        $events = collect($this->app->make(Schedule::class)->events())
            ->filter(fn ($e) => str_contains($e->command, 'dtms:sync'));

        $this->assertCount(1, $events);
        $event = $events->first();
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertTrue($event->runInBackground);

        config(['dtms.url' => null, 'dtms.token' => null]);
        $this->assertFalse($event->filtersPass($this->app));

        $this->configure();
        $this->assertTrue($event->filtersPass($this->app));
    }
}

<?php

namespace App\Integrations\Dtms;

use App\Integrations\Dtms\Exceptions\CursorExpiredException;
use App\Integrations\Dtms\Exceptions\DtmsAuthException;
use App\Integrations\Dtms\Exceptions\DtmsUnavailableException;
use App\Models\DtmsSyncFailure;
use App\Models\DtmsSyncState;
use Illuminate\Support\Facades\Cache;

class SyncRunner
{
    private const LOCK = 'dtms-sync';
    private const LOCK_SECONDS = 300;

    public function __construct(private FeedClient $client, private ItemApplier $applier)
    {
    }

    public function run(bool $dryRun = false, ?int $limit = null): SyncReport
    {
        $report = new SyncReport();
        $lock = Cache::lock(self::LOCK, self::LOCK_SECONDS);

        if (! $lock->get()) {
            $report->skipped = true;

            return $report;
        }

        try {
            $state = DtmsSyncState::firstOrCreate([], ['cursor' => '0']);
            $cursor = (string) $state->cursor;
            $pageSize = $limit ?? (int) config('dtms.page_size');

            do {
                try {
                    $page = $this->client->changes($cursor, $pageSize);
                } catch (DtmsAuthException $e) {
                    $this->fail($report, SyncReport::ERROR_AUTH, $e);
                    break;
                } catch (CursorExpiredException $e) {
                    $this->fail($report, SyncReport::ERROR_CURSOR_EXPIRED, $e);
                    break;
                } catch (DtmsUnavailableException $e) {
                    $this->fail($report, SyncReport::ERROR_UNAVAILABLE, $e);
                    break;
                }

                $report->pages++;
                foreach ($page['items'] ?? [] as $item) {
                    $report->add($this->applier->apply($item, $dryRun));
                }

                $next = (string) ($page['next_cursor'] ?? $cursor);
                $hasMore = (bool) ($page['has_more'] ?? false);

                if (! $dryRun) {
                    $state->forceFill(['cursor' => $next, 'last_success_at' => now()])->save();
                }

                if ($hasMore && $next === $cursor) {
                    $report->stalled = true;
                    break;
                }

                $cursor = $next;
            } while ($hasMore);

            $report->cursor = $cursor;
        } finally {
            $lock->release();
        }

        return $report;
    }

    /**
     * Re-apply unresolved dtms_sync_failures rows. Never touches the cursor.
     */
    public function retryFailures(bool $dryRun = false): SyncReport
    {
        $report = new SyncReport();
        $lock = Cache::lock(self::LOCK, self::LOCK_SECONDS);

        if (! $lock->get()) {
            $report->skipped = true;

            return $report;
        }

        try {
            DtmsSyncFailure::whereNull('resolved_at')->orderBy('id')->each(function (DtmsSyncFailure $f) use ($report, $dryRun) {
                $item = $f->payload;
                if (! is_array($item)) {
                    $report->add(ApplyResult::failed(['type' => $f->event_type, 'id' => $f->entity_id], 'payload is empty'));

                    return;
                }
                $report->add($this->applier->apply($item, $dryRun));
            });
        } finally {
            $lock->release();
        }

        return $report;
    }

    private function fail(SyncReport $report, string $kind, \Throwable $e): void
    {
        $report->error = $kind;
        $report->errorMessage = $e->getMessage();
    }
}

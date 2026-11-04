<?php

namespace App\Console\Commands;

use App\Integrations\Dtms\ApplyResult;
use App\Integrations\Dtms\SyncReport;
use App\Integrations\Dtms\SyncRunner;
use Illuminate\Console\Command;

class DtmsSync extends Command
{
    protected $signature = 'dtms:sync {--dry-run} {--retry-failures}';

    protected $description = 'ดึงการเปลี่ยนแปลงนักเรียน/คอร์ส/สาขาจาก DTMS แล้วเขียนลงฐานข้อมูล (--dry-run ไม่เขียน, --retry-failures ประมวลผลรายการที่ล้มเหลวซ้ำ)';

    public function handle(SyncRunner $runner): int
    {
        if (blank(config('dtms.url')) || blank(config('dtms.token'))) {
            $this->error('ยังไม่ได้ตั้งค่า DTMS_SYNC_URL หรือ DTMS_SYNC_TOKEN ใน .env');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        if ($dry) {
            $this->warn('โหมด dry-run: ไม่เขียนข้อมูลและไม่บันทึก cursor');
        }

        $report = $this->option('retry-failures') ? $runner->retryFailures($dry) : $runner->run($dry);

        if ($report->skipped) {
            $this->warn('มี dtms:sync อีกตัวกำลังทำงานอยู่ ข้ามรอบนี้');

            return self::SUCCESS;
        }

        $this->table(
            ['created', 'updated', 'restored', 'unchanged', 'failed'],
            [[
                $report->counts[ApplyResult::CREATED],
                $report->counts[ApplyResult::UPDATED],
                $report->counts[ApplyResult::RESTORED],
                $report->counts[ApplyResult::UNCHANGED],
                $report->counts[ApplyResult::FAILED],
            ]]
        );

        foreach ($report->failures as $f) {
            $this->error("ล้มเหลว {$f->type} {$f->entityId}: {$f->message}");
        }

        if ($report->stalled) {
            $this->error('cursor ไม่เลื่อนแต่ DTMS บอกว่ายังมีข้อมูลอีก หยุดเพื่อกัน loop ไม่รู้จบ');
        }

        if ($report->error) {
            $this->reportError($report);
        }

        return $report->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }

    private function reportError(SyncReport $report): void
    {
        switch ($report->error) {
            case SyncReport::ERROR_AUTH:
                $this->error('DTMS ปฏิเสธ token (401/400) ตรวจ DTMS_SYNC_TOKEN แล้วแจ้งแอดมิน: '.$report->errorMessage);
                break;
            case SyncReport::ERROR_CURSOR_EXPIRED:
                $this->error('cursor หมดอายุ (410) ติดต่อแอดมิน DTMS ให้ re-emit ข้อมูล แล้วตั้ง dtms_sync_state.cursor เป็น 0');
                break;
            default:
                $this->error('DTMS ใช้งานไม่ได้ชั่วคราว จะลองใหม่รอบหน้า cursor ไม่เลื่อน: '.$report->errorMessage);
        }
    }
}

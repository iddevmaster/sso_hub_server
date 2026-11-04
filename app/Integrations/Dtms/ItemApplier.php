<?php

namespace App\Integrations\Dtms;

interface ItemApplier
{
    /**
     * เขียน item หนึ่งรายการจาก feed ลงฐานข้อมูล (idempotent)
     * ห้ามโยน exception เพราะข้อมูลมีปัญหา: บันทึกลง dtms_sync_failures แล้วคืน ApplyResult::failed()
     * โหมด $dryRun = true ห้ามเขียนอะไรเลย (รวมถึง dtms_sync_failures) คืนผลที่จะเกิดขึ้นเท่านั้น
     */
    public function apply(array $item, bool $dryRun = false): ApplyResult;
}

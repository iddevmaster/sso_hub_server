<?php

namespace App\Integrations\Dtms;

use RuntimeException;

/** ข้อมูลจาก feed ไม่ผ่านกฎ (เช่น สาขายังไม่จับคู่) ใช้ภายใน SnapshotApplier เพื่อ rollback แล้วบันทึก failure */
class SnapshotRuleViolation extends RuntimeException
{
}

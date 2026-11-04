<?php

namespace App\Integrations\Dtms;

class IdNumber
{
    /**
     * ต่างชาติ = เลขบัตรไม่ใช่ตัวเลข 13 หลักพอดี (หนังสือเดินทาง ฯลฯ)
     * ตรงกับกฎเดิมของ fetch:* ที่ใช้ความยาว != 13
     */
    public static function isForeign(string $idNumber): bool
    {
        return ! preg_match('/^\d{13}$/', trim($idNumber));
    }
}

<?php

namespace App\Integrations\Dtms;

interface FeedClient
{
    /**
     * เรียก GET /api/integrations/sso/changes
     *
     * @return array{items: array<int, array>, next_cursor: string, has_more: bool}
     *
     * @throws Exceptions\DtmsAuthException        401 หรือ 400 หยุดและแจ้งแอดมิน
     * @throws Exceptions\CursorExpiredException   410 ต้อง re-emit แล้วเริ่มจาก "0"
     * @throws Exceptions\DtmsUnavailableException 429, 5xx หรือเครือข่ายล่ม ลองใหม่รอบหน้า cursor ไม่เลื่อน
     */
    public function changes(string $after = '0', int $limit = 200): array;
}

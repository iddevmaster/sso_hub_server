<?php

return [
    // ค่าทั้งหมดอ่านจาก .env ห้ามเขียนค่าจริงลงไฟล์นี้
    'url' => env('DTMS_SYNC_URL'),
    'token' => env('DTMS_SYNC_TOKEN'),
    'school_id' => env('DTMS_SCHOOL_ID'),
    'agency_id' => env('DTMS_AGENCY_ID', 'IDD0002'),
    'page_size' => 200,
];

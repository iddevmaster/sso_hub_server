<?php

namespace Database\Seeders;

use App\Models\CourseType;
use App\Models\DtmsCourseTypeMap;
use Illuminate\Database\Seeder;

class DtmsCourseTypeMapSeeder extends Seeder
{
    /**
     * หมายเหตุ: รหัส vehicle type CAR / MC เป็นค่าสมมติ จนกว่า DTMS จะยืนยันรหัสจริง
     * ชื่อ course type ตรงกับที่ UpdateCourseType สร้างไว้ ข้ามรายการที่ไม่มีในฐานข้อมูล
     */
    public function run(): void
    {
        $rows = [
            ['CAR', false, 'รถยนต์'],
            ['MC', false, 'รถจักรยานยนต์'],
            ['CAR', true, 'รถยนต์ต่างชาติ'],
            ['MC', true, 'รถจักรยานยนต์ต่างชาติ'],
        ];

        foreach ($rows as [$vehicle, $foreign, $name]) {
            $type = CourseType::where('name', $name)->first();
            if (! $type) {
                continue;
            }
            DtmsCourseTypeMap::updateOrCreate(
                ['vehicle_type_code' => $vehicle, 'is_foreign' => $foreign],
                ['course_type' => $type->code]
            );
        }
    }
}

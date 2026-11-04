# งาน SSO: ตัวรับ feed จาก DTMS (Phase 1)

ทำใน repo นี้ ตามแผนใน [13-phase1-legacy-dtms-bridge.md](../13-phase1-legacy-dtms-bridge.md)
งานคู่กันฝั่ง DTMS อยู่ใน [dtms-phase1-sso-feed.md](dtms-phase1-sso-feed.md)

ทำคู่ขนานกับ DTMS ได้ เพราะเทสต์ใช้ fixture ตามสัญญา ไม่ต้องรอ DTMS เสร็จ ยกเว้นขั้น 9–10

## ลำดับงาน

| # | งาน | ไฟล์หลัก | รอ DTMS | สถานะ |
|---|---|---|---|---|
| 1 | เตรียมเครื่อง: `composer install`, ฐานข้อมูลทดสอบแยก, ทำให้ `php artisan test` รันได้ | `phpunit.xml`, `.env.testing` | ไม่ | เสร็จ |
| 2 | Migration: `dtms_branch_map`, `dtms_course_type_map`, `dtms_sync_state`, `dtms_sync_failures` และคอลัมน์ `courses.dtms_id`, `users.dtms_student_id`, `user_has_courses.dtms_enrollment_id` | `database/migrations/` | ไม่ | เสร็จ |
| 3 | `DtmsClient`: เรียก `changes` ด้วย Bearer token, แปลง 401/400/410/429/5xx เป็น exception แยกชนิด | `app/Integrations/Dtms/DtmsClient.php`, `config/services.php` | ไม่ | เสร็จ |
| 4 | `SnapshotApplier`: กฎเขียนข้อมูลของ school/branch/course/student ตาม doc 13 รวมการกู้บัญชีที่ถูก soft delete | `app/Integrations/Dtms/SnapshotApplier.php` | ไม่ | เสร็จ |
| 5 | `SyncRunner` + คำสั่ง `dtms:sync` (`--dry-run`, `--retry-failures`), lock กันรันซ้อน, บันทึก cursor หลังเขียนครบทั้งหน้า | `app/Integrations/Dtms/SyncRunner.php`, `app/Console/Commands/` | ไม่ | เสร็จ |
| 6 | คำสั่ง `dtms:branches` และ `dtms:map-branch {dtms_code} {brn_id}` | `app/Console/Commands/` | ไม่ | เสร็จ |
| 7 | เทสต์ applier ด้วย fixture ร่างจากสัญญา (แทนด้วยของจริงเมื่อ DTMS ส่งมา) | `tests/Feature/Dtms/`, `tests/Fixtures/dtms/` | ไม่ | เสร็จ (fixture ร่างเอง) |
| 8 | แก้โค้ดเดิม: `/api/user` คืน `courses: []` แทน 500, ลบ `fetch:apidata`, ตั้ง scheduler `dtms:sync` ทุกนาที | `routes/api.php`, `app/Console/Kernel.php` | ไม่ | เสร็จ |
| 9 | ใส่ fixture จริงจาก DTMS แล้วรันเทสต์ซ้ำ | `tests/Fixtures/dtms/` | ต้องได้ fixture | รอ DTMS |
| 10 | ทดสอบกับ DTMS staging: sync ครั้งแรก, จับคู่สาขา, `--dry-run` | — | ต้องได้ staging | รอ DTMS |
| 11 | (แนะนำ) ปิดช่องโหว่ A1–A4 ใน doc 08 | `routes/web.php`, `HomeController` | ไม่ | เสร็จ A1–A4 |

## ข้อมูลที่ต้องได้จากผู้ใช้ก่อนถึงขั้น 4 เสร็จ

1. **รหัสประเภทคอร์สใน prod** แถวทั้งหมดของตาราง `course_types` (`code`, `name`) ใน SSO prod เพื่อใส่ `dtms_course_type_map` ถ้ายังไม่ได้ จะใส่เป็น seeder ที่แก้ค่าทีหลังได้
2. **รายการ vehicle type `code`** ที่ DTMS IDDrives ใช้จริง (ไม่มี course option แล้ว ต่างชาติแยกจากเลขบัตร) (DTMS จะส่งมาตามข้อ 4 ของงาน DTMS)

## ข้อมูลที่ต้องได้ก่อนขึ้น prod

- URL ของ DTMS และ token (ใส่ใน `.env` ของ SSO เท่านั้น)
- school uuid ของ IDDrives ใน DTMS
- ยืนยันว่าบนเซิร์ฟเวอร์ SSO มี cron `schedule:run` และเอา cron ของ `fetch:apidata` ออกแล้ว

## Env ใหม่ของ SSO

```dotenv
DTMS_SYNC_URL=
DTMS_SYNC_TOKEN=
DTMS_SCHOOL_ID=
DTMS_AGENCY_ID=IDD0002
```

## สถานะ (2026-11-04)

ขั้น 1–8 และ 11 เสร็จแล้ว `php artisan test` ผ่าน 92 เทสต์ (รวมเทสต์ต่อทั้งสาย `EndToEndSyncTest`) ยังไม่ได้ commit

สิ่งที่ยังค้าง:

- ขั้น 9–10 รอ fixture จริงและ staging จาก DTMS
- `dtms_course_type_map` ยังว่าง `DtmsCourseTypeMapSeeder` สมมติ vehicle code เป็น `CAR`/`MC` ต้องแก้ตามที่ DTMS ส่งมา และยังไม่ได้ลงทะเบียนใน `DatabaseSeeder`
- ตั้ง `.env` บน prod (`DTMS_SYNC_URL`, `DTMS_SYNC_TOKEN`, `DTMS_SCHOOL_ID`) และ cron `schedule:run` Scheduler จะไม่ทำงานจนกว่า url และ token จะมีค่า
- ลบ cron ของ `fetch:apidata` บนเซิร์ฟเวอร์ และ **หมุน Basic auth ของ DSMS** (ค่าเดิมยังอยู่ในประวัติ git)
- เครื่อง dev ต้อง `composer install --ignore-platform-req=php` เพราะ lock มี `lcobucci/clock` 3.0 ที่รองรับ PHP แค่ 8.1/8.2 ส่วนเครื่องนี้ใช้ 8.3 (ตรวจว่า prod ใช้ PHP รุ่นไหน)
- `HomeController::index` ยังจับคู่สาขาด้วยชื่อภาษาไทย (hard-code)
- A2: ปุ่มจัดการประเภทคอร์สในหน้า `/data` ยังโชว์ให้ staff แต่ server ตอบ 403
- middleware `admin`/`adminAndStaff` redirect ไป `/` แทนการตอบ 403

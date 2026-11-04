# 14 · คู่มือ deploy Phase 1 (SSO เดิม ต่อ DTMS ของ IDDrives)

ใช้กับ **เซิร์ฟเวอร์ที่รันระบบอยู่แล้ว** ไม่ใช่การติดตั้งใหม่ (การติดตั้งใหม่ดู [07](07-setup-operations.md)) แผนและเหตุผลอยู่ใน [13](13-phase1-legacy-dtms-bridge.md)

## ก่อนเริ่ม ต้องมีครบ

| รายการ | ได้จาก |
|---|---|
| DTMS (branch `iddriver`) deploy feed แล้ว: `GET /api/integrations/sso/changes` ตอบ 200 | ทีม DTMS ([brief](tasks/dtms-phase1-sso-feed.md)) |
| URL ของ DTMS, `SSO_SYNC_TOKEN` (ใช้เป็น `DTMS_SYNC_TOKEN` ฝั่งนี้), school uuid ของ IDDrives | ทีม DTMS (ส่ง token ทางที่ปลอดภัย ห้ามใส่ในแชตหรือ repo) |
| DTMS รัน backfill แล้ว (ให้ feed ส่งข้อมูลเดิมครบจาก cursor `0`) | ทีม DTMS |
| vehicle type `code` ที่ IDDrives ใช้จริง | ทีม DTMS |
| แถวทั้งหมดของตาราง `course_types` (`code`, `name`) บน prod | `SELECT code, name FROM course_types;` |
| รายชื่อสาขา DTMS เทียบกับ `brn_id` ของ SSO (`IDD0002`–`IDD0007`) | คนที่รู้ข้อมูลสาขา |
| PHP บน prod เป็นรุ่นอะไร | `php -v` ดูหมายเหตุ composer ด้านล่าง |

ทดสอบ feed ก่อนโดยไม่ต้องรอ SSO:

```bash
curl -s -H "Authorization: Bearer $TOKEN" "$DTMS_URL/api/integrations/sso/changes?after=0&limit=5"
```

ต้องได้ JSON ที่มี `items`, `next_cursor`, `has_more` ถ้าได้ 401 token ผิด, 404 feed ยังปิดอยู่

## 1. สำรองก่อนทุกอย่าง

```bash
mysqldump --single-transaction -u USER -p DB_NAME > sso_before_dtms_$(date +%F).sql
```

เก็บไฟล์ไว้นอกเซิร์ฟเวอร์ด้วย migration รอบนี้ **เพิ่ม** ตารางและคอลัมน์อย่างเดียว ไม่แก้หรือลบข้อมูลเดิม แต่ `dtms:sync` ในขั้น 7 จะ **เขียนทับชื่อและสาขา** ของนักเรียนที่มีอยู่แล้ว (เลขบัตรตรงกัน) จึงต้องมี backup

## 2. อัปเดตโค้ด

```bash
git pull
composer install --no-dev --optimize-autoloader
```

- `composer.lock` ล็อก `lcobucci/clock` 3.0 ซึ่งรองรับ PHP 8.1/8.2 เท่านั้น ถ้า prod เป็น 8.3 ขึ้นไป `composer install` จะ error ต้องแก้ lock ด้วย `composer update lcobucci/clock` ในเครื่อง dev แล้ว commit (ยังไม่ได้ทำ) ห้ามใช้ `--ignore-platform-req` บน prod
- ถ้าใช้ `npm run build` อยู่ ไม่มีไฟล์ frontend เปลี่ยนในรอบนี้ ข้ามได้ ยกเว้น `resources/views/pages/customer-table.blade.php` (blade ไม่ต้อง build)

## 3. ตั้งค่า `.env` (ห้ามทับ `.env` เดิม)

เพิ่ม 3 ค่าลงใน `.env` เดิมของ prod ส่วน `DTMS_AGENCY_ID` มีค่าเริ่มต้น `IDD0002` อยู่แล้ว

```dotenv
DTMS_SYNC_URL=https://...      # ไม่มี / ท้าย
DTMS_SYNC_TOKEN=...
DTMS_SCHOOL_ID=...             # school uuid ของ IDDrives
DTMS_AGENCY_ID=IDD0002
```

> ถ้า `cp .env.example .env` บนเครื่องใหม่: ใน `.env.example` มี `APP_KEY` ที่ถูก commit ไว้ ให้ตั้งค่าใหม่ด้วย `php artisan key:generate` ส่วนเครื่องเดิมที่ใช้งานอยู่ **ห้ามเปลี่ยน `APP_KEY`** เพราะ session และข้อมูลเข้ารหัสจะใช้ไม่ได้

ขั้น 6 ต้องใช้ URL และ token รันคำสั่งมือ จึงตั้งครบตั้งแต่ขั้นนี้ แต่ scheduler จะรัน `dtms:sync` ทุกนาทีทันทีที่ทั้งสองค่ามีค่า **และ** มี cron `schedule:run` อยู่ ตรวจก่อนด้วย `crontab -l`:

- ยังไม่มี cron `schedule:run`: ตั้งครบได้เลย แล้วค่อยเพิ่ม cron ในขั้น 8
- มี cron `schedule:run` อยู่แล้ว: ใส่ `DTMS_SYNC_URL`/`DTMS_SYNC_TOKEN` ทีหลัง ในขั้น 6 ทันทีก่อนรันมือ ไม่เช่นนั้นระบบจะ sync เองระหว่างที่ยังไม่ได้จับคู่สาขา (ไม่เสียหาย รายการจะถูกพักไว้ แต่จะมีรายการ failed เต็ม)

## 4. Migration

```bash
php artisan migrate --force
```

จะสร้างตาราง `dtms_branch_map`, `dtms_course_type_map`, `dtms_sync_state`, `dtms_sync_failures` และคอลัมน์ `courses.dtms_id`, `courses.dtms_vehicle_type_code`, `users.dtms_student_id`, `user_has_courses.dtms_enrollment_id` ตรวจ:

```bash
php artisan migrate:status | tail -3
```

## 5. ใส่ตารางแมปประเภทคอร์ส

`dtms_course_type_map` เป็นตัวบอกว่า (ประเภทรถ + ต่างชาติหรือไม่) ตรงกับ `course_types.code` ตัวไหน **ถ้าว่างหรือไม่ครบ นักเรียนและคอร์สจะถูกพักไว้ใน `dtms_sync_failures`** (ไม่พัง) และต้องใส่ให้ครบทั้งแถวปกติและต่างชาติของทุกประเภทรถ

seeder `DtmsCourseTypeMapSeeder` สมมติรหัสรถเป็น `CAR` และ `MC` และหาประเภทจากชื่อ `รถยนต์`, `รถจักรยานยนต์`, `รถยนต์ต่างชาติ`, `รถจักรยานยนต์ต่างชาติ` ถ้าตรงกับของจริงให้รัน:

```bash
php artisan db:seed --class=DtmsCourseTypeMapSeeder
```

ถ้าไม่ตรง (vehicle code อื่น เช่น ประเภทบรรทุก หรือชื่อประเภทต่าง) ให้ INSERT เอง:

```sql
INSERT INTO dtms_course_type_map (vehicle_type_code, is_foreign, course_type, created_at, updated_at)
VALUES ('CAR', 0, '<course_types.code ปกติ>', NOW(), NOW()),
       ('CAR', 1, '<course_types.code ต่างชาติ>', NOW(), NOW());
-- ทำซ้ำสำหรับทุก vehicle type code
```

ตรวจ: `SELECT * FROM dtms_course_type_map;` ต้องครบทุกประเภทรถที่ DTMS ส่งมา

## 6. รอบแรก: เก็บรายชื่อสาขาและจับคู่

ยังไม่เปิด scheduler ในขั้นนี้ รันมือ:

```bash
php artisan dtms:sync
```

- รอบแรก **สาขายังไม่จับคู่** จึงคาดว่านักเรียนทุกคนจะขึ้น failed (`branch not mapped`) นี่เป็นเรื่องปกติ รายการถูกพักไว้ใน `dtms_sync_failures` พร้อมข้อมูลเต็ม และ cursor เดินต่อ ไม่ต้องดึงใหม่
- สาขาของ DTMS ถูกบันทึกลง `dtms_branch_map` แล้ว (ตาราง `branches` ของ SSO ไม่ถูกแตะ)
- `--dry-run` ใช้ขั้นนี้ไม่ได้ผลเพราะไม่เขียนอะไร รวมถึงรายชื่อสาขา

จากนั้นดูและจับคู่สาขาทีละสาขา:

```bash
php artisan dtms:branches
php artisan dtms:map-branch KK IDD0003     # รหัสสาขา DTMS  brn_id ของ SSO
```

- ถ้ารหัสสาขา DTMS ซ้ำกันหลายแถว ให้ใช้ uuid แทน code
- ห้ามสร้างสาขาใหม่ผ่านคำสั่งนี้ สาขาของ SSO ต้องมีอยู่แล้ว (ถ้า DTMS มีสาขาใหม่ ให้สร้างในหน้า "ข้อมูลระบบ" ของ SSO พร้อม `brn_id` ก่อน และต้องเพิ่ม `send_branch` ใน `HomeController::index` ด้วย เพราะยังจับคู่ด้วยชื่อภาษาไทย)
- รัน `php artisan dtms:branches` อีกครั้ง ต้องไม่มีแถว `UNMAPPED` ที่ active

## 7. ประมวลผลรายการที่พักไว้

```bash
php artisan dtms:sync --retry-failures --dry-run   # ดูผลก่อน ไม่เขียน
php artisan dtms:sync --retry-failures             # ทำจริง
```

ดูตารางสรุป created / updated / restored / unchanged / failed แล้วตรวจ:

```sql
SELECT event_type, error, COUNT(*) FROM dtms_sync_failures WHERE resolved_at IS NULL GROUP BY 1, 2;
```

เป้าหมายคือไม่เหลือรายการค้าง ข้อความที่พบบ่อย:

| ข้อความ | ทำอย่างไร |
|---|---|
| `branch not mapped` | จับคู่สาขาเพิ่ม (ขั้น 6) แล้วรันซ้ำ |
| `course type not mapped for vehicle type ...` | เพิ่มแถวใน `dtms_course_type_map` (ขั้น 5) แล้วรันซ้ำ |
| อื่น ๆ | เก็บข้อความและ payload ส่งให้ผู้พัฒนา อย่าแก้ตารางเอง |

ผลที่ต้องรู้ก่อนกดทำจริง:

- นักเรียนที่มีบัญชีอยู่แล้ว (เลขบัตรตรงกัน) จะถูก **อัปเดตชื่อ และสาขา/หน่วยงานตามการลงทะเบียนล่าสุด** ไม่แตะรหัสผ่าน
- นักเรียนที่ถูกลบ (soft delete) จะถูกกู้กลับมา
- นักเรียนใหม่ได้รหัสผ่านเริ่มต้น = เลขบัตร (เหมือนเดิม) และ role `customer`
- การลงทะเบียนที่ยกเลิกจะลบลิงก์คอร์สนั้นของนักเรียน ถ้าไม่เหลือคอร์สเลย KST Hub จะไม่ให้เข้า (phase 1 ยังไม่ตัดสิทธิ์เอง)
- ข้อมูลใน DTMS เป็นตัวจริง ถ้าแก้ชื่อใน SSO จะถูก sync ทับ

## 8. เปิดการซิงก์อัตโนมัติ

1. ตั้ง `DTMS_SYNC_URL` และ `DTMS_SYNC_TOKEN` ใน `.env` ให้ครบ (ถ้ายังไม่ได้ตั้ง)
2. ตั้ง cron ให้ scheduler ทำงาน (ถ้ายังไม่มี):

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

3. ล้าง cache config:

```bash
php artisan config:clear && php artisan config:cache
php artisan schedule:list      # ต้องเห็น dtms:sync ทุกนาที
```

4. **ลบ cron เก่าของ `fetch:apidata`** บนเซิร์ฟเวอร์ (คำสั่งนี้ถูกลบจากโค้ดแล้ว ถ้าปล่อยไว้จะ error ทุกรอบ) `fetch:posdata` ไม่เปลี่ยน

## 9. ตรวจหลัง deploy

| ตรวจ | วิธี |
|---|---|
| sync ทำงาน | `SELECT cursor, last_success_at FROM dtms_sync_state;` ต้องเปลี่ยนและเวลาไม่เก่าเกิน 2–3 นาที |
| ไม่มีรายการค้าง | `SELECT COUNT(*) FROM dtms_sync_failures WHERE resolved_at IS NULL;` ต้องเป็น 0 |
| นักเรียนใหม่เข้าระบบ | ลงทะเบียนนักเรียนทดสอบที่ DTMS รอ 1–2 นาที แล้วค้นเลขบัตรในหน้านักเรียนของ SSO |
| `/api/user` | login ผ่าน KST Hub (และ Check-Before, SmartHub) ด้วยนักเรียนจาก DTMS อย่างน้อยหนึ่งคนต่อประเภทรถ และต่างชาติหนึ่งคน (เลขไม่ใช่ 13 หลัก) ตรวจว่า `courses[0].course_type` ถูกต้อง |
| นักเรียนไม่มีคอร์ส | `/api/user` ต้องตอบ `courses: []` ไม่ใช่ 500 |
| ช่องโหว่ที่ปิด | `/register` ต้อง 404; staff เปิด `/users/*` ไม่ได้; ปุ่มลบคอร์สนักเรียนในหน้า "ข้อมูลนักเรียน" ยังใช้ได้ (เปลี่ยนเป็น POST แล้ว ยังไม่ได้ลองในเบราว์เซอร์) |
| log | `storage/logs/laravel.log` ไม่มี error ซ้ำทุกนาที |

## 10. งานความปลอดภัยที่ต้องทำพร้อมกัน

- **หมุนรหัส Basic auth ของ DSMS** (`dsmsys.net`) ค่าเดิมยังอยู่ในประวัติ git แม้ไฟล์ถูกลบแล้ว รวมถึง OAuth client secret ใน `mynote.txt` และ Basic auth ของ POS ใน `FetchPOSDataToDatabase` ([08](08-known-issues.md) ข้อ A5)
- เปลี่ยนรหัสผ่านผู้ใช้ `admin` ถ้ายังเป็นค่าจาก seeder
- staff ที่เคยจัดการผู้ใช้หรือข้อมูลระบบ (หน่วยงาน สาขา role ประเภทคอร์ส) จะทำไม่ได้แล้ว ต้องเป็น admin แจ้งผู้ใช้ก่อน

## ถอยกลับ (rollback)

| กรณี | ทำอย่างไร |
|---|---|
| sync มีปัญหา ต้องหยุดชั่วคราว | ลบ `DTMS_SYNC_TOKEN` ใน `.env` แล้ว `php artisan config:clear` scheduler จะหยุดรัน ข้อมูลเดิมไม่หาย |
| ข้อมูลนักเรียนถูกเขียนผิด | restore จาก backup ขั้น 1 (ระวังข้อมูลที่เข้ามาหลัง backup) |
| ยกเลิกโค้ดทั้งหมด | หยุด sync ก่อน แล้ว `php artisan migrate:rollback --step=1` จะลบตารางและคอลัมน์ DTMS (ข้อมูลที่ sync แล้วในตาราง `users` ยังอยู่) จากนั้น `git checkout` ไป commit เดิม `fetch:apidata` ไม่มีข้อมูลเข้าอยู่แล้วเพราะ EM ปิด |
| cursor เลื่อนผิด / DTMS ตอบ 410 | ติดต่อทีม DTMS ให้ re-emit แล้วตั้ง `UPDATE dtms_sync_state SET cursor='0';` |

## รูปแบบ checklist สั้น

- [ ] backup ฐานข้อมูล
- [ ] feed ของ DTMS ตอบ 200 ด้วย curl
- [ ] `git pull` + `composer install --no-dev`
- [ ] เพิ่มค่า DTMS ลง `.env`
- [ ] `php artisan migrate --force`
- [ ] `dtms_course_type_map` ครบ
- [ ] `dtms:sync` รอบแรก → `dtms:map-branch` ทุกสาขา → `dtms:sync --retry-failures`
- [ ] ไม่มี failure ค้าง
- [ ] cron `schedule:run` + `schedule:list` เห็น `dtms:sync`
- [ ] ลบ cron `fetch:apidata`
- [ ] ทดสอบ login ผ่าน KST Hub / Check-Before / SmartHub
- [ ] หมุนความลับที่หลุดใน git

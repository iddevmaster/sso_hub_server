# 13 · Phase 1: ต่อ SSO เดิมเข้ากับ DTMS ของ IDDrives

สถานะ: **แผนที่ตกลงแล้ว ยังไม่ได้เริ่มพัฒนา**

## ที่มา

DTMS แยกเป็น 2 ตัว คือของ IDDrives (repo `dtms` branch `iddriver`) และตัว subscription สำหรับขายให้โรงเรียนอื่น (repo `dtms-subscription`)
IDDrives ต้องใช้งานเร็ว จึงแบ่งเป็นสองช่วง

1. **Phase 1 (เอกสารนี้):** ให้ SSO เดิมดึงข้อมูลจาก DTMS ของ IDDrives แทน EM ที่ปิดไปแล้ว
2. **ภายหลัง:** สร้าง SSO ใหม่แบบหลายโรงเรียนตาม [11](11-new-sso-design.md) แล้วย้ายไปใช้

SSO เดิมออกแบบไว้สำหรับโรงเรียนเดียว และ DTMS ของ IDDrives ก็มีโรงเรียนเดียว phase 1 จึงไม่ต้องแก้เรื่องหลายโรงเรียน

## หลักการ: งานฝั่ง DTMS ทำครั้งเดียว ใช้ได้ทั้งสองช่วง

- DTMS ทำ feed ตามสัญญาใน [10](10-dtms-sync-contract.md) **ไม่ต้องทำรูปแบบพิเศษสำหรับ SSO เดิม**
- SSO เดิมมีตัวแปลง (`dtms:sync`) ที่อ่าน feed แล้วเขียนลงตารางเดิม ระบบปลายทาง (KST Hub, Check-Before, SmartHub) **ไม่ต้องแก้อะไร** เพราะ `/api/user` ยังส่งข้อมูลรูปแบบเดิม
- ตอนย้ายระบบ SSO ใหม่จะอ่าน feed เดียวกัน แล้วดึงข้อมูลทั้งหมดใหม่ตั้งแต่ต้นด้วย backfill
- ส่วนที่จะถูกทิ้งตอนย้ายระบบมีแค่ตัวแปลงใน SSO เดิม จึงควรเขียนให้เล็กที่สุด

## สิ่งที่ตกลงแล้ว

| เรื่อง | ข้อตกลง |
|---|---|
| DTMS ต้นทาง | repo `dtms` branch `iddriver` |
| การจับคู่สาขา | **map ภายใน SSO เอง ไม่แก้ข้อมูลใน DTMS** ดูหัวข้อ "การจับคู่สาขา" ด้านล่าง |
| นักเรียนที่ถูก soft delete ใน SSO | ถ้ามีข้อมูลใหม่จาก DTMS ให้**กู้บัญชีเดิม** (restore) ไม่สร้างใหม่ |
| ตัดสิทธิ์เมื่อเรียนจบ/ยกเลิก | **ยังไม่ทำใน phase 1** รอ SSO ใหม่ |
| เจ้าหน้าที่/ครูฝึก | ใช้บัญชีใน SSO เดิมไปก่อน ยังไม่ทำ `staff/verify` |
| แหล่งข้อมูลจริง | DTMS เป็นตัวจริง ถ้าแก้ข้อมูลนักเรียนในหน้าแอดมิน SSO จะถูก sync เขียนทับ ต้องไปแก้ใน DTMS |

### การจับคู่สาขา

ไม่แก้ `code` หรือ `branch_num` ใน DTMS เพราะ `code` เป็นส่วนหน้าของเลขใบเสร็จ (`lib/receipt-number.ts`) และ `branch_num` ถูกใช้ในลิงก์ QR สำหรับเก็บ consent
SSO จึงเก็บตาราง map ของตัวเอง (`dtms_branch_map` ด้านล่าง)

1. ทุกครั้งที่ feed ส่งสาขามา SSO จะบันทึกหรืออัปเดตแถวใน `dtms_branch_map` (id, code, ชื่อ จาก DTMS) โดย `brn_id` ยังว่าง
2. แอดมินดูรายการสาขาที่ยังไม่จับคู่ด้วย `php artisan dtms:branches` แล้วจับคู่ด้วย `php artisan dtms:map-branch {dtms_code} {brn_id}` เช่น `dtms:map-branch KK IDD0003` ทำครั้งเดียวต่อสาขา
3. นักเรียนหรือการลงทะเบียนที่อยู่ในสาขาที่ยังไม่จับคู่ จะลง `dtms_sync_failures` และประมวลผลซ้ำได้ด้วย `dtms:sync --retry-failures` หลังจับคู่แล้ว
4. SSO **ไม่สร้างสาขาใหม่เอง** ถ้า DTMS เพิ่มสาขาใหม่ ต้องสร้างสาขาในหน้า "ข้อมูลระบบ" ของ SSO พร้อม `brn_id` แล้วจับคู่ และต้องเพิ่ม `send_branch` ใน `HomeController::index` ด้วย (จุด hard-code เดิม)

## งานฝั่ง DTMS (branch `iddriver`)

ทำตาม [09](09-dtms-sync-design.md) และ [10](10-dtms-sync-contract.md) แต่ตัดบางส่วนออกเพื่อให้เสร็จเร็ว

| ทำใน phase 1 | เลื่อนไปทีหลัง |
|---|---|
| ตาราง `integration_events` และ trigger บน `schools`, `branches`, `courses`, `students`, `enrollments` | ping แจ้งเตือน (doorbell) ใช้การดึงทุก 1 นาทีแทน ข้อมูลช้าไม่เกินประมาณ 1 นาที |
| `GET /api/integrations/sso/changes` และตัวสร้าง snapshot | `POST /api/integrations/sso/staff/verify` |
| token สำหรับ SSO และ `SSO_SYNC_SCHOOL_SCOPE` เป็นโรงเรียน IDDrives | |
| คำสั่ง backfill (สร้าง event ของข้อมูลเดิมทั้งหมด) | |
| cron ลบ event เก่า | |

- feed ต้องปิดไว้ด้วย env (ไม่มี token = ตอบ 404/401) เผื่อโค้ดนี้ถูกนำไปรวมใน `dtms-subscription`
- `master` กับ `iddriver` แยกทางกันมากแล้ว (ต่างกันหลายร้อย commit ตอนเขียนเอกสารนี้) ต้องตัดสินใจทีหลังว่าจะนำ feed ไปใส่ใน `master`/subscription ตอนทำ SSO ใหม่อย่างไร

## งานฝั่ง SSO เดิม

### 1. Migration (เพิ่มคอลัมน์ ไม่ลบของเดิม)

| ตาราง | คอลัมน์ใหม่ | ใช้ทำอะไร |
|---|---|---|
| ใหม่ `dtms_branch_map` | `dtms_id` (uuid, unique), `dtms_code`, `dtms_name`, `brn_id` (nullable), `updated_at` | จับคู่สาขา DTMS กับ `branches.brn_id` ของ SSO โดยไม่แก้ข้อมูลทั้งสองฝั่ง |
| `courses` | `dtms_id` (uuid, unique, nullable) | จับคู่คอร์สด้วย id ไม่ใช่ชื่อหรือรหัส |
| `users` | `dtms_student_id` (uuid, nullable, index) | ผูกนักเรียนกับ DTMS ใช้ตอนย้ายระบบ |
| `user_has_courses` | `dtms_enrollment_id` (uuid, unique, nullable) | จับคู่การลงทะเบียน |
| ใหม่ `dtms_sync_state` | `cursor`, `updated_at` | เก็บตำแหน่งที่อ่าน feed ถึง |
| ใหม่ `dtms_sync_failures` | `event_type`, `entity_id`, `error`, `payload`, `created_at`, `resolved_at` | เก็บรายการที่ประมวลผลไม่ได้ ไม่ทำให้ทั้งรอบล้ม |
| ใหม่ `dtms_course_type_map` | `vehicle_type_code`, `is_foreign`, `course_type` | แปลงประเภทรถ + ตัวเลือก "ต่างชาติ" เป็น `course_types.code` เดิม |

### 2. คำสั่ง `dtms:sync`

- อ่าน feed จาก cursor ที่เก็บไว้ ประมวลผลทีละรายการ แล้วบันทึก cursor
- มี `--dry-run` สำหรับดูผลก่อนเขียนจริง
- ตั้งเวลารันทุกนาทีใน `Console/Kernel.php` ด้วย `withoutOverlapping()` (ต้องมี cron `schedule:run` บนเซิร์ฟเวอร์)
- รายการที่ล้มเหลวลง `dtms_sync_failures` และ exit code ไม่ใช่ 0 เมื่อมีรายการล้มเหลวใหม่ ต่างจากคำสั่ง fetch เดิมที่ล้มแบบเงียบ (B6)
- เขียนเป็นคลาสแยกใน `app/Integrations/Dtms/` ไม่ใส่ใน controller

### 3. กฎการเขียนข้อมูล

| ข้อมูลจาก DTMS | เขียนลง SSO เดิม |
|---|---|
| school | ตรวจว่าเป็นโรงเรียนที่ตั้งไว้ใน env แล้วใช้ agency `IDD0002` ตัวเดิม ไม่เขียนทับชื่อ |
| branch | บันทึก/อัปเดตใน `dtms_branch_map` เท่านั้น **ไม่แตะตาราง `branches`** (ห้ามเขียนทับชื่อ เพราะ `HomeController::index` ยังจับคู่ด้วยชื่อภาษาไทย) สาขาของนักเรียนได้จาก `dtms_branch_map.brn_id` ถ้ายังว่างให้ลง `dtms_sync_failures` |
| course | หาจาก `dtms_id` ถ้าไม่พบให้สร้างใหม่ (`code`, `name`, `agn = IDD0002`) แล้วตั้ง `course_type` จาก `dtms_course_type_map` ห้ามใช้ `update:coursetype` ที่เดาจากชื่อ |
| student | หาจาก `dtms_student_id` แล้วตามด้วย `username = id_number` **รวมบัญชีที่ถูก soft delete** ถ้าเจอบัญชีที่ถูกลบให้ restore ถ้าไม่เจอให้สร้างใหม่ ด้วยรหัสผ่านเริ่มต้น = เลขบัตรเหมือนเดิม และให้ทั้ง `role = customer` กับ Spatie role `customer` ถ้ามีอยู่แล้ว**ให้อัปเดต**ชื่อไทย/อังกฤษและสาขาตามการลงทะเบียนล่าสุด แต่ไม่แตะรหัสผ่าน |
| enrollment `active` / `pending_cancellation` / `completed` | สร้างหรือ restore แถว `user_has_courses` (จับคู่ด้วย `dtms_enrollment_id`) ให้ `created_at` = `enrolled_at` เพราะ `/api/user` เลือกคอร์สล่าสุดจาก `created_at` |
| enrollment `cancelled` | soft delete แถว `user_has_courses` นั้น |

- ทุกกฎต้องรันซ้ำได้ผลเหมือนเดิม (idempotent) เพราะ feed อาจส่งรายการเดิมซ้ำ
- `completed` ยังคงสิทธิ์ เพราะยังไม่ตัดสิทธิ์ใน phase 1
- `nationality`: กำหนดจาก course option "ต่างชาติ" ถ้ามี ไม่ต้องเดาจากรูปแบบรหัสนักเรียนแบบคำสั่ง fetch เดิม

### 4. แก้โค้ดเดิมที่จำเป็น

| เรื่อง | เหตุผล |
|---|---|
| `/api/user` คืน `courses: []` แทน error 500 เมื่อไม่มีคอร์ส (B7) | การยกเลิกการลงทะเบียนจะทำให้นักเรียนบางคนไม่มีคอร์สเหลือ |
| ลบ `fetch:apidata` ออก และลบ cron ของคำสั่งนี้บนเซิร์ฟเวอร์ | EM ปิดแล้ว และในไฟล์มี Basic auth ที่ commit ไว้ (A5) |
| `fetch:posdata` ไม่ต้องแตะ | ยังไม่ได้ตัดสินใจเรื่อง POS |

### 5. แนะนำให้ทำพร้อมกัน (ช่องโหว่ที่แก้ใน route ไม่กี่บรรทัด)

จาก [08](08-known-issues.md) เพราะระบบจะเริ่มมีข้อมูลไหลเข้าจริง

- A3 เพิ่ม/ลบคอร์สนักเรียนได้โดยไม่ต้อง login
- A4 ปิด `/register`
- A1/A2 staff สร้าง user หรือยกระดับตัวเองเป็น admin ได้

## ลำดับการทำและการทดสอบ

1. DTMS ทำ feed + backfill บน staging ของ `iddriver`
2. SSO ทำ migration + `dtms:sync` แล้วทดสอบกับ fixtures 1–7 ใน [10](10-dtms-sync-contract.md)
3. รัน sync กับ prod ครั้งแรกเพื่อเก็บรายการสาขาลง `dtms_branch_map` แล้วจับคู่ทุกสาขาด้วย `dtms:map-branch` ก่อนดูข้อมูลนักเรียน
4. สำรองฐานข้อมูล SSO prod แล้วรัน `dtms:sync --dry-run` กับข้อมูลจริง ตรวจจำนวนบัญชีที่จะสร้าง/กู้/อัปเดต และต้องไม่มีสาขาที่ยังไม่จับคู่
5. รันจริง แล้วทดสอบ login ผ่าน KST Hub, Check-Before และ SmartHub ด้วยนักเรียนจาก DTMS อย่างน้อยหนึ่งคนต่อประเภทรถ
6. เปิด scheduler ทุกนาที และดู `dtms_sync_failures` ช่วงแรกทุกวัน

## สิ่งที่ phase 1 ไม่ทำ

- ไม่รองรับหลายโรงเรียน
- ไม่ตัดสิทธิ์เมื่อเรียนจบหรือยกเลิก (นักเรียนที่ยกเลิกทุกคอร์สยัง login SSO ได้ แต่ KST Hub จะปฏิเสธเพราะ `courses` ว่าง)
- ไม่เปลี่ยนรหัสผ่านเริ่มต้นจากเลขบัตร (A8)
- ไม่แก้ปัญหาอื่นใน [08](08-known-issues.md) นอกจากที่ระบุไว้ข้างบน

## ตอนย้ายไป SSO ใหม่

- SSO ใหม่ดึงข้อมูลทั้งหมดจาก feed ผ่าน backfill ไม่ต้องย้ายข้อมูลนักเรียนจาก SSO เดิม
- `users.dtms_student_id` ใน SSO เดิมช่วยจับคู่บัญชีเก่ากับ `sub` ใหม่ ถ้าตัดสินใจย้าย password hash (คำถามเปิดใน [11](11-new-sso-design.md))
- ระบบปลายทางย้ายไปใช้ SSO ใหม่ทีละระบบ

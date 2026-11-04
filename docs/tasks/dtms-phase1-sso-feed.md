# งาน DTMS: Change feed สำหรับ SSO (Phase 1)

> เอกสารนี้ส่งให้ agent ที่ทำงานใน repo `dtms` อ่านจบในตัว ไม่ต้องเปิด repo SSO
> ที่มา: SSO (`sso_hub_server`, Laravel) ต้องดึงข้อมูลนักเรียนจาก DTMS แทนระบบ EM ที่ปิดไปแล้ว

## บริบท

- ทำใน repo `dtms` โดย**แตก branch ใหม่จาก `iddriver`** (DTMS ที่ IDDrives ใช้จริง) ห้ามทำบน `master` หรือ `dtms-subscription`
- SSO จะเรียก `GET /api/integrations/sso/changes` ทุก 1 นาที เพื่อรับ **สถานะปัจจุบัน** ของโรงเรียน สาขา คอร์ส และนักเรียน (พร้อมการลงทะเบียน) ที่เปลี่ยนไปหลัง cursor
- การซิงก์เป็นทางเดียว DTMS → SSO ฝั่ง DTMS **ไม่ต้องรู้จัก SSO** นอกจาก token
- ภายหลังจะมี SSO ใหม่แบบหลายโรงเรียนมาอ่าน feed เดียวกันนี้ จึงต้องทำตามสัญญาด้านล่างให้ตรง ไม่ทำรูปแบบพิเศษสำหรับ IDDrives

## ขอบเขต

| ทำ | ไม่ทำ (รอบหลัง) |
|---|---|
| ตาราง `integration_events` + trigger | ping/webhook แจ้ง SSO (`after(() => pingSso())`) |
| `GET /api/integrations/sso/changes` | `POST /api/integrations/sso/staff/verify` |
| snapshot builder + เทสต์ + fixture | UI ใด ๆ |
| สคริปต์ backfill / re-emit | |
| cron ล้าง event เก่า | |
| ADR เรื่องเริ่มใช้ DB trigger | |

**ห้ามแก้** `branches.code` หรือ `branches.branch_num` หรือข้อมูลสาขาใด ๆ การจับคู่สาขากับรหัสเดิมทำฝั่ง SSO

## 1. Migration (เลขถัดไปที่ว่างใน `db/migrations/` ของ `iddriver` ตอนนี้ล่าสุดคือ `0106`)

```sql
CREATE TABLE integration_events (
  id          bigserial PRIMARY KEY,
  school_id   uuid        NOT NULL,
  entity_type varchar(20) NOT NULL,  -- 'school' | 'branch' | 'course' | 'student'
  entity_id   uuid        NOT NULL,
  created_at  timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX idx_integration_events_school_id ON integration_events (school_id, id);

-- จุดที่ล้าง event ไปแล้ว ใช้ตอบ 410 (แถวเดียว)
CREATE TABLE integration_event_watermark (
  id             smallint PRIMARY KEY DEFAULT 1 CHECK (id = 1),
  deleted_up_to  bigint NOT NULL DEFAULT 0
);
INSERT INTO integration_event_watermark DEFAULT VALUES;
```

Trigger แบบ `AFTER INSERT OR UPDATE ... FOR EACH ROW` (ทั้งหมดใน migration เดียวกัน):

| ตาราง | event ที่เพิ่ม |
|---|---|
| `schools` | `('school', NEW.id, school_id = NEW.id)` |
| `branches` | `('branch', NEW.id)` |
| `courses` | `('course', NEW.id)` |
| `students` | `('student', NEW.id)` |
| `enrollments` | `('student', NEW.student_id)` **และ** ถ้า `TG_OP = 'UPDATE' AND OLD.student_id IS DISTINCT FROM NEW.student_id` ให้เพิ่ม `('student', OLD.student_id)` ด้วย (กรณีเปลี่ยนตัวผู้เรียน) |
| `title_prefixes` | ไม่ต้อง (เปลี่ยนชื่อคำนำหน้าไม่บ่อย ใช้ re-emit แทน) |

- ใส่ `WHEN (OLD.* IS DISTINCT FROM NEW.*)` ใน trigger ของ UPDATE ได้ถ้าสะดวก ไม่บังคับ event ซ้ำไม่เป็นปัญหา
- trigger ต้องไม่ทำให้ transaction เดิมล้ม ห้าม query หนักใน trigger แค่ INSERT แถวเดียว
- ยังไม่มี trigger ใน DTMS มาก่อน **ให้เขียน ADR** ใน `docs/adr/` (เลขถัดไป) อธิบายว่าทำไมใช้ trigger: จับทุกเส้นทางที่แก้ข้อมูล รวมถึง action ที่จะเพิ่มในอนาคต

## 2. Endpoint `GET /api/integrations/sso/changes`

ไฟล์: `app/api/integrations/sso/changes/route.ts` (path `/api/*` ไม่ผ่าน `proxy.ts` อยู่แล้ว)

### การยืนยันตัวตน

- `Authorization: Bearer <token>` เทียบกับ env `SSO_SYNC_TOKEN` และ `SSO_SYNC_TOKEN_PREVIOUS` (ถ้ามี ใช้ตอนหมุน token) ด้วย `crypto.timingSafeEqual`
- ถ้าไม่ได้ตั้ง `SSO_SYNC_TOKEN` → ตอบ `404` (ปิดฟีเจอร์)
- ขอบเขตโรงเรียนจาก env `SSO_SYNC_SCHOOL_SCOPE`: `*` = ทุกโรงเรียน หรือรายการ school uuid คั่นด้วย comma **ห้ามรับ `school_id` จาก request**
- ไม่ใช้ session ของ Auth.js

### Query

| พารามิเตอร์ | ค่าเริ่มต้น | กติกา |
|---|---|---|
| `after` | `"0"` | string ของจำนวนเต็ม ≥ 0 ผิดรูปแบบ → 400 |
| `limit` | 200 | 1–500 ผิด → 400 |

### ขั้นตอน

1. ถ้า `after > 0` และ `after < deleted_up_to` → `410 {"error": "cursor_expired"}`
2. อ่าน event `WHERE id > after AND created_at <= now() - interval '5 seconds' ORDER BY id LIMIT limit+1` (หน่วง 5 วินาทีกัน transaction ที่ commit ช้ากว่าเลข id)
3. `has_more` = ได้เกิน `limit` แถว ตัดให้เหลือ `limit`
4. `next_cursor` = id ของ event สุดท้ายที่อ่าน (ถ้าไม่มี event คืน `after` เดิม) เป็น string
5. กรอง event ที่ `school_id` อยู่นอกขอบเขตทิ้ง **แต่ cursor ยังเลื่อนผ่าน**
6. รวม event ซ้ำเป็น `(entity_type, entity_id)` เดียว แล้วโหลดสถานะปัจจุบันจากตารางจริง
7. เรียง items: `school` → `branch` → `course` → `student`
8. entity ที่หาแถวไม่เจอแล้ว (ถูกลบจริง) → ข้ามและ log

### Response 200

```json
{
  "items": [
    { "type": "school", "id": "<uuid>", "school_id": "<uuid>",
      "data": { "slug": "iddriver", "name": "...", "is_active": true, "deleted": false } },
    { "type": "branch", "id": "<uuid>", "school_id": "<uuid>",
      "data": { "code": "KK", "name": "โนนทัน", "is_active": true, "deleted": false } },
    { "type": "course", "id": "<uuid>", "school_id": "<uuid>",
      "data": { "code": "CAR-01", "name": "อบรมรถยนต์ 15 ชม.",
                "vehicle_type": { "code": "CAR", "name": "รถยนต์" },
                "is_active": true, "deleted": false } },
    { "type": "student", "id": "<uuid>", "school_id": "<uuid>",
      "data": {
        "id_number": "1234567890123",
        "title_prefix": "นาย",
        "first_name": "สมชาย",
        "last_name": "ใจดี",
        "fullname_en": "Mr. Somchai Jaidee",
        "updated_at": "2026-11-04T09:15:00+07:00",
        "enrollments": [
          { "id": "<uuid>", "status": "active",
            "enrolled_at": "2026-11-04T09:14:58+07:00",
            "completed_at": null, "cancelled_at": null,
            "branch": { "id": "<uuid>", "code": "KK", "name": "โนนทัน" },
            "course": { "id": "<uuid>", "code": "CAR-01", "name": "อบรมรถยนต์ 15 ชม.",
                        "vehicle_type": { "code": "CAR", "name": "รถยนต์" } } }
        ]
      } }
  ],
  "next_cursor": "48213",
  "has_more": false
}
```

ที่มาของแต่ละฟิลด์:

| ฟิลด์ | มาจาก |
|---|---|
| `deleted` | `deleted_at IS NOT NULL` (ตารางที่มีคอลัมน์นี้) |
| `title_prefix` | `title_prefixes.name` ผ่าน `students.title_prefix_id` (null ได้) |
| `enrollments` | **ทุก** enrollment ของนักเรียนคนนั้น รวมที่ `cancelled` เรียงตาม `enrolled_at` |
| `status` | `enrollments.status` ตรง ๆ (`active`, `pending_cancellation`, `cancelled`, `completed`) |
| (ไม่ส่ง `course_option`) | ตัดออกตามที่ทีม DTMS แจ้ง 2026-11-04 SSO แยกต่างชาติจาก `id_number` เอง |
| `vehicle_type` | `vehicle_types.code`, `name` ผ่าน `courses.vehicle_type_id` |
| เวลา | ISO 8601 พร้อม offset |

**ห้ามส่ง**: ที่อยู่ เบอร์โทร วันเกิด เพศ รูป consent ข้อมูลเงิน ครูฝึก ตารางเรียน รอบสอบ (PDPA ส่งเท่าที่ SSO ใช้)

### Error

| Status | เมื่อ |
|---|---|
| 401 | token ผิดหรือไม่มี |
| 400 | `after`/`limit` ไม่ถูกต้อง |
| 404 | ไม่ได้ตั้ง `SSO_SYNC_TOKEN` |
| 410 | cursor เก่ากว่าที่ล้างไปแล้ว |

## 3. โครงสร้างโค้ด

- `lib/integrations/sso/` เก็บ logic
  - `snapshot-rules.ts`: ฟังก์ชัน pure แปลงแถวจาก DB → item ตามสัญญา, รวม event ซ้ำ, เรียงลำดับ, parse `after`/`limit`, ตรวจ scope
  - `snapshot-rules.test.ts`: เทสต์ด้วย `tsx --test` และ**เพิ่มเข้า script `test` ใน `package.json`**
  - `changes.ts`: query DB + เรียก rules
  - `auth.ts`: ตรวจ token แบบ constant-time
- route handler บางที่สุด เรียก `lib/integrations/sso/changes.ts`

## 4. Fixture

เก็บ JSON response ที่ builder สร้างไว้ใน `lib/integrations/sso/__fixtures__/` และให้เทสต์เทียบผลกับ fixture อย่างน้อย:

1. นักเรียนใหม่ ลงทะเบียน 1 คอร์ส
2. การลงทะเบียนถูกยกเลิก (เหลือแต่ `cancelled`)
3. นักเรียน 2 คอร์ส ต่างประเภทรถ
4. นักเรียนที่ `title_prefix`, `fullname_en` เป็น `null`
5. นักเรียนที่การลงทะเบียนทั้งหมด `completed`
6. เปลี่ยนตัวผู้เรียน: นักเรียนเดิมและนักเรียนใหม่ต้องได้ item ทั้งคู่
7. หน้าเปล่า `{"items": [], "next_cursor": "...", "has_more": false}`

ใช้ uuid และเลขบัตรสมมติ **ห้ามใช้ข้อมูลนักเรียนจริง** fixture ชุดนี้จะถูกคัดลอกไปใช้เทสต์ฝั่ง SSO

## 5. Backfill / re-emit

สคริปต์ (เช่น `scripts/sso-reemit.ts` รันด้วย `tsx`) ใส่ event หนึ่งแถวต่อทุก school, branch, course, student ในขอบเขต `SSO_SYNC_SCHOOL_SCOPE`

- เป็น `INSERT ... SELECT` ใน transaction เดียวต่อชนิด
- รันซ้ำได้ (ผลคือ SSO ได้ข้อมูลซ้ำ ซึ่งไม่เป็นปัญหา)
- มีตัวเลือกจำกัดโรงเรียนเดียวได้
- ใช้ทั้งตอนเปิดใช้ครั้งแรก และตอน SSO ได้ 410 แล้วต้องเริ่ม cursor จาก 0

## 6. Cron ล้าง event

`app/api/cron/integration-events-cleanup/route.ts` ตาม pattern ของ `app/api/cron/audit-cleanup/route.ts` (auth ด้วย `CRON_SECRET`)

- ลบ event ที่เก่ากว่า 30 วัน
- อัปเดต `integration_event_watermark.deleted_up_to` = id สูงสุดที่ลบ ใน transaction เดียวกัน
- เพิ่มวิธีตั้ง crontab ใน README ส่วน "ตั้ง Cron Job" (วันละครั้ง)

## 7. Env และเอกสาร

- เพิ่มใน `.env.example` แบบคอมเมนต์ไว้ ไม่มีค่าจริง: `SSO_SYNC_TOKEN`, `SSO_SYNC_TOKEN_PREVIOUS`, `SSO_SYNC_SCHOOL_SCOPE`
- อัปเดต `docs/database.md` (ตารางใหม่และ trigger)
- **ห้าม commit token จริง**

## เสร็จเมื่อ

- [ ] migration รันผ่านบนฐานข้อมูล dev และ trigger สร้าง event เมื่อเพิ่ม/แก้นักเรียน การลงทะเบียน คอร์ส สาขา
- [ ] `npm test` ผ่าน รวมเทสต์ใหม่ และ `npm run lint` ผ่าน
- [ ] เรียก endpoint ด้วย curl: token ผิด → 401, ไม่ตั้ง token → 404, `after=abc` → 400, ปกติ → 200 ตามรูปแบบข้างบน
- [ ] ลงทะเบียนนักเรียนใหม่ที่ POS แล้วภายในไม่กี่วินาที item ของนักเรียนคนนั้นโผล่ใน feed พร้อม enrollment
- [ ] ยกเลิกการลงทะเบียนแล้ว feed ส่ง item เดิมที่ `status = cancelled`
- [ ] backfill บน dev แล้วเรียกจาก `after=0` ได้ข้อมูลครบทุกหน้า (`has_more` วนจนเป็น `false`)
- [ ] fixture 1–7 อยู่ใน `lib/integrations/sso/__fixtures__/`
- [ ] ADR และ `docs/database.md` อัปเดตแล้ว

## ส่งกลับมาให้ฝั่ง SSO

1. ชื่อ branch และ commit ที่ทำเสร็จ
2. ไฟล์ fixture ทั้งหมด
3. URL ของ DTMS staging ที่เปิด feed แล้ว และ school uuid ของ IDDrives (ส่ง token แยกทางที่ปลอดภัย ไม่ใส่ในไฟล์หรือแชต)
4. รายการ vehicle type `code` ที่ IDDrives ใช้จริง (ไม่ต้องส่งรายการ course option แล้ว)
5. จุดที่ทำต่างจากเอกสารนี้ (ถ้ามี) พร้อมเหตุผล

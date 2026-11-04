# 10 · สัญญา API ระหว่าง DTMS และ SSO (ร่าง v0)

เอกสารนี้เป็นจุดตกลงร่วมของสองทีม แก้ที่นี่ก่อนแก้โค้ด เหตุผลการออกแบบอยู่ใน [09-dtms-sync-design.md](09-dtms-sync-design.md) ฝั่งผู้รับคือ SSO ใหม่แบบหลายโรงเรียน ([11-new-sso-design.md](11-new-sso-design.md))

## ภาพรวม

| ทิศทาง | Endpoint | หน้าที่ |
|---|---|---|
| SSO → DTMS | `GET {DTMS}/api/integrations/sso/changes` | ดึง snapshot ของสิ่งที่เปลี่ยนหลัง cursor |
| DTMS → SSO | `POST {SSO}/api/integrations/dtms/ping` | กริ่งบอกว่ามีของใหม่ (ไม่มีข้อมูล) |
| SSO → DTMS | `POST {DTMS}/api/integrations/sso/staff/verify` | ตรวจ username/รหัสผ่านของเจ้าหน้าที่/ครูฝึก (ขั้น 7, ยังไม่ต้องทำรอบแรก) |

ทั้งสองทางใช้ HTTPS เท่านั้น

## การยืนยันตัวตน

| Endpoint | Header | ค่าเก็บที่ |
|---|---|---|
| `changes` | `Authorization: Bearer <token>` | DTMS: `SSO_SYNC_TOKEN`, `SSO_SYNC_SCHOOL_SCOPE` (`*` = ทุกโรงเรียน หรือรายการ school id) · SSO: `DTMS_SYNC_TOKEN` |
| `ping` | `X-DTMS-Signature: <secret>` | ทั้งสองฝั่ง: `DTMS_PING_SECRET` |

- token ฝั่ง DTMS เป็นระดับแพลตฟอร์ม ขอบเขตโรงเรียนมาจาก `SSO_SYNC_SCHOOL_SCOPE` ใน env (ค่าเริ่มต้นที่เสนอคือ `*`) **ไม่รับ `school_id` จาก request** (ตามกติกา tenant ของ DTMS) การจำกัดเป็นรายการช่วยตอนทดสอบหรือเปิดทีละโรงเรียน
- เปรียบเทียบ secret แบบ constant-time; หมุนค่าได้ด้วยการรองรับสองค่าชั่วคราว
- ห้าม commit ค่าใด ๆ ลง repo (ต่างจากของเดิมใน `Fetch*`)

## `GET /api/integrations/sso/changes`

### Query

| พารามิเตอร์ | ชนิด | ค่าเริ่มต้น | ความหมาย |
|---|---|---|---|
| `after` | string | `"0"` | cursor ที่ได้จากครั้งก่อน (opaque — ห้ามคำนวณเอง) |
| `limit` | int | 200 | จำนวน event สูงสุดที่จะอ่าน (สูงสุด 500) |

### Response 200

```json
{
  "items": [
    {
      "type": "school",
      "id": "51f0...uuid",
      "school_id": "51f0...uuid",
      "data": { "slug": "iddriver", "name": "โรงเรียนสอนขับรถ ...", "is_active": true, "deleted": false }
    },
    {
      "type": "branch",
      "id": "9b1c...uuid",
      "school_id": "51f0...uuid",
      "data": { "code": "KK", "name": "โนนทัน", "is_active": true, "deleted": false }
    },
    {
      "type": "course",
      "id": "c2d4...uuid",
      "school_id": "51f0...uuid",
      "data": {
        "code": "CAR-01", "name": "อบรมรถยนต์ 15 ชม.",
        "vehicle_type": { "code": "CAR", "name": "รถยนต์" },
        "is_active": true, "deleted": false
      }
    },
    {
      "type": "student",
      "id": "a7e3...uuid",
      "school_id": "51f0...uuid",
      "data": {
        "id_number": "1234567890123",
        "title_prefix": "นาย",
        "first_name": "สมชาย",
        "last_name": "ใจดี",
        "fullname_en": "Mr. Somchai Jaidee",
        "updated_at": "2026-11-04T09:15:00+07:00",
        "enrollments": [
          {
            "id": "e1f2...uuid",
            "status": "active",
            "enrolled_at": "2026-11-04T09:14:58+07:00",
            "completed_at": null,
            "cancelled_at": null,
            "branch": { "id": "9b1c...uuid", "code": "KK", "name": "โนนทัน" },
            "course": {
              "id": "c2d4...uuid", "code": "CAR-01", "name": "อบรมรถยนต์ 15 ชม.",
              "vehicle_type": { "code": "CAR", "name": "รถยนต์" }
            }
          }
        ]
      }
    }
  ],
  "next_cursor": "48213",
  "has_more": false
}
```

### ความหมายและกติกา

- **items คือสถานะปัจจุบัน ไม่ใช่เหตุการณ์** ถ้าระหว่าง cursor มีการแก้นักเรียนคนเดียว 5 ครั้ง จะได้ item เดียวที่เป็นค่าล่าสุด
- item เดียวกันอาจมาซ้ำในหน้าถัดไปหรือรอบถัดไป ผู้รับต้อง upsert แบบ idempotent
- ภายในหน้าเดียว DTMS เรียง `school` → `branch` → `course` → `student` แต่ `student` ฝังข้อมูลสาขาและคอร์สที่จำเป็นไว้แล้ว ผู้รับจึงไม่ต้องพึ่งลำดับ
- `student.data.enrollments` คือการลงทะเบียน **ทั้งหมด** ของนักเรียนคนนั้นในโรงเรียนนั้น (รวมที่ยกเลิก) ผู้รับใช้เขียนทับได้ทั้งชุด
- `status` ของการลงทะเบียน: `active`, `pending_cancellation`, `cancelled`, `completed` (ตามอภิธานศัพท์ของ DTMS)
- `school_id` ของ item ชนิด `school` เท่ากับ `id` ของตัวเอง
- `fullname_en`, `title_prefix` เป็น `null` ได้
- **ไม่มีฟิลด์ `course_option`** (ทีม DTMS ตัดออก 2026-11-04) feed แยกไม่ได้ว่านักเรียนซื้อแบบต่างชาติหรือไม่ ผู้รับแยก "ต่างชาติ" จาก `id_number` เองโดยถือว่าเลขที่ไม่ใช่ตัวเลข 13 หลักพอดีคือต่างชาติ (กฎเดียวกับ `fetch:*` เดิม)
- เวลาทั้งหมดเป็น ISO 8601 พร้อม offset
- `has_more: true` → ผู้รับเรียกต่อทันทีด้วย `after = next_cursor` จนได้ `false`
- ผู้รับบันทึก `next_cursor` **หลัง** เขียนทุก item ของหน้านั้นเสร็จแล้วเท่านั้น
- DTMS ไม่คืน event ที่อายุน้อยกว่า 5 วินาที เพื่อกันกรณี transaction ที่ commit ช้ากว่าเลข id (ผู้รับไม่ต้องทำอะไร)
- ถ้า cursor เก่ากว่าที่ DTMS เก็บไว้ (event ถูกล้างแล้ว) → ตอบ `410 Gone` ผู้รับต้องขอให้ DTMS re-emit แล้วเริ่มจาก `"0"`

### ข้อมูลที่ตั้งใจไม่ส่ง

ที่อยู่, เบอร์โทร, วันเกิด, เพศ, รูปนักเรียน, consent, ข้อมูลใบเสร็จ/การชำระเงิน, ครูฝึก, ตารางเรียน, รอบสอบ — SSO ไม่ได้ใช้ (เพิ่มได้ภายหลังเป็นรายฟิลด์ผ่านการแก้สัญญานี้ เช่นถ้า KST-Plus ต้องการ)

### Error

| Status | เมื่อ | ผู้รับทำ |
|---|---|---|
| 401 | token ผิดหรือไม่มี | หยุด, แจ้งแอดมิน |
| 400 | `after` / `limit` ไม่ถูกต้อง | หยุด, แจ้งแอดมิน |
| 410 | cursor เก่าเกินไป | หยุด, ขอ re-emit |
| 429 / 5xx | DTMS ไม่พร้อม | ลองใหม่รอบถัดไป (cursor ไม่เลื่อน) |

## `POST /api/integrations/dtms/ping`

- Body: `{}` (หรือ `{"school_id": "..."}` เพื่อ log เท่านั้น ผู้รับไม่ใช้ค่านี้ตัดสินอะไร)
- Response: `202 Accepted` ทันที แล้ว SSO สั่ง `dtms:sync` หลังตอบ
- `401` ถ้า secret ไม่ตรง
- ผู้ส่งตั้ง timeout ≤ 3 วินาที ไม่ retry (รอบ 1 นาทีของ SSO ครอบคลุมอยู่แล้ว)
- ผู้รับจำกัดอัตรา (throttle) และใช้ lock กันรันซ้อนกับรอบตาม scheduler

## `POST /api/integrations/sso/staff/verify` (ขั้น 7)

ใช้ token เดียวกับ `changes` · Body: `{"username": "...", "password": "..."}`

| Status | Body | ความหมาย |
|---|---|---|
| 200 | `{"user": {"id", "school_id", "branch_id", "role", "title_prefix", "first_name", "last_name", "is_active"}}` | รหัสผ่านถูกและบัญชีใช้งานได้ |
| 401 | `{"error": "invalid_credentials"}` | ผิด (ไม่บอกว่าผิดที่ username หรือรหัสผ่าน) |
| 423 | `{"error": "locked", "locked_until": "..."}` | DTMS ล็อกบัญชีอยู่ (`locked_until`) |
| 403 | `{"error": "not_allowed"}` | `super_admin`, บัญชีปิด, หรือโรงเรียนอยู่นอกขอบเขต token |

- DTMS นับการกรอกผิดตามกติกาเดิมของตัวเอง (`failed_login_count`) ผู้รับไม่ต้องนับซ้ำ แต่ควรจำกัดอัตราต่อ IP เอง
- ผู้รับต้องไม่ log body ของ request นี้
- ถ้า `must_change_password` เป็นจริงใน DTMS ให้ตอบ 403 `{"error": "must_change_password"}` เพื่อให้ไปเปลี่ยนที่ DTMS ก่อน

## Fixture ร่วม

เก็บตัวอย่าง response ไว้ทั้งสองฝั่ง (เสนอ `tests/Fixtures/dtms/` ใน SSO และ `lib/integrations/sso/__fixtures__/` ใน DTMS) อย่างน้อย:

1. นักเรียนใหม่ ลงทะเบียน 1 คอร์ส
2. เลขบัตรเดียวกันในสองโรงเรียน (ต้องได้สองบัญชีแยกกัน)
3. การลงทะเบียนถูกยกเลิก (เหลือแต่ `cancelled`)
4. นักเรียน 2 คอร์ส ต่างประเภทรถ
5. vehicle type หรือตัวเลือกคอร์สที่ไม่มีใน `course_type_map` (ต้องเกิด failure ไม่ใช่ crash)
6. หน้าเปล่า `{"items": [], "next_cursor": "...", "has_more": false}`
7. นักเรียนที่การลงทะเบียนทั้งหมด `completed` (สิทธิ์ต้องหมด และ token ต้องถูกเพิกถอน)

DTMS ทดสอบว่า snapshot builder สร้างผลตรง fixture; SSO ทดสอบว่า applier เขียนฐานข้อมูลตาม fixture

## การเปลี่ยนสัญญา

- เพิ่มฟิลด์ใหม่ได้โดยไม่ต้องขึ้นเวอร์ชัน ผู้รับต้องไม่พังเมื่อเจอฟิลด์ที่ไม่รู้จัก
- ลบ/เปลี่ยนความหมายฟิลด์ → ขึ้น `/api/integrations/sso/v2/...` และให้สองเวอร์ชันอยู่คู่กันจนผู้รับย้ายเสร็จ

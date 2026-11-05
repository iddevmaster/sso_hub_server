# 06 · การซิงก์ข้อมูลและคำสั่ง artisan

คำสั่งทั้งหมดอยู่ใน `app/Console/Commands/` และถูกโหลดอัตโนมัติ (`Kernel::commands` เรียก `$this->load`) **ไม่มี schedule** ต้องตั้ง cron / Windows Task Scheduler เอง (เช่น รันทุกวัน)

## สรุปคำสั่ง

| คำสั่ง | ใช้ทำอะไร | ชนิด |
|---|---|---|
| ~~`php artisan fetch:apidata {dest}`~~ | **ลบแล้ว** (ดูหัวข้อด้านล่าง) | ยกเลิก |
| `php artisan fetch:posdata` | ดึงนักเรียน+คอร์ส **ของวันนี้** จาก POS TrainingZenter | ซิงก์ประจำ |
| `php artisan update:database-normalize` | แปลง `users.brn`/`users.agn` จากชื่อ/id เป็น `brn_id`/`agn_id` | ซ่อมข้อมูล (ครั้งเดียว) |
| `php artisan update:coursetype` | สร้างประเภทคอร์สเริ่มต้นและจัดประเภทให้คอร์สเดิม | ซ่อมข้อมูล (ครั้งเดียว) |
| `php artisan update:usercourse` | ย้ายคอร์สจากคอลัมน์ JSON `users.course` ไปตาราง `user_has_courses` | ซ่อมข้อมูล (ครั้งเดียว) |

## `fetch:apidata` (DSMS) — ลบแล้ว

คำสั่งนี้ถูกลบออกจากโค้ดแล้ว เพราะ EM (DSMS, `dsmsys.net`) ปิดให้บริการ จึงไม่มีข้อมูลเข้า และไฟล์เดิมมี Basic auth ที่ commit ไว้ ให้ลบ cron ของคำสั่งนี้บนเซิร์ฟเวอร์ด้วย ตัวแทนคือการซิงก์จาก DTMS ดู `docs/13-phase1-legacy-dtms-bridge.md`

## การเพิ่มสาขาใหม่ (ต้องแก้หลายที่)

1. (ส่วน `switch ($apiDest)` ของ `fetch:apidata` ถูกลบแล้ว) สาขา DTMS ผูกผ่านตาราง `dtms_branch_map` ด้วยคำสั่ง `dtms:branches` / `dtms:map-branch` ดู `docs/13`
2. `HomeController::index` — เพิ่มเงื่อนไขค้นชื่อสาขา (ภาษาไทย) → ค่า `send_branch` สำหรับ Check-Before Driving
3. ตั้ง cron สำหรับคำสั่งซิงก์ที่ใช้งานอยู่ (ดู `docs/13`)
4. ตรวจว่าเลือก `brn_id` ไม่ซ้ำกับที่มีอยู่

## คำสั่งซ่อมข้อมูล

- `update:database-normalize` — วนทุก user: ค้น branch/agency ด้วย `name` หรือ `id` เดิม แล้วเขียนทับด้วย `brn_id`/`agn_id`
- `update:coursetype` — สร้างประเภท "รถยนต์", "รถจักรยานยนต์", "รถยนต์ต่างชาติ", "รถจักรยานยนต์ต่างชาติ" (ถ้ายังไม่มี) แล้ววนทุกคอร์สเพื่อตั้ง `course_type` จาก **คำในชื่อคอร์ส** ("รถยนต์"/"รย." → รถยนต์, "จักรยานยนต์"/"จยย." → รถจักรยานยนต์, มีคำว่า "ต่างชาติ" → ประเภทต่างชาติ) คอร์สที่ไม่ตรงกฎใดจะถูกตั้งเป็น `null` **ทับค่าเดิมที่เคยตั้งไว้ด้วย** จึงไม่ควรรันซ้ำหลังจัดประเภทด้วยมือ
- `update:usercourse` — อ่านคอลัมน์ JSON `users.course` แล้วสร้างแถว `user_has_courses` ที่ขาด

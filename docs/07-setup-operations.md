# 07 · การติดตั้งและการดูแลระบบ

## ความต้องการ

PHP ^8.1 (พร้อม extension มาตรฐานของ Laravel) · Composer · Node.js + npm · MySQL

## ติดตั้งครั้งแรก

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed       # สร้างตาราง + role + ผู้ใช้ admin
php artisan passport:keys        # สร้างคีย์เข้ารหัส token
php artisan passport:client      # สร้าง OAuth client ให้ระบบปลายทางแต่ละระบบ
npm run build                    # หรือ npm run dev ระหว่างพัฒนา
php artisan serve
```

- `.env.example` ตั้ง `DB_DATABASE=sso_hub_server_db`, `DB_USERNAME=root`, รหัสผ่านว่าง — ปรับให้ตรงกับเครื่อง
- ไฟล์ `.env.example` มี `APP_KEY` ค่าหนึ่งใส่ไว้แล้ว ให้รัน `key:generate` สร้างค่าใหม่ของตัวเองเสมอ
- หลังติดตั้ง **เปลี่ยนรหัสผ่านผู้ใช้ `admin`** ที่สร้างโดย `UserSeeder`
- `passport:client` ให้ `redirect URI` ของระบบปลายทางนั้น ๆ; เก็บ client secret ไว้ในระบบปลายทาง ไม่ใส่ใน repo

## การสร้างผู้ใช้เพิ่ม

- เจ้าหน้าที่/แอดมิน: ล็อกอินเป็น admin → เมนู "ข้อมูลผู้ใช้" → เพิ่ม (ต้องมีสาขาและ role ให้ตั้งใน "ข้อมูลระบบ" ก่อน)
- นักเรียน: เพิ่มจากหน้า "ข้อมูลนักเรียน", นำเข้า Excel หรือรอให้ `dtms:sync` ซิงก์เข้ามา
- Agency/Branch/ประเภทคอร์ส ชุดแรกมาจาก `update:coursetype` และ `fetch:*` ที่สร้างให้อัตโนมัติ (ดู [06-data-sync.md](06-data-sync.md))

## งานประจำที่ต้องตั้งเอง

ระบบไม่มี scheduler ให้ตั้งคำสั่งภายนอกให้รันเป็นรอบ (เช่นทุกวัน) อย่างน้อย:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1   # ให้ dtms:sync ทำงานทุกนาที (ต้องตั้ง DTMS_SYNC_URL / DTMS_SYNC_TOKEN / DTMS_SCHOOL_ID ใน .env)
php artisan fetch:posdata
```

`fetch:apidata` ถูกลบแล้ว (EM ปิด) ข้อมูลนักเรียนมาจาก `dtms:sync` ดู [13-phase1-legacy-dtms-bridge.md](13-phase1-legacy-dtms-bridge.md) ส่วน `fetch:posdata` ดึงของ "วันนี้" เท่านั้น ถ้าพลาดวันใดต้องรันย้อนหลังเอง

## ทดสอบและตรวจโค้ด

```bash
php artisan test                      # PHPUnit (ปัจจุบันมีเฉพาะ ExampleTest ของ Laravel)
php artisan test --filter=ExampleTest
./vendor/bin/pint                     # จัดรูปแบบโค้ด
```

`phpunit.xml` ปิดบรรทัดตั้งค่า sqlite ไว้ ดังนั้นเทสต์จะใช้ฐานข้อมูลตาม `.env` — ควรเปิดใช้ sqlite in-memory หรือสร้างฐานข้อมูลทดสอบแยกก่อนเขียนเทสต์จริง
`ExampleTest` คาดหวังว่า `/` ตอบ 200 แต่หน้านี้ต้องล็อกอิน จึงน่าจะไม่ผ่านตามค่าเริ่มต้น (ยังไม่ได้รัน)

## ค่า config ที่เกี่ยวข้อง

| ไฟล์ | ประเด็น |
|---|---|
| `config/passport.php` | `guard = web`, `client_uuids = true`; รองรับคีย์จาก env `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY` |
| `config/auth.php` | guard `api` ใช้ driver `passport` |
| `app/Providers/AuthServiceProvider.php` | อายุ token, รายการ scope, โมเดล client |
| `config/permission.php` | ตั้งค่า Spatie (ใช้ค่าเริ่มต้น) |
| `.env` | `APP_URL`, `DB_*`, `SESSION_*`, `MAIL_*` (ฟีเจอร์รีเซ็ตรหัสผ่านทางอีเมลของ laravel/ui น่าจะใช้ไม่ได้ เพราะคอลัมน์ `email` ถูก rename เป็น `username` — ยังไม่ได้ทดสอบ) |

## ไฟล์ที่ไม่ควรอยู่ใน repo

`mynote.txt` (มี OAuth client secret) และ `.rnd` ถูก commit อยู่ ดู [08-known-issues.md](08-known-issues.md)

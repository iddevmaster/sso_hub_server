# 08 · ปัญหาที่พบจากการอ่านโค้ด

รายการนี้มาจากการอ่านซอร์สโค้ด **ยังไม่ได้รันระบบ** แต่ละข้อระบุไฟล์ที่เกี่ยวข้อง และระดับความมั่นใจ:
**ยืนยันจากโค้ด** = เห็นชัดในโค้ดที่มีอยู่ · **ต้องตรวจสอบ** = อนุมานจากโค้ด/แพ็กเกจ ต้องลองรันจริงก่อนแก้

เรียงตามความเร่งด่วน (A = ควรแก้ก่อน)

## A · ความปลอดภัย

| # | ปัญหา | ที่อยู่ | ความมั่นใจ |
|---|---|---|---|
| A1 | **Staff สร้าง/แก้ user และกำหนด role ได้เอง** — route `/users/store|update|delete` ใช้ middleware `adminAndStaff` ทั้งที่หน้าจอเป็นของ admin และ `storeUser` รับ `role` จาก request โดยตรง → staff ส่ง request เองเพื่อสร้างหรือยกระดับเป็น admin ได้ | `routes/web.php`, `HomeController::storeUser/updateUser` | ยืนยันจากโค้ด — **แก้แล้ว 2026-11-04 (ยังไม่ได้ตรวจบน prod)** — `/users/store|update|delete` เปลี่ยนเป็น middleware `admin` |
| A2 | **Staff จัดการ role / permission / agency / branch ได้** ผ่าน `/data/add|update|delete` (`adminAndStaff`) รวมถึงลบ role `admin` | `routes/web.php`, `HomeController::deleteData` | ยืนยันจากโค้ด — **แก้แล้ว 2026-11-04 (ยังไม่ได้ตรวจบน prod)** — `/data/*` ยังเข้าถึงได้ด้วย `adminAndStaff` เพื่อให้ staff จัดการ "หลักสูตร" (`course`) ได้ตามเดิม (มีปุ่ม Add/แก้/ลบในหน้า `/data`) แต่ `HomeController::authorizeDataType` ตอบ 403 สำหรับประเภทอื่น (agn, brn, role, perm, ctype) ที่ไม่ใช่ admin |
| A3 | **เพิ่ม/ลบคอร์สของนักเรียนไม่ต้องล็อกอิน** — `customer-add-course` และ `customer-remove-course` ไม่มี middleware ใด ๆ และ `CustomerController` ไม่มี constructor ที่บังคับ `auth`; ลบคอร์สเป็น **GET** (ไม่มี CSRF) | `routes/web.php`, `CustomerController::addCourse/removeCourse` | ยืนยันจากโค้ด — **แก้แล้ว 2026-11-04 (ยังไม่ได้ตรวจบน prod)** — ทั้งสอง route ใส่ `adminAndStaff` และ `customer-remove-course` เปลี่ยนจาก GET เป็น POST พร้อม CSRF header ใน `customer-table.blade.php` |
| A4 | **เปิดสมัครสมาชิกสาธารณะ** — `Auth::routes()` เปิด `/register` ทำให้ใครก็สร้างบัญชีได้ (ไม่มี role แต่ล็อกอิน SSO ได้) ควรปิดด้วย `Auth::routes(['register' => false])` | `routes/web.php`, `RegisterController` | ยืนยันจากโค้ด — **แก้แล้ว 2026-11-04 (ยังไม่ได้ตรวจบน prod)** — `Auth::routes(['register' => false])`, `/register` ตอบ 404 (ลิงก์ในเมนูซ่อนเองผ่าน `Route::has`) |
| A5 | **ความลับถูก commit** — OAuth client secret ใน `mynote.txt`, header Basic auth ของ DSMS/POS เขียนตายในคำสั่ง fetch, รหัสผ่าน admin เริ่มต้นใน `UserSeeder`, `APP_KEY` ใน `.env.example`, ไฟล์ `.rnd` ควรเปลี่ยนค่าทั้งหมด ย้ายเข้า `.env`/config และพิจารณาล้างประวัติ git | repo root, `app/Console/Commands/Fetch*`, `database/seeders` | ยืนยันจากโค้ด |
| A6 | **Staff แก้/ลบนักเรียนข้ามสาขาได้** — หน้ารายการกรองตามสาขา แต่ `updateCustomer`/`deleteCustomer`/`addCourse` รับ id ตรง ๆ ไม่ตรวจสาขา | `CustomerController` | ยืนยันจากโค้ด |
| A7 | **รหัสผ่านถูกส่งกลับใน response** — หลาย action ตอบ `$request->all()` กลับไปซึ่งรวม `pass` (รหัสผ่านแบบ plaintext ที่ผู้ใช้เพิ่งกรอก) | `HomeController`, `CustomerController` | ยืนยันจากโค้ด |
| A8 | **รหัสผ่านเริ่มต้น = เลขบัตรประชาชน**, และเลขบัตรถูกใส่ใน URL path ของลิงก์ Check-Before Driving (ติดใน log/ประวัติเบราว์เซอร์) | `Fetch*`, `CustomerController::importData`, `home.blade.php` | ยืนยันจากโค้ด |
| A9 | **เปลี่ยนข้อมูลด้วย GET** — `/customer/clean` (ลบถาวรทั้งถังขยะ), `/customer/remove/...`, `/toggle-theme` | `routes/web.php` | ยืนยันจากโค้ด |
| A10 | `/api/kst-plus` คืน user ทั้งโมเดลโดยไม่ตรวจ permission; `/api/i-prompt` คืนโมเดลทั้งก้อน (ฟิลด์ `password` ถูก hidden แต่ฟิลด์ส่วนตัวอื่นหลุดไปด้วย) | `routes/api.php` | ยืนยันจากโค้ด |
| A11 | แทบไม่มี validation ที่ฝั่ง `HomeController` (add/update/delete/storeUser/updateUser) และใช้ `find()->update()` ซึ่งจะ error ถ้าไม่พบ id | `HomeController` | ยืนยันจากโค้ด |

## B · ความถูกต้องของข้อมูลและ logic

| # | ปัญหา | ที่อยู่ | ความมั่นใจ |
|---|---|---|---|
| B1 | **นำเข้า Excel เขียนข้อมูลแบบเก่า** — เก็บ `brn`/`agn` เป็น *ชื่อ* (ควรเป็น `brn_id`/`agn_id`) และเขียน JSON ลง `users.course` แทน `user_has_courses` ทำให้คอร์สที่นำเข้าไม่ถูกนับใน `/api/user` จนกว่าจะรัน `update:usercourse` / `update:database-normalize` | `CustomerController::importData` | ยืนยันจากโค้ด |
| B2 | `agencies.agn_id` / `branches.brn_id` ไม่ถูกตั้งเมื่อสร้างจากหน้า "ข้อมูลระบบ" แต่ `users` อ้างอิงด้วยรหัสนี้ → สาขา/หน่วยงานที่สร้างด้วยมือใช้ไม่ได้จนกว่าจะแก้ DB | `HomeController::addData` | ยืนยันจากโค้ด |
| B3 | `courses.agn` ไม่สม่ำเสมอ: DSMS ใช้ `agn_id`, POS ใช้ `agencies.id`; หน้า `/data` ของ staff กรองด้วย `users.agn` (ซึ่งเป็น `agn_id`) → คอร์ส POS อาจไม่โผล่ | `FetchApiDataToDatabase`, `FetchPOSDataToDatabase`, `dataTable` | ยืนยันจากโค้ด |
| B4 | กฎสัญชาติต่างกัน: DSMS ใช้ `strlen != 13` → EN, POS ใช้ `strlen < 13` → EN | `Fetch*` | ยืนยันจากโค้ด |
| B5 | `fetch:*` ไม่อัปเดตนักเรียนเดิม (ชื่อ/สาขา/สถานะการเรียนไม่เปลี่ยนตามต้นทาง) และนักเรียนที่ถูก soft delete จะชน unique ของ `username` ตอนสร้างใหม่ | `Fetch*` | ยืนยันจากโค้ด / unique **ต้องตรวจสอบ** |
| B6 | `fetch:*` ล้มเหลวเงียบ ๆ ระดับแถว (echo แล้วไปต่อ, exit code 0) → monitoring จับไม่ได้ | `Fetch*` | ยืนยันจากโค้ด |
| B7 | `/api/user` พังถ้าผู้ใช้ที่ `role != staff` ไม่มีคอร์สเลย (`$courses_list->first()->course_id` บน null) — รวมถึง admin | `routes/api.php` | ยืนยันจากโค้ด |
| B8 | `/api/courses`: ถ้าไม่พบ agency ที่ prefix ตรงกับรหัสคอร์ส จะอ้าง `$agn->name` บน null | `routes/api.php` | ยืนยันจากโค้ด |
| B9 | `update:coursetype` ใช้ `strpos(...)` เป็นเงื่อนไข boolean โดยตรง — ถ้าคำค้นอยู่ตำแหน่ง 0 ของชื่อคอร์สจะถูกมองว่าไม่พบ และคอร์สที่ไม่ตรงกฎถูกตั้ง `course_type = null` ทับของเดิม | `UpdateCourseType` | ยืนยันจากโค้ด |
| B10 | สร้างคอร์สจากหน้าเว็บโดย admin (ซึ่งมี `agn` ว่างจาก seeder) จะอ่าน `getAgn->prefix` จาก null → น่าจะเกิดข้อผิดพลาด | `HomeController::addData` | **ต้องตรวจสอบ** |
| B11 | คำสั่ง `fetch:*` ดึงเฉพาะข้อมูลของวันนี้ ไม่รองรับย้อนหลัง และระบบไม่มีการตั้งเวลารันในตัว | `Fetch*`, `Console/Kernel.php` | ยืนยันจากโค้ด |

## C · การยืนยันตัวตนที่ต้องลองรันจริง

| # | ประเด็น | เหตุผล |
|---|---|---|
| C1 | **ล็อกอินด้วย `username`** — ฟอร์มส่งฟิลด์ `username` แต่ `LoginController` ใช้ `AuthenticatesUsers` มาตรฐานโดยไม่ override `username()` ซึ่งค่าเริ่มต้นของ laravel/ui คือ `email` ถ้าไม่ได้ patch ที่อื่น การล็อกอินควรจะไม่ทำงาน (ไม่มี `vendor/` ให้ตรวจ) | ต้องตรวจสอบ |
| C2 | `auth.basic` ของ `/api/courses` ใช้คอลัมน์ `email` เป็นค่าเริ่มต้น แต่ถูก rename เป็น `username` แล้ว | ต้องตรวจสอบ |
| C3 | รีเซ็ตรหัสผ่านทางอีเมล / ยืนยันอีเมลของ laravel/ui อาศัยคอลัมน์ `email` | ต้องตรวจสอบ |

## D · โค้ดค้าง / หนี้ทางเทคนิค

- `HomeController` import `App\Models\Department` ซึ่งไม่มีอยู่จริง
- `HomeController::customerLogin` อ้าง view `auth.customer-login` ที่ไม่มี และ `permTable` ไม่มี route (คอมเมนต์ไว้); component Livewire `UserPermission` จึงใช้งานไม่ได้
- `CustomerController::customerAuth` (guard `customer`) ถูกคอมเมนต์ทั้งก้อน
- `resources/views/auth/login.txt`, `mynote.txt`, `.rnd` เป็นไฟล์ที่ไม่น่าอยู่ใน repo
- ตั้ง Sanctum ไว้แต่ไม่ได้ใช้ (ใช้ Passport)
- `CustomerController`/`HomeController` ใหญ่และซ้ำซ้อน (เช่น query รายการนักเรียนซ้ำสองกิ่งสำหรับ admin/staff; ตรรกะสาขา hard-code สองที่)
- ไม่มีเทสต์จริง; `ExampleTest` น่าจะไม่ผ่านเพราะ `/` ต้องล็อกอิน (ได้ 302 ไม่ใช่ 200)
- คำสั่งต่าง ๆ ใช้ `echo` แทน `$this->info()` และ description เป็นข้อความเริ่มต้น "Command description"

## แนวทางลำดับการแก้ (ข้อเสนอ)

1. A1–A3 (ช่องโหว่สิทธิ์) แก้ที่ `routes/web.php` โดยเปลี่ยน middleware เป็น `admin` และเพิ่ม `adminAndStaff` ให้ route คอร์สของนักเรียน พร้อมเปลี่ยน GET→POST/DELETE
2. A5 หมุนความลับทั้งหมด (client secret, Basic auth ของ DSMS/POS, รหัส admin) แล้วย้ายเข้า `.env`
3. A4 ปิด `/register`
4. C1–C3 ลองรันล็อกอินจริงเพื่อยืนยันก่อนทำอย่างอื่นที่เกี่ยวกับ auth
5. B1–B3 ปรับให้การนำเข้า/ซิงก์เขียนข้อมูลรูปแบบเดียวกัน แล้วรัน `update:*` เพื่อซ่อมข้อมูลเดิม

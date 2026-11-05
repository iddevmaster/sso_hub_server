# 04 · บทบาทและสิทธิ์

## บทบาท (role)

มี 3 บทบาท สร้างโดย `RoleSeeder`:

| Role | ใคร | ทำอะไรได้ในเว็บ |
|---|---|---|
| `admin` | ผู้ดูแลระบบ | ทุกอย่าง เห็นนักเรียนทุกสาขา จัดการ user, ถังขยะนักเรียน, `/phpinfo` |
| `staff` | เจ้าหน้าที่สาขา | จัดการนักเรียน (เฉพาะสาขาตัวเองในหน้ารายการ) และหน้า "ข้อมูลระบบ" |
| `customer` | นักเรียน | ล็อกอินเพื่อใช้ SSO และดูหน้าแรกเท่านั้น |

## ระบบสิทธิ์สองชั้น (ต้องดูแลให้ตรงกัน)

1. **คอลัมน์ `users.role`** (string) — ใช้ใน `/api/user` (`role !== 'staff'` เพื่อตัดสินว่าจะดึงคอร์สหรือไม่) และส่งออกให้ระบบปลายทาง
2. **Spatie role** (`model_has_roles`) — ใช้ใน middleware, `@role` / `@hasanyrole` ของ Blade และ `User::role('customer')`

โค้ดที่สร้างหรือแก้ role ต้องเขียนทั้งสองที่ ตัวอย่างที่ทำถูก: `HomeController::storeUser` (`assignRole` แล้วตั้ง `$user->role`) ถ้าแก้ที่เดียว ค่าจะเพี้ยนกัน

## Middleware

| Alias | คลาส | เงื่อนไขผ่าน | ถ้าไม่ผ่าน |
|---|---|---|---|
| `admin` | `AdminRoleMiddleware` | ล็อกอินแล้วและ `hasRole('admin')` | redirect `/` |
| `adminAndStaff` | `StaffRoleMiddleware` | ล็อกอินแล้วและ `hasAnyRole(['admin','staff'])` | redirect `/` |
| `auth` | ของ Laravel | ล็อกอินแล้ว | redirect login |

`HomeController` ใส่ `auth` ใน constructor ทั้งคลาส ส่วน `CustomerController` **ไม่มี** constructor middleware ต้องพึ่ง middleware ที่ผูกกับ route เท่านั้น

## ตารางสิทธิ์ต่อ route (`routes/web.php`)

| Route | Method | ต้องผ่าน | หมายเหตุ |
|---|---|---|---|
| `/login`, `/register`, `/password/*` | — | ไม่ต้อง (guest) | จาก `Auth::routes(['register' => false])` — `/register` ปิดแล้ว (404) ตั้งแต่ 2026-11-04 |
| `/`, `/toggle-theme/{isdark}`, `/different-account` | GET/POST | `auth` | |
| `/users` | GET | `admin` | รายชื่อผู้ใช้ที่ไม่ใช่นักเรียน |
| `/users/store`, `/users/update`, `/users/delete` | POST | `admin` | แก้แล้ว 2026-11-04 (เดิม `adminAndStaff`; ยังไม่ได้ตรวจบน prod) |
| `/data` | GET | `adminAndStaff` | ข้อมูลระบบ: agency/branch/course/type/role/permission |
| `/data/add`, `/data/update`, `/data/delete` | POST | `adminAndStaff` + ตรวจในคอนโทรลเลอร์ | แก้แล้ว 2026-11-04: staff ทำได้เฉพาะประเภท `course`; agn/brn/role/perm/ctype ต้องเป็น admin (403) |
| `/customers`, `/customers/search` | GET | `adminAndStaff` | staff เห็นเฉพาะ `brn` ของตัวเอง |
| `/customer/store`, `/customer/update`, `/customer/delete`, `/customer/import` | POST | `adminAndStaff` | update/delete ไม่ตรวจว่านักเรียนอยู่สาขาเดียวกัน |
| `/customers/deleted` | GET | `admin` | ถังขยะ |
| `/customer/reuse`, `/customer/delete-permanently` | POST | `admin` | กู้คืน / ลบถาวร |
| `/customer/clean` | GET | `admin` | ลบถาวรทุกคนในถังขยะ (เปลี่ยนข้อมูลด้วย GET) |
| `/customers/add-course/{uid}` | POST | `adminAndStaff` | แก้แล้ว 2026-11-04 (เดิมไม่มี middleware) |
| `/customer/remove/{uid}/{cid}` | POST | `adminAndStaff` | แก้แล้ว 2026-11-04 (เดิม GET ไม่มี middleware; ตอนนี้ POST + CSRF) |
| `/phpinfo` | GET | `admin` | |

สัญลักษณ์ ⚠ = จุดที่น่าจะไม่ตั้งใจ รายละเอียดใน [08-known-issues.md](08-known-issues.md)

## Permission ระดับละเอียด (Spatie)

- Permission สร้าง/ลบได้จากหน้า "ข้อมูลระบบ" (`addType=perm` / `deltype=perm`) การลบจะ revoke จากผู้ใช้ที่มีสิทธิ์นั้นก่อน
- การใช้งานจริงในโค้ดมีจุดเดียว: `/api/i-prompt` ตรวจ `can('i-prompt')`
- หน้าจัดการสิทธิ์รายบุคคล (`Livewire\UserPermission`, view `pages/perm-table`) **ไม่มี route ใช้งาน** เพราะ route `/permissions` ถูกคอมเมนต์ไว้

## ขอบเขตข้อมูลตามสาขา

| จุด | admin | staff |
|---|---|---|
| รายการนักเรียน (`/customers`) | ทุกสาขา | เฉพาะ `users.brn` ตรงกับของตัวเอง |
| รายการคอร์ส (`/data`) | ทุกคอร์ส | เฉพาะ `courses.agn` ตรงกับ `users.agn` ของตัวเอง |
| นักเรียนที่สร้างใหม่ | — | ได้ `brn`, `agn`, `staff` ของผู้สร้างอัตโนมัติ |

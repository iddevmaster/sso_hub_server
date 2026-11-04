# 05 · เว็บแอดมิน

เมนูหลัก (จาก `layouts/app.blade.php`): หน้าหลัก · ข้อมูลนักเรียน · ข้อมูลผู้ใช้ (admin เท่านั้น) · ข้อมูลระบบ · เมนูผู้ใช้ (คู่มือ PDF, ออกจากระบบ)
เมนูสามรายการหลังหน้าหลักแสดงเฉพาะ admin/staff ธีม light/dark เก็บใน session (`/toggle-theme/{true|false}`)

การเรียกใช้งานจากหน้าเว็บส่วนใหญ่เป็น AJAX (POST แล้วตอบ JSON) หน้าตารางใช้ DataTables

## หน้าและ controller

| หน้า | Route | Controller@method | View |
|---|---|---|---|
| หน้าหลัก (การ์ดบริการ) | `/` | `HomeController@index` | `home` |
| ข้อมูลนักเรียน | `/customers` | `HomeController@customerTable` | `pages/customer-table` |
| นักเรียนที่ถูกลบ | `/customers/deleted` | `CustomerController@deletedCustomer` | `pages/deleted-customer` |
| ข้อมูลผู้ใช้ (staff/admin) | `/users` | `HomeController@userTable` | `pages/user-table` |
| ข้อมูลระบบ | `/data` | `HomeController@dataTable` | `pages/data-table` |

## ข้อมูลนักเรียน

- รายการแบ่งหน้าละ 10 เรียงใหม่สุดก่อน ค้นหาด้วย `username` / `name` / `lname` (LIKE) คำค้นถูกเก็บใน **session** (`/customers/search?searchText=` แล้ว redirect กลับ) จึงค้างอยู่จนกว่าจะค้นใหม่
- **เพิ่ม** (`customer.store`): ตรวจ `citizen_id` ไม่ซ้ำใน `users.username` (รวมแถวที่ถูกลบแบบ soft), `name` จำเป็น, รหัสผ่าน 8–13 ตัว; บันทึก `brn`/`agn`/`staff` จากผู้สร้าง, `role='customer'` และ assign Spatie role `customer`
- **แก้ไข** (`customer.update`): แก้ข้อมูลส่วนตัว; ถ้าส่ง `pass` มาจะเปลี่ยนรหัสผ่านโดยตรวจความยาว 8–13 ตัวหลังจากบันทึกฟิลด์อื่นไปแล้ว
- **ลบ** (`customer.delete`): soft delete → ไปอยู่หน้าถังขยะ
- **นำเข้า Excel** (`customer.import`): ฝั่ง browser แปลงไฟล์เป็น JSON ส่งมาที่ field `data` (array ของแถว อ้างตำแหน่งคอลัมน์: 0 ชื่อ, 1 เพศ, 4 ชื่อคอร์ส, 5 โทร, 6 ที่อยู่, 7 เลขบัตร) สร้างคอร์สที่ยังไม่มี และสร้างนักเรียนที่ยังไม่มี (รหัสผ่านเริ่มต้น = เลขบัตร) ⚠ ข้อบกพร่องของเส้นทางนี้ดู [08-known-issues.md](08-known-issues.md)
- **เพิ่ม/ลบคอร์สของนักเรียน**: `customer-add-course` (POST, ฟอร์ม `courseSelect`) และ `customer-remove-course` (GET) จัดการแถวใน `user_has_courses`

## ถังขยะนักเรียน (admin)

- กู้คืน `customer-reuse` (`restore`) · ลบถาวรรายคน `delete-permanently` (`forceDelete`) · ล้างถังทั้งหมด `customer-clean` (`forceDelete` ทุกแถวที่ถูกลบ)
- เพราะ `username` ต้องไม่ซ้ำรวมแถวที่ถูกลบ การสร้างนักเรียนเลขบัตรเดิมซ้ำต้องกู้คืนจากถังขยะ ไม่ใช่สร้างใหม่

## ข้อมูลผู้ใช้ (admin)

- แสดงผู้ใช้ทุกคนที่ **ไม่ใช่** role `customer`
- เพิ่ม/แก้: เลือกสาขา (`brn_id`) → ระบบหา `agn` จากสาขานั้นเอง; ตั้ง role (assign Spatie + เขียน `users.role`); เปลี่ยนรหัสผ่านเมื่อกรอก `pass`
- เมื่อแก้ role: ถอด Spatie role เดิมทุกตัวก่อน แล้ว assign ตัวใหม่

## ข้อมูลระบบ (`/data`)

หน้าเดียวจัดการหลายชนิดข้อมูล ผ่าน `/data/add|update|delete` โดยแยกด้วย `addType` / `deltype`:

| ชนิด | สร้าง | แก้ | ลบ | พฤติกรรมพิเศษ |
|---|---|---|---|---|
| `agn` หน่วยงาน | name, prefix | ✓ | ✓ | `agn_id` ไม่ถูกตั้งจากหน้านี้ |
| `brn` สาขา | name, agn | ✓ | ✓ | `brn_id` ไม่ถูกตั้งจากหน้านี้ |
| `course` คอร์ส | name, ctype | ✓ (name, ctype) | ✓ | `agn` = agency ของผู้สร้าง; `code` = `prefix + id + ปี ค.ศ.` |
| `ctype` ประเภทคอร์ส | name | ✓ | ✓ | `code` = ปี + id 4 หลัก |
| `perm` permission | name | — | ✓ | ลบแล้ว revoke จากผู้ใช้ก่อน |
| `role` | name | — | ✓ | ลบแล้ว remove จากผู้ใช้ก่อน |

หน้านี้ยังแสดงจำนวนผู้ใช้ต่อ role (นับจาก `model_has_roles`)
ข้อควรระวัง: agency/branch ที่สร้างจากหน้านี้จะไม่มี `agn_id`/`brn_id` ซึ่งความสัมพันธ์ของ `users` ต้องใช้ จึงต้องตั้งค่านี้เองในฐานข้อมูล (หรือใช้ข้อมูลจากคำสั่งซิงก์ที่ตั้งให้อัตโนมัติ)

## Livewire

มี component เดียว `UserPermission` (view `livewire/user-permission`, `livewire/edit-perm`) สำหรับกำหนด permission รายผู้ใช้ ปัจจุบันไม่มี route เข้าถึง เหลือแค่ `pages/perm-table.blade.php` ที่ฝัง component นี้

# เอกสารระบบ HUB Training SSO Server

ระบบนี้เป็นเซิร์ฟเวอร์ Single Sign-On (OAuth2) สำหรับกลุ่มโรงเรียนสอนขับรถ (IDDrives / TrainingZenter) พัฒนาด้วย Laravel 10 + Laravel Passport
ทำหน้าที่ 3 อย่างหลัก:

1. **เป็น Identity Provider** ให้ระบบอื่น (Hub-Training, Check-Before Driving, I-Prompt, KST-Plus) ล็อกอินผ่าน OAuth2
2. **เป็นหน้าจัดการข้อมูล** นักเรียน, เจ้าหน้าที่, หน่วยงาน (agency), สาขา (branch), คอร์ส และสิทธิ์ผ่านเว็บแอดมิน
3. **ดึงข้อมูลนักเรียนอัตโนมัติ** จากระบบภายนอก (DSMS ของแต่ละสาขา และ POS ของ TrainingZenter) ด้วยคำสั่ง artisan

## สารบัญเอกสาร

| ไฟล์ | เนื้อหา | อ่านเมื่อ |
|---|---|---|
| [01-architecture.md](01-architecture.md) | ภาพรวมสถาปัตยกรรม, โครงสร้างโฟลเดอร์, flow การทำงาน | เริ่มทำความเข้าใจระบบ |
| [02-database.md](02-database.md) | ตาราง, ความสัมพันธ์ (ERD), ประวัติ migration | แก้ schema / เขียน query |
| [03-sso-api.md](03-sso-api.md) | ขั้นตอน SSO, scope, endpoint ของ API, อายุ token | เชื่อมระบบอื่นเข้ามา |
| [04-roles-permissions.md](04-roles-permissions.md) | บทบาท, middleware, ตารางสิทธิ์ต่อ route | แก้ระบบสิทธิ์ |
| [05-admin-ui.md](05-admin-ui.md) | หน้าจอและฟังก์ชันของเว็บแอดมิน | แก้หน้าเว็บ / controller |
| [06-data-sync.md](06-data-sync.md) | คำสั่ง artisan, API ภายนอก, การแมปข้อมูล | แก้ / เพิ่มการซิงก์ข้อมูล |
| [07-setup-operations.md](07-setup-operations.md) | การติดตั้ง, ค่า env, การดูแลระบบ | ติดตั้งหรือ deploy |
| [08-known-issues.md](08-known-issues.md) | ปัญหาที่พบจากการอ่านโค้ด (ความปลอดภัย, ข้อมูล, โค้ดค้าง) | ก่อนแก้ไข / ปรับปรุงระบบ |
| [09-dtms-sync-design.md](09-dtms-sync-design.md) | ร่างการออกแบบซิงก์ข้อมูลจาก DTMS (แทน EM) | วางแผน / พัฒนาการซิงก์ |
| [10-dtms-sync-contract.md](10-dtms-sync-contract.md) | ร่างสัญญา API ระหว่าง DTMS กับ SSO | พัฒนาฝั่ง DTMS หรือ SSO |
| [11-new-sso-design.md](11-new-sso-design.md) | ร่างการออกแบบ SSO ใหม่แบบหลายโรงเรียน และแผนย้ายจากระบบเดิม | วางแผนยกเครื่องระบบ |
| [12-kst-hub-sso-notes.md](12-kst-hub-sso-notes.md) | สิ่งที่ KST Hub ใช้จาก SSO และข้อเสนอที่ควรแก้ (อ่านอย่างเดียว ไม่ได้แก้ KST Hub) | แก้ KST Hub / ออกแบบ `/api/user` |
| [13-phase1-legacy-dtms-bridge.md](13-phase1-legacy-dtms-bridge.md) | Phase 1: ต่อ SSO เดิมเข้ากับ DTMS ของ IDDrives ก่อนทำ SSO ใหม่ | **งานถัดไป** เริ่มอ่านที่นี่ |
| [14-deploy-phase1.md](14-deploy-phase1.md) | ขั้นตอน deploy Phase 1 บนเซิร์ฟเวอร์ที่รันอยู่ พร้อมตรวจผลและ rollback | ก่อน deploy |

ไฟล์ 01–08 อธิบาย **ระบบเดิม** ส่วน 09–12 เป็น **ร่างของระบบใหม่และระบบที่เชื่อมด้วย** ที่จะย้ายไป repo ใหม่เมื่อสร้างแล้ว
ไฟล์ 13 เป็นแผนที่ตกลงแล้วสำหรับ **ระบบเดิมใน repo นี้** งานที่แตกออกมาอยู่ใน `tasks/`:
[sso-phase1-tasks.md](tasks/sso-phase1-tasks.md) (ทำใน repo นี้) และ [dtms-phase1-sso-feed.md](tasks/dtms-phase1-sso-feed.md) (ส่งให้ทีม DTMS)

## หมายเหตุเรื่องความถูกต้อง

เอกสารชุดนี้เขียนจากการอ่านซอร์สโค้ดเท่านั้น **ยังไม่ได้รันระบบจริง** (ไม่มีโฟลเดอร์ `vendor/` และไม่มีฐานข้อมูลใน environment ที่วิเคราะห์)
ส่วนใดที่เป็นการอนุมานจากโค้ดและยังไม่ได้ทดสอบ จะระบุว่า "ต้องตรวจสอบ" ไว้ใน [08-known-issues.md](08-known-issues.md)

คู่มือผู้ใช้ฉบับ PDF (ของเดิม) อยู่ที่ `public/ssohub_User_Manual.pdf` และมีลิงก์ในเมนูผู้ใช้ของเว็บ

-- รันไฟล์นี้หนึ่งครั้งกับฐานข้อมูลเดิม ก่อนนำโค้ดรองรับ 3 รูปขึ้นใช้งาน
-- คำสั่งเป็นแบบ idempotent จึงรันซ้ำได้โดยไม่ลบรูปเดิมในคอลัมน์ image
ALTER TABLE `rooms`
  ADD COLUMN IF NOT EXISTS `image_2` varchar(255) DEFAULT NULL AFTER `image`,
  ADD COLUMN IF NOT EXISTS `image_3` varchar(255) DEFAULT NULL AFTER `image_2`;

-- Rollback (ทำเมื่อยืนยันแล้วว่าไม่ต้องใช้รูปที่ 2 และ 3 เท่านั้น เพราะข้อมูลจะถูกลบ):
-- ALTER TABLE `rooms` DROP COLUMN `image_3`, DROP COLUMN `image_2`;

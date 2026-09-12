<?php
require_once '../includes/admin_auth_check.php';
require_once '../config/db_connect.php';

$total_rooms    = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$available      = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn();
$occupied       = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าหลักผู้ดูแลระบบ</title>
</head>
<body>
    <h2>สวัสดีคุณ <?= htmlspecialchars($_SESSION['full_name']) ?> (ผู้ดูแลระบบ)</h2>

    <div style="border:1px solid #ccc; padding:10px; margin-bottom:15px;">
        <p>ห้องพักทั้งหมด: <strong><?= $total_rooms ?></strong> ห้อง</p>
        <p>ว่าง: <strong><?= $available ?></strong> ห้อง | ไม่ว่าง: <strong><?= $occupied ?></strong> ห้อง</p>
        <p>การจองทั้งหมด: <strong><?= $total_bookings ?></strong> รายการ</p>
    </div>

    <nav>
        <a href="manage_rooms.php">จัดการห้องพัก</a> |
        <a href="manage_staff.php">จัดการพนักงาน</a> |
        <a href="reports.php">รายงานสรุป</a> |
        <a href="../staff/pending_payments.php">ตรวจสอบสลิป</a> |
        <a href="../customer/logout.php">ออกจากระบบ</a>
    </nav>
</body>
</html>
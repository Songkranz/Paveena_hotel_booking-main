<?php
require_once '../includes/staff_auth_check.php';
require_once '../config/db_connect.php';

// นับจำนวนรายการที่รอตรวจสอบ ไว้โชว์เป็นตัวเลขแจ้งเตือน
$stmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending'");
$pending_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าหลักพนักงาน</title>
</head>
<body>
    <h2>สวัสดีคุณ <?= htmlspecialchars($_SESSION['full_name']) ?> 
        (<?= $_SESSION['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 'พนักงาน' ?>)
    </h2>

    <nav>
        <a href="pending_payments.php">รายการรอตรวจสอบสลิป (<?= $pending_count ?>)</a> |
        <a href="manage_bookings.php">จัดการเช็คอิน/เช็คเอาต์</a> |
        <a href="../customer/logout.php">ออกจากระบบ</a>
    </nav>
</body>
</html>
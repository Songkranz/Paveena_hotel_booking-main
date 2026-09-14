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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>หน้าหลักผู้ดูแลระบบ</title>
    <link rel="stylesheet" href="../assets/backoffice.css">
</head>
<body class="backoffice-body">
    <header class="bo-header">
        <div class="bo-header-inner">
            <a class="bo-brand" href="dashboard.php">PAVEENA HOTEL · ADMIN</a>
            <nav class="bo-nav" aria-label="เมนูผู้ดูแลระบบ">
                <a href="dashboard.php" aria-current="page">หน้าหลัก</a>
                <a href="manage_rooms.php">ห้องพัก</a>
                <a href="manage_staff.php">พนักงาน</a>
                <a href="reports.php">รายงาน</a>
                <a href="../customer/logout.php">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="bo-main">
        <header class="bo-page-heading">
            <div>
                <h1>ภาพรวมระบบ</h1>
                <p>สวัสดีคุณ <?= htmlspecialchars($_SESSION['full_name']) ?> · ผู้ดูแลระบบ</p>
            </div>
        </header>

        <dl class="bo-stats" aria-label="ข้อมูลสรุป">
            <div><dt>ห้องพักทั้งหมด</dt><dd><?= number_format($total_rooms) ?></dd></div>
            <div><dt>ห้องว่าง</dt><dd><?= number_format($available) ?></dd></div>
            <div><dt>ห้องไม่ว่าง</dt><dd><?= number_format($occupied) ?></dd></div>
            <div><dt>การจองทั้งหมด</dt><dd><?= number_format($total_bookings) ?></dd></div>
        </dl>

        <section class="bo-section" aria-labelledby="admin-menu-title">
            <h2 id="admin-menu-title">เมนูจัดการ</h2>
            <div class="bo-menu">
                <a href="manage_rooms.php"><strong>จัดการห้องพัก</strong><span>เพิ่ม แก้ไข และเปลี่ยนสถานะห้อง</span></a>
                <a href="manage_staff.php"><strong>จัดการพนักงาน</strong><span>เพิ่มหรือลบบัญชีพนักงาน</span></a>
                <a href="../staff/pending_payments.php"><strong>ตรวจสอบสลิป</strong><span>อนุมัติหรือปฏิเสธหลักฐานการชำระเงิน</span></a>
                <a href="../staff/manage_bookings.php"><strong>จัดการการเข้าพัก</strong><span>บันทึกการเช็คอินและเช็คเอาต์</span></a>
                <a href="reports.php"><strong>รายงานสรุป</strong><span>ตรวจสอบรายได้และประวัติการจอง</span></a>
            </div>
        </section>
    </main>
</body>
</html>

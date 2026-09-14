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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>หน้าหลักพนักงาน</title>
    <link rel="stylesheet" href="../assets/backoffice.css">
</head>
<body class="backoffice-body">
    <header class="bo-header">
        <div class="bo-header-inner">
            <a class="bo-brand" href="dashboard.php">PAVEENA HOTEL · STAFF</a>
            <nav class="bo-nav" aria-label="เมนูพนักงาน">
                <a href="dashboard.php" aria-current="page">หน้าหลัก</a>
                <a href="pending_payments.php">ตรวจสอบสลิป</a>
                <a href="manage_bookings.php">การเข้าพัก</a>
                <a href="../customer/logout.php">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="bo-main">
        <header class="bo-page-heading">
            <div>
                <h1>งานประจำวัน</h1>
                <p>สวัสดีคุณ <?= htmlspecialchars($_SESSION['full_name']) ?> · <?= $_SESSION['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 'พนักงาน' ?></p>
            </div>
        </header>

        <dl class="bo-stats bo-stats-single" aria-label="รายการที่ต้องดำเนินการ">
            <div><dt>สลิปรอตรวจสอบ</dt><dd><?= number_format($pending_count) ?></dd></div>
        </dl>

        <section class="bo-section" aria-labelledby="staff-menu-title">
            <h2 id="staff-menu-title">เมนูปฏิบัติงาน</h2>
            <div class="bo-menu">
                <a href="pending_payments.php"><strong>ตรวจสอบสลิป</strong><span><?= number_format($pending_count) ?> รายการรอดำเนินการ</span></a>
                <a href="manage_bookings.php"><strong>เช็คอิน / เช็คเอาต์</strong><span>จัดการสถานะการเข้าพักของลูกค้า</span></a>
            </div>
        </section>
    </main>
</body>
</html>

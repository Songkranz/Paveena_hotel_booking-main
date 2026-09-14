<?php
require_once '../includes/staff_auth_check.php';
require_once '../config/db_connect.php';

$stmt = $pdo->query("SELECT bookings.id, bookings.checkin_date, bookings.checkout_date, bookings.status,
                             users.full_name, rooms.room_number, room_types.type_name
                      FROM bookings
                      JOIN users ON bookings.user_id = users.id
                      JOIN rooms ON bookings.room_id = rooms.id
                      JOIN room_types ON rooms.room_type_id = room_types.id
                      WHERE bookings.status IN ('confirmed', 'checked_in')
                      ORDER BY bookings.checkin_date ASC");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_thai = ['confirmed' => 'รอเช็คอิน', 'checked_in' => 'เข้าพักอยู่'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการการเข้าพัก</title>
    <link rel="stylesheet" href="../assets/backoffice.css">
</head>
<body class="backoffice-body">
    <header class="bo-header">
        <div class="bo-header-inner">
            <a class="bo-brand" href="<?= $_SESSION['role'] === 'admin' ? '../admin/dashboard.php' : 'dashboard.php' ?>">PAVEENA HOTEL · STAFF</a>
            <nav class="bo-nav" aria-label="เมนูพนักงาน"><a href="<?= $_SESSION['role'] === 'admin' ? '../admin/dashboard.php' : 'dashboard.php' ?>">หน้าหลัก</a><a href="pending_payments.php">ตรวจสอบสลิป</a><a href="manage_bookings.php" aria-current="page">การเข้าพัก</a><a href="../customer/logout.php">ออกจากระบบ</a></nav>
        </div>
    </header>
    <main class="bo-main">
        <header class="bo-page-heading">
            <div><h1>เช็คอิน / เช็คเอาต์</h1><p>รายการที่ยืนยันแล้วและลูกค้าที่กำลังเข้าพัก</p></div>
            <span class="bo-status"><?= count($bookings) ?> รายการ</span>
        </header>

        <?php if (!$bookings): ?>
            <p class="bo-empty">ไม่มีรายการที่ต้องเช็คอินหรือเช็คเอาต์ในขณะนี้</p>
        <?php else: ?>
            <section class="bo-card-list" aria-label="รายการการเข้าพัก">
            <?php foreach ($bookings as $b): ?>
                <article class="bo-card">
                    <h2>ห้อง <?= htmlspecialchars($b['room_number']) ?></h2>
                    <dl class="bo-details">
                        <div><dt>ลูกค้า</dt><dd><?= htmlspecialchars($b['full_name']) ?></dd></div>
                        <div><dt>ประเภทห้อง</dt><dd><?= htmlspecialchars($b['type_name']) ?></dd></div>
                        <div><dt>ระยะเวลาเข้าพัก</dt><dd><?= htmlspecialchars($b['checkin_date']) ?> ถึง <?= htmlspecialchars($b['checkout_date']) ?></dd></div>
                        <div><dt>สถานะ</dt><dd><span class="bo-status"><?= htmlspecialchars($status_thai[$b['status']]) ?></span></dd></div>
                    </dl>
                    <form class="bo-actions" method="POST" action="update_stay_status.php">
                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                        <?php if ($b['status'] === 'confirmed'): ?>
                            <button class="bo-button" type="submit" name="new_status" value="checked_in">ยืนยันเช็คอิน</button>
                        <?php elseif ($b['status'] === 'checked_in'): ?>
                            <button class="bo-button" type="submit" name="new_status" value="checked_out">ยืนยันเช็คเอาต์</button>
                        <?php endif; ?>
                    </form>
                </article>
            <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>

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
    <title>จัดการการเข้าพัก</title>
</head>
<body>
    <a href="<?= $_SESSION['role'] === 'admin' ? '../admin/dashboard.php' : 'dashboard.php' ?>">&laquo; กลับหน้าหลัก</a>
    <h2>รายการเช็คอิน/เช็คเอาต์</h2>

    <?php foreach ($bookings as $b): ?>
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
            <p>ลูกค้า: <?= htmlspecialchars($b['full_name']) ?></p>
            <p>ห้อง <?= htmlspecialchars($b['room_number']) ?> — <?= htmlspecialchars($b['type_name']) ?></p>
            <p><?= htmlspecialchars($b['checkin_date']) ?> ถึง <?= htmlspecialchars($b['checkout_date']) ?></p>
            <p>สถานะ: <strong><?= $status_thai[$b['status']] ?></strong></p>

            <form method="POST" action="update_stay_status.php">
                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                <?php if ($b['status'] === 'confirmed'): ?>
                    <button type="submit" name="new_status" value="checked_in">เช็คอิน</button>
                <?php elseif ($b['status'] === 'checked_in'): ?>
                    <button type="submit" name="new_status" value="checked_out">เช็คเอาต์</button>
                <?php endif; ?>
            </form>
        </div>
    <?php endforeach; ?>
</body>
</html>
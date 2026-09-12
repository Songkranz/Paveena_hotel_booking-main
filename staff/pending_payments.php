<?php
require_once '../includes/staff_auth_check.php';
require_once '../config/db_connect.php';

$stmt = $pdo->query("SELECT payments.id AS payment_id, payments.slip_image, payments.amount,
                             bookings.id AS booking_id, bookings.checkin_date, bookings.checkout_date,
                             users.full_name, rooms.room_number, room_types.type_name
                      FROM payments
                      JOIN bookings ON payments.booking_id = bookings.id
                      JOIN users ON bookings.user_id = users.id
                      JOIN rooms ON bookings.room_id = rooms.id
                      JOIN room_types ON rooms.room_type_id = room_types.id
                      WHERE payments.payment_status = 'pending'
                      ORDER BY payments.paid_at ASC");
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการรอตรวจสอบ</title>
</head>

<?php
// ... โค้ดดึงข้อมูล $payments เดิมที่มีอยู่ ...

// เพิ่มโค้ดดึงบริการเสริมตรงนี้
$svc_map = [];
if (count($payments) > 0) {
    $ids = array_column($payments, 'booking_id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $s   = $pdo->prepare("SELECT booking_id, service_name, price FROM booking_services WHERE booking_id IN ($in)");
    $s->execute($ids);
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $svc_map[$row['booking_id']][] = $row;
    }
}
?>

<body>
    <a href="<?= $_SESSION['role'] === 'admin' ? '../admin/dashboard.php' : 'dashboard.php' ?>">&laquo; กลับหน้าหลัก</a>
    <h2>รายการรอตรวจสอบสลิป (<?= count($payments) ?> รายการ)</h2>

    <?php if (count($payments) === 0): ?>
        <p>ไม่มีรายการรอตรวจสอบ</p>
    <?php endif; ?>

    <?php foreach ($payments as $p): ?>
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
            <p>ลูกค้า: <?= htmlspecialchars($p['full_name']) ?></p>
            <p>ห้อง <?= htmlspecialchars($p['room_number']) ?> — <?= htmlspecialchars($p['type_name']) ?></p>
            <p><?= htmlspecialchars($p['checkin_date']) ?> ถึง <?= htmlspecialchars($p['checkout_date']) ?></p>
            <p>ยอดโอน: <?= number_format($p['amount'], 2) ?> บาท</p>
            <img src="../assets/uploads/slips/<?= htmlspecialchars($p['slip_image']) ?>" width="250"><br><br>

            <?php if (!empty($svc_map[$p['booking_id']])): ?>
            <p style="margin:4px 0;">บริการเสริม:</p>
            <ul style="margin:4px 0;">
            <?php foreach ($svc_map [$p['booking_id']] as $s): ?>
            <li><?= htmlspecialchars($s['service_name']) ?> — <?= number_format($s['price'], 2) ?> บาท</li>
             <?php endforeach; ?>
            </ul>
            <?php endif; ?>   


            <form method="POST" action="verify_payment.php" style="display:inline;">
                <input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>">
                <input type="hidden" name="booking_id" value="<?= $p['booking_id'] ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit">✅ อนุมัติ</button>
            </form>

            <form method="POST" action="verify_payment.php" style="display:inline;">
                <input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>">
                <input type="hidden" name="booking_id" value="<?= $p['booking_id'] ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit">❌ ปฏิเสธ</button>
            </form>
        </div>
    <?php endforeach; ?>
</body>
</html>
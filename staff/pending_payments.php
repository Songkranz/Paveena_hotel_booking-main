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
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รายการรอตรวจสอบ</title>
    <link rel="stylesheet" href="../assets/backoffice.css">
</head>
<body class="backoffice-body">
    <header class="bo-header">
        <div class="bo-header-inner">
            <a class="bo-brand" href="<?= $_SESSION['role'] === 'admin' ? '../admin/dashboard.php' : 'dashboard.php' ?>">PAVEENA HOTEL · STAFF</a>
            <nav class="bo-nav" aria-label="เมนูพนักงาน"><a href="<?= $_SESSION['role'] === 'admin' ? '../admin/dashboard.php' : 'dashboard.php' ?>">หน้าหลัก</a><a href="pending_payments.php" aria-current="page">ตรวจสอบสลิป</a><a href="manage_bookings.php">การเข้าพัก</a><a href="../customer/logout.php">ออกจากระบบ</a></nav>
        </div>
    </header>
    <main class="bo-main">
        <header class="bo-page-heading">
            <div><h1>ตรวจสอบสลิป</h1><p>ตรวจสอบข้อมูลการจองและหลักฐานก่อนอนุมัติการชำระเงิน</p></div>
            <span class="bo-status"><?= count($payments) ?> รายการ</span>
        </header>

        <?php if (count($payments) === 0): ?>
            <p class="bo-empty">ไม่มีรายการรอตรวจสอบ</p>
        <?php else: ?>
            <section class="bo-card-list" aria-label="รายการสลิปรอตรวจสอบ">
            <?php foreach ($payments as $p): ?>
                <article class="bo-card">
                    <h2>การจองเลขที่ <?= number_format($p['booking_id']) ?> · ห้อง <?= htmlspecialchars($p['room_number']) ?></h2>
                    <dl class="bo-details">
                        <div><dt>ลูกค้า</dt><dd><?= htmlspecialchars($p['full_name']) ?></dd></div>
                        <div><dt>ประเภทห้อง</dt><dd><?= htmlspecialchars($p['type_name']) ?></dd></div>
                        <div><dt>วันเข้าพัก</dt><dd><?= htmlspecialchars($p['checkin_date']) ?> ถึง <?= htmlspecialchars($p['checkout_date']) ?></dd></div>
                        <div><dt>ยอดโอน</dt><dd><?= number_format($p['amount'], 2) ?> บาท</dd></div>
                    </dl>
                    <img class="bo-slip" src="../assets/uploads/slips/<?= htmlspecialchars($p['slip_image']) ?>" alt="สลิปการชำระเงินของ <?= htmlspecialchars($p['full_name']) ?>">

                    <?php if (!empty($svc_map[$p['booking_id']])): ?>
                        <h3>บริการเสริม</h3>
                        <ul>
                        <?php foreach ($svc_map[$p['booking_id']] as $s): ?>
                            <li><?= htmlspecialchars($s['service_name']) ?> — <?= number_format($s['price'], 2) ?> บาท</li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <div class="bo-actions">
                        <form method="POST" action="verify_payment.php">
                            <input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>"><input type="hidden" name="booking_id" value="<?= $p['booking_id'] ?>"><input type="hidden" name="action" value="approve">
                            <button class="bo-button" type="submit">อนุมัติการชำระเงิน</button>
                        </form>
                        <form method="POST" action="verify_payment.php">
                            <input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>"><input type="hidden" name="booking_id" value="<?= $p['booking_id'] ?>"><input type="hidden" name="action" value="reject">
                            <button class="bo-button bo-button-danger" type="submit">ปฏิเสธ</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>

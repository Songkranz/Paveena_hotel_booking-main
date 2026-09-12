<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';

$user_id = $_SESSION['user_id'];

// 1. ดึงข้อมูลการจองทั้งหมดของผู้ใช้
$stmt = $pdo->prepare("SELECT bookings.*, rooms.room_number, room_types.type_name, payments.payment_status
                       FROM bookings
                       JOIN rooms ON bookings.room_id = rooms.id
                       JOIN room_types ON rooms.room_type_id = room_types.id
                       LEFT JOIN payments ON payments.id = (
                           SELECT p2.id FROM payments p2
                           WHERE p2.booking_id = bookings.id
                           ORDER BY p2.id DESC LIMIT 1
                       )
                       WHERE bookings.user_id = ?
                       ORDER BY bookings.created_at DESC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. ดึงบริการเสริมของทุกการจอง
$svc_map = [];
if (count($bookings) > 0) {
    $ids = array_column($bookings, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $s   = $pdo->prepare("SELECT booking_id, service_name, price FROM booking_services WHERE booking_id IN ($in)");
    $s->execute($ids);
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $svc_map[$row['booking_id']][] = $row;
    }
}

$status_thai = [
    'pending'     => 'รอตรวจสอบ',
    'confirmed'   => 'ยืนยันแล้ว',
    'checked_in'  => 'เช็คอินแล้ว',
    'checked_out' => 'เช็คเอาต์แล้ว',
    'cancelled'   => 'ยกเลิก'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>การจองของฉัน</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .card { border:1px solid #ccc; padding:15px; margin-bottom:15px; max-width:500px; border-radius:5px; }
    </style>
</head>
<body>
    <a href="dashboard.php">&laquo; กลับหน้าหลัก</a>
    <h2>รายการจองของฉัน</h2>

    <?php if (isset($_GET['uploaded'])): ?>
        <p style="color:green;">แนบสลิปสำเร็จ รอพนักงานตรวจสอบ</p>
    <?php endif; ?>
    <?php if (isset($_GET['cancelled'])): ?>
        <p style="color:green;">ยกเลิกการจองเรียบร้อยแล้ว</p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p style="color:red;">ไม่สามารถยกเลิกการจองนี้ได้</p>
    <?php endif; ?>

    <?php if (count($bookings) === 0): ?>
        <p>ยังไม่มีรายการจอง</p>
    <?php endif; ?>

    <?php foreach ($bookings as $b): ?>
        <div class="card">
            <p><strong>ห้อง <?= htmlspecialchars($b['room_number']) ?></strong> — <?= htmlspecialchars($b['type_name']) ?></p>
            <p><?= htmlspecialchars($b['checkin_date']) ?> ถึง <?= htmlspecialchars($b['checkout_date']) ?></p>
            <p>ราคารวม: <?= number_format($b['total_price'], 2) ?> บาท</p>

            <!-- แสดงบริการเสริม -->
            <?php if (!empty($svc_map[$b['id']])): ?>
                <p style="margin:4px 0; font-size: 14px;">บริการเสริม:</p>
                <ul style="margin:4px 0; font-size: 14px;">
                    <?php foreach ($svc_map[$b['id']] as $s): ?>
                        <li><?= htmlspecialchars($s['service_name']) ?> — <?= number_format($s['price'], 2) ?> บาท</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <p>สถานะการจอง: <strong><?= $status_thai[$b['status']] ?></strong>
                <?php if ($b['status'] === 'cancelled' && $b['payment_status'] === 'verified'): ?>
                    <span style="color:#c00;">(ชำระเงินแล้ว — ไม่มีการคืนเงิน)</span>
                <?php endif; ?>
            </p>

            <!-- ลิงก์แนบสลิป (เฉพาะสถานะ pending และไม่ใช่ cancelled) -->
            <?php if ($b['status'] !== 'cancelled'): ?>
                <?php if ($b['payment_status'] === 'verified'): ?>
                    <p style="color:green;">สถานะการชำระเงิน: ตรวจสอบแล้ว</p>
                <?php elseif ($b['payment_status'] === 'rejected'): ?>
                    <p style="color:red;">สถานะการชำระเงิน: ถูกปฏิเสธ <a href="upload_slip.php?booking_id=<?= $b['id'] ?>">แนบสลิปใหม่</a></p>
                <?php elseif (!$b['payment_status']): ?>
                    <p><a href="upload_slip.php?booking_id=<?= $b['id'] ?>">แนบสลิปโอนเงิน</a></p>
                <?php else: ?>
                    <p>สถานะการชำระเงิน: รอตรวจสอบ</p>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ปุ่มยกเลิกการจอง -->
            <?php if (in_array($b['status'], ['pending', 'confirmed'])): ?>
                <?php
                $warn = ($b['payment_status'] === 'verified')
                    ? 'การจองนี้ชำระเงินแล้ว หากยกเลิกจะไม่ได้รับเงินคืนตามนโยบายของโรงแรม ยืนยันหรือไม่?'
                    : 'ยืนยันยกเลิกการจองห้อง ' . $b['room_number'] . ' ?';
                ?>
                <form method="POST" action="cancel_booking.php"
                      onsubmit="return confirm('<?= htmlspecialchars($warn, ENT_QUOTES) ?>');">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <button type="submit" style="color:red; cursor:pointer;">ยกเลิกการจอง</button>
                </form>
                <p style="font-size:12px; color:#888;">* ยกเลิกแล้วไม่สามารถขอคืนเงินได้ทุกกรณี</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</body>
</html>
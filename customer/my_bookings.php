<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';

$user_id = $_SESSION['user_id'];

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

$svc_map = [];
if (count($bookings) > 0) {
    $ids = array_column($bookings, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $s = $pdo->prepare("SELECT booking_id, service_name, price FROM booking_services WHERE booking_id IN ($in)");
    $s->execute($ids);
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $svc_map[$row['booking_id']][] = $row;
    }
}

$status_thai = [
    'pending' => 'รอตรวจสอบ',
    'confirmed' => 'ยืนยันแล้ว',
    'checked_in' => 'เช็คอินแล้ว',
    'checked_out' => 'เช็คเอาต์แล้ว',
    'cancelled' => 'ยกเลิก'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>การจองของฉัน — Paveena Hotel</title>
    <link rel="stylesheet" href="../assets/site.css">
</head>
<body>
<div class="page-shell">
    <header class="site-header">
        <div class="header-top">
            <a class="brand" href="../index.php">
                <span class="brand-mark">PH</span>
                <span>PAVEENA HOTEL</span>
            </a>
            <div class="header-account">
                <span>สวัสดีคุณ <?= htmlspecialchars($_SESSION['full_name']) ?></span>
                <a class="button" href="dashboard.php">บัญชีของฉัน</a>
                <a href="logout.php">ออกจากระบบ</a>
            </div>
        </div>
        <nav class="site-nav" aria-label="เมนูหลัก">
            <a href="../index.php">หน้าแรก</a>
            <a href="search_rooms.php">ค้นหาห้อง</a>
            <a class="active" href="my_bookings.php">การจองของฉัน</a>
        </nav>
    </header>

    <main class="content-page bookings-page">
        <a class="back-link" href="dashboard.php">← กลับหน้าบัญชี</a>
        <div class="content-heading">
            <div>
                <p class="eyebrow">Your reservations</p>
                <h1>การจองของฉัน</h1>
                <p>ติดตามสถานะการเข้าพักและการชำระเงินของคุณ</p>
            </div>
            <a class="button button-aqua" href="search_rooms.php">จองห้องเพิ่ม</a>
        </div>

        <?php if (isset($_GET['uploaded'])): ?>
            <div class="notice notice-success">แนบสลิปสำเร็จ รอพนักงานตรวจสอบ</div>
        <?php endif; ?>
        <?php if (isset($_GET['cancelled'])): ?>
            <div class="notice notice-success">ยกเลิกการจองเรียบร้อยแล้ว</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="notice notice-error">ไม่สามารถยกเลิกการจองนี้ได้</div>
        <?php endif; ?>

        <?php if (count($bookings) === 0): ?>
            <div class="empty-state bookings-empty">
                <span class="empty-state-mark">PH</span>
                <h2>ยังไม่มีรายการจอง</h2>
                <p>ค้นหาห้องพักที่เหมาะกับคุณ แล้วเริ่มต้นวันพักผ่อนได้เลย</p>
                <a class="button button-aqua" href="search_rooms.php">ค้นหาห้องพัก</a>
            </div>
        <?php else: ?>
            <div class="bookings-list">
                <?php foreach ($bookings as $b): ?>
                    <article class="booking-card">
                        <div class="booking-card-header">
                            <div>
                                <p class="eyebrow">Booking #<?= (int)$b['id'] ?></p>
                                <h2>ห้อง <?= htmlspecialchars($b['room_number']) ?></h2>
                                <p><?= htmlspecialchars($b['type_name']) ?></p>
                            </div>
                            <span class="status-badge status-<?= htmlspecialchars($b['status']) ?>">
                                <?= htmlspecialchars($status_thai[$b['status']] ?? $b['status']) ?>
                            </span>
                        </div>

                        <div class="booking-info-grid">
                            <div>
                                <span>วันเข้าพัก</span>
                                <strong><?= date('d/m/Y', strtotime($b['checkin_date'])) ?></strong>
                            </div>
                            <div>
                                <span>วันออก</span>
                                <strong><?= date('d/m/Y', strtotime($b['checkout_date'])) ?></strong>
                            </div>
                            <div>
                                <span>ราคารวม</span>
                                <strong><?= number_format($b['total_price'], 2) ?> บาท</strong>
                            </div>
                        </div>

                        <?php if (!empty($svc_map[$b['id']])): ?>
                            <div class="booking-services">
                                <strong>บริการเสริม</strong>
                                <ul>
                                    <?php foreach ($svc_map[$b['id']] as $service): ?>
                                        <li>
                                            <span><?= htmlspecialchars($service['service_name']) ?></span>
                                            <span><?= number_format($service['price'], 2) ?> บาท</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($b['status'] === 'cancelled' && $b['payment_status'] === 'verified'): ?>
                            <div class="notice notice-warning">รายการนี้ชำระเงินแล้วและถูกยกเลิก — ไม่มีการคืนเงินตามนโยบาย</div>
                        <?php endif; ?>

                        <div class="payment-panel">
                            <div>
                                <span>สถานะการชำระเงิน</span>
                                <?php if ($b['payment_status'] === 'verified'): ?>
                                    <strong class="payment-success">ตรวจสอบแล้ว</strong>
                                <?php elseif ($b['payment_status'] === 'rejected'): ?>
                                    <strong class="payment-error">สลิปถูกปฏิเสธ</strong>
                                <?php elseif (!$b['payment_status']): ?>
                                    <strong>ยังไม่ได้แนบสลิป</strong>
                                <?php else: ?>
                                    <strong class="payment-pending">รอตรวจสอบ</strong>
                                <?php endif; ?>
                            </div>
                            <?php if ($b['status'] !== 'cancelled' && ($b['payment_status'] === 'rejected' || !$b['payment_status'])): ?>
                                <a class="button button-aqua" href="upload_slip.php?booking_id=<?= (int)$b['id'] ?>">
                                    <?= $b['payment_status'] === 'rejected' ? 'แนบสลิปใหม่' : 'แนบสลิปโอนเงิน' ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if (in_array($b['status'], ['pending', 'confirmed'])): ?>
                            <?php
                            $warn = ($b['payment_status'] === 'verified')
                                ? 'การจองนี้ชำระเงินแล้ว หากยกเลิกจะไม่ได้รับเงินคืนตามนโยบายของโรงแรม ยืนยันหรือไม่?'
                                : 'ยืนยันยกเลิกการจองห้อง ' . $b['room_number'] . ' ?';
                            ?>
                            <div class="booking-card-footer">
                                <p>* ยกเลิกแล้วไม่สามารถขอคืนเงินได้ทุกกรณี</p>
                                <form method="POST" action="cancel_booking.php"
                                      onsubmit="return confirm('<?= htmlspecialchars($warn, ENT_QUOTES) ?>');">
                                    <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                    <button class="button button-danger" type="submit">ยกเลิกการจอง</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <strong>PAVEENA HOTEL</strong>
        <span>การพักผ่อนที่เรียบง่ายและน่าจดจำ</span>
    </footer>
</div>
</body>
</html>

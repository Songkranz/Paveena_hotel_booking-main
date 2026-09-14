<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/services.php';

if (!isset($SERVICES) && isset($services) && is_array($services)) {
    $SERVICES = $services;
}
if (!isset($SERVICES) || !is_array($SERVICES)) {
    $SERVICES = [];
}

$room_id  = $_POST['room_id'];
$checkin  = $_POST['checkin_date'];
$checkout = $_POST['checkout_date'];

$stmt = $pdo->prepare("SELECT rooms.room_number, rooms.image, rooms.image_2, rooms.image_3,
                              room_types.type_name, room_types.price_per_night
                       FROM rooms
                       JOIN room_types ON rooms.room_type_id = room_types.id
                       WHERE rooms.id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

$nights     = (strtotime($checkout) - strtotime($checkin)) / 86400;
$room_total = $nights * $room['price_per_night'];
$room_image = $room['image'] ?: ($room['image_2'] ?: $room['image_3']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ยืนยันการจอง — Paveena Hotel</title>
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
            <a class="active" href="search_rooms.php">ค้นหาห้อง</a>
            <a href="my_bookings.php">การจองของฉัน</a>
        </nav>
    </header>

    <main class="booking-page">
        <a class="back-link" href="search_rooms.php">← กลับไปค้นหาห้องใหม่</a>
        <div class="booking-page-heading">
            <p class="eyebrow" style="color:var(--aqua);">ตรวจสอบรายละเอียด</p>
            <h1>ยืนยันการจอง</h1>
            <p>เลือกรายการเสริมและตรวจสอบยอดก่อนสร้างการจอง</p>
        </div>

        <form class="reservation-layout" method="POST" action="create_booking.php">
            <input type="hidden" name="room_id" value="<?= htmlspecialchars($room_id) ?>">
            <input type="hidden" name="checkin_date" value="<?= htmlspecialchars($checkin) ?>">
            <input type="hidden" name="checkout_date" value="<?= htmlspecialchars($checkout) ?>">

            <div class="reservation-main">
                <section class="booking-room-overview">
                    <?php if ($room_image): ?>
                        <img class="booking-room-photo"
                             src="../assets/uploads/rooms/<?= htmlspecialchars($room_image) ?>"
                             alt="ห้อง <?= htmlspecialchars($room['room_number']) ?>">
                    <?php else: ?>
                        <div class="booking-room-photo-placeholder">PAVEENA HOTEL</div>
                    <?php endif; ?>
                    <div class="booking-room-copy">
                        <p class="eyebrow" style="color:var(--aqua);">ห้องหมายเลข <?= htmlspecialchars($room['room_number']) ?></p>
                        <h2><?= htmlspecialchars($room['type_name']) ?></h2>
                        <div class="stay-details">
                            <div class="stay-detail">
                                <span>วันที่เข้าพัก</span>
                                <strong><?= date('d/m/Y', strtotime($checkin)) ?></strong>
                            </div>
                            <div class="stay-detail">
                                <span>วันที่ออก</span>
                                <strong><?= date('d/m/Y', strtotime($checkout)) ?></strong>
                            </div>
                            <div class="stay-detail">
                                <span>จำนวนคืน</span>
                                <strong><?= (int)$nights ?> คืน</strong>
                            </div>
                            <div class="stay-detail">
                                <span>ราคาต่อคืน</span>
                                <strong><?= number_format($room['price_per_night'], 2) ?> บาท</strong>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="booking-section-card">
                    <h2>บริการเสริม</h2>
                    <p>เลือกเฉพาะบริการที่ต้องการ สามารถไม่เลือกได้</p>
                    <div class="service-options">
                        <?php foreach ($SERVICES as $key => $service): ?>
                            <label class="service-option">
                                <input type="checkbox" name="services[]" value="<?= htmlspecialchars($key) ?>"
                                       data-price="<?= $service['price'] ?>" onchange="calcTotal()">
                                <span>
                                    <strong><?= htmlspecialchars($service['name']) ?></strong>
                                    <span>+<?= number_format($service['price'], 2) ?> บาท</span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="terms-box">
                    <h2>เงื่อนไขการจอง</h2>
                    <p>เมื่อชำระเงินและได้รับการยืนยันแล้ว หากยกเลิกภายหลัง โรงแรมขอสงวนสิทธิ์ไม่คืนเงินทุกกรณี</p>
                    <label class="terms-accept">
                        <input type="checkbox" required>
                        <span>ข้าพเจ้ารับทราบและยอมรับเงื่อนไขการจองข้างต้น</span>
                    </label>
                </section>
            </div>

            <aside class="price-summary">
                <p class="eyebrow">สรุปราคา</p>
                <h2><?= htmlspecialchars($room['type_name']) ?></h2>
                <div class="price-line">
                    <span>ค่าห้อง <?= (int)$nights ?> คืน</span>
                    <strong><?= number_format($room_total, 2) ?> บาท</strong>
                </div>
                <div class="price-line">
                    <span>บริการเสริม</span>
                    <strong><span id="svc">0.00</span> บาท</strong>
                </div>
                <div class="price-total">
                    <span>ยอดรวมทั้งสิ้น</span>
                    <strong><span id="grand"><?= number_format($room_total, 2) ?></span><small> บาท</small></strong>
                </div>
                <button class="button" type="submit">ยืนยันการจอง</button>
                <p class="price-note">หลังยืนยัน ระบบจะพาไปหน้าแนบสลิป</p>
            </aside>
        </form>
    </main>

    <footer class="site-footer">
        <span>© <?= date('Y') ?> Paveena Hotel</span>
        <span>ระบบจองห้องพักออนไลน์</span>
    </footer>
</div>

<script>
const roomTotal = <?= $room_total ?>;
function calcTotal() {
    let serviceTotal = 0;
    document.querySelectorAll('input[name="services[]"]:checked')
            .forEach(input => serviceTotal += Number(input.dataset.price));

    const formatPrice = value => value.toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    document.getElementById('svc').textContent = formatPrice(serviceTotal);
    document.getElementById('grand').textContent = formatPrice(roomTotal + serviceTotal);
}
</script>
</body>
</html>

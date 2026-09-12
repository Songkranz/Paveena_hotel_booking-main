<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/services.php'; 

// Support either the expected $SERVICES array name or the lower-case $services
// name used by the included service definitions.
if (!isset($SERVICES) && isset($services) && is_array($services)) {
    $SERVICES = $services;
}
if (!isset($SERVICES) || !is_array($SERVICES)) {
    $SERVICES = [];
}

$room_id  = $_POST['room_id'];
$checkin  = $_POST['checkin_date'];
$checkout = $_POST['checkout_date'];

    $stmt = $pdo->prepare("SELECT rooms.room_number, room_types.type_name, room_types.price_per_night
                       FROM rooms JOIN room_types ON rooms.room_type_id = room_types.id
                       WHERE rooms.id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    $nights     = (strtotime($checkout) - strtotime($checkin)) / 86400;
    $room_total = $nights * $room['price_per_night'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ยืนยันการจอง</title>
    <style>body { font-family: sans-serif; } .box { border:1px solid #ccc; padding:15px; max-width:520px; margin-bottom:15px; }</style>
</head>
<body>
    <h2>สรุปการจอง</h2>

    <div class="box">
        <p>ห้อง <?= htmlspecialchars($room['room_number']) ?> — <?= htmlspecialchars($room['type_name']) ?></p>
        <p>เช็คอิน: <?= htmlspecialchars($checkin) ?> | เช็คเอาต์: <?= htmlspecialchars($checkout) ?></p>
        <p>จำนวน <?= $nights ?> คืน x <?= number_format($room['price_per_night'], 2) ?> บาท
           = <strong><?= number_format($room_total, 2) ?> บาท</strong></p>
    </div>

    <form method="POST" action="create_booking.php">
        <input type="hidden" name="room_id" value="<?= htmlspecialchars($room_id) ?>">
        <input type="hidden" name="checkin_date" value="<?= htmlspecialchars($checkin) ?>">
        <input type="hidden" name="checkout_date" value="<?= htmlspecialchars($checkout) ?>">

        <div class="box">
            <strong>บริการเสริม (ไม่บังคับ)</strong>
            <?php foreach ($SERVICES as $key => $s): ?>
                <p style="margin:8px 0;">
                    <label>
                        <input type="checkbox" name="services[]" value="<?= $key ?>"
                               data-price="<?= $s['price'] ?>" onchange="calcTotal()">
                        <?= htmlspecialchars($s['name']) ?>
                        — <?= number_format($s['price'], 2) ?> บาท
                    </label>
                </p>
            <?php endforeach; ?>
        </div>

        <div class="box" style="border:2px solid #333;">
            <p>ค่าห้องพัก: <?= number_format($room_total, 2) ?> บาท</p>
            <p>บริการเสริม: <span id="svc">0.00</span> บาท</p>
            <h3>ราคารวมทั้งสิ้น: <span id="grand"><?= number_format($room_total, 2) ?></span> บาท</h3>
        </div>

        <div style="border:1px solid #c00; padding:10px; margin:15px 0; background:#fff5f5; max-width:520px;">
            <strong>เงื่อนไขการจอง</strong>
            <p style="margin:5px 0;">เมื่อชำระเงินและได้รับการยืนยันแล้ว
            หากยกเลิกการจองในภายหลัง ทางโรงแรมขอสงวนสิทธิ์ไม่คืนเงินทุกกรณี</p>
            <label><input type="checkbox" required> ข้าพเจ้ารับทราบและยอมรับเงื่อนไขข้างต้น</label>
        </div>

        <button type="submit">ยืนยันการจอง</button>
    </form>

    <p><a href="search_rooms.php">&laquo; กลับไปค้นหาใหม่</a></p>

    <script>
    const roomTotal = <?= $room_total ?>;
    function calcTotal() {
        let svc = 0;
        document.querySelectorAll('input[name="services[]"]:checked')
                .forEach(el => svc += parseFloat(el.dataset.price));
        const fmt = n => n.toLocaleString('th-TH', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('svc').textContent = fmt(svc);
        document.getElementById('grand').textContent = fmt(roomTotal + svc);
    }
    </script>
</body>
</html>
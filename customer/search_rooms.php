<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';

$rooms = [];
$searched = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $checkin = $_POST['checkin_date'];
    $checkout = $_POST['checkout_date'];
    $searched = true;

    // หาห้องที่ "ว่าง" และ "ไม่ชนกับการจองเดิม" ในช่วงวันที่เลือก
    $sql = "SELECT rooms.id, rooms.room_number,rooms.image, room_types.type_name, 
                   room_types.price_per_night, room_types.max_guests, room_types.description
            FROM rooms
            JOIN room_types ON rooms.room_type_id = room_types.id
            WHERE rooms.status = 'available'
            AND rooms.id NOT IN (
                SELECT room_id FROM bookings
                WHERE status IN ('pending', 'confirmed', 'checked_in')
                AND checkin_date < ?
                AND checkout_date > ?
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$checkout, $checkin]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ค้นหาห้องพัก</title>
    <style>
        .room-image { width: 280px; height: 180px; object-fit: cover; border-radius: 6px; }
        .no-image { width: 280px; height: 180px; display: flex; align-items: center;
                    justify-content: center; background: #eee; color: #777; border-radius: 6px; }
    </style>
</head>
<body>
    <a href="dashboard.php">&laquo; กลับหน้าหลัก</a>
    <h2>ค้นหาห้องว่าง</h2>

    <form method="POST">
        วันที่เข้าพัก: <input type="date" name="checkin_date" required value="<?= $_POST['checkin_date'] ?? '' ?>"><br><br>
        วันที่ออก: <input type="date" name="checkout_date" required value="<?= $_POST['checkout_date'] ?? '' ?>"><br><br>
        <button type="submit">ค้นหา</button>
    </form>

    <?php if ($searched): ?>
        <h3>ผลการค้นหา</h3>
        <?php if (count($rooms) === 0): ?>
            <p>ไม่มีห้องว่างในช่วงวันที่เลือก</p>
        <?php else: ?>
            <?php foreach ($rooms as $room): ?>
                <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
                    <?php if ($room['image']): ?>
                        <img src="../assets/uploads/rooms/<?= htmlspecialchars($room['image']) ?>"
                             alt="รูปห้อง <?= htmlspecialchars($room['room_number']) ?>" class="room-image">
                    <?php else: ?>
                        <div class="no-image">ยังไม่มีรูปห้อง</div>
                    <?php endif; ?>
                    <p>ห้อง <?= htmlspecialchars($room['room_number']) ?> — <?= htmlspecialchars($room['type_name']) ?></p>
                    <p>ราคา <?= number_format($room['price_per_night'], 2) ?> บาท/คืน | เข้าพักได้สูงสุด <?= $room['max_guests'] ?> คน</p>
                    <p><?= htmlspecialchars($room['description']) ?></p>
                    <form method="POST" action="book_room.php">
                        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                        <input type="hidden" name="checkin_date" value="<?= htmlspecialchars($_POST['checkin_date']) ?>">
                        <input type="hidden" name="checkout_date" value="<?= htmlspecialchars($_POST['checkout_date']) ?>">
                        <button type="submit">จองห้องนี้</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>

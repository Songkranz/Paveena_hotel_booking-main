<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/services.php';

$user_id  = $_SESSION['user_id'];
$room_id  = $_POST['room_id'];
$checkin  = $_POST['checkin_date'];
$checkout = $_POST['checkout_date'];
$selected = $_POST['services'] ?? [];

// 1. ตรวจสอบว่าห้องยังว่างอยู่จริง (ป้องกันจองซ้ำในเวลาเดียวกัน)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings 
                       WHERE room_id = ? 
                       AND status IN ('pending', 'confirmed', 'checked_in')
                       AND checkin_date < ? AND checkout_date > ?");
$stmt->execute([$room_id, $checkout, $checkin]);
if ($stmt->fetchColumn() > 0) {
    header("Location: search_rooms.php?error=taken");
    exit();
}

// 2. คำนวณราคาห้องพัก
$stmt = $pdo->prepare("SELECT price_per_night FROM room_types 
                       JOIN rooms ON rooms.room_type_id = room_types.id 
                       WHERE rooms.id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

$nights     = (strtotime($checkout) - strtotime($checkin)) / 86400;
$room_total = $nights * $room['price_per_night'];

// 3. รวมราคาบริการเสริม
$service_total = 0;
$valid_services = [];
foreach ($selected as $key) {
    if (isset($SERVICES[$key])) {
        $valid_services[] = $SERVICES[$key];
        $service_total   += $SERVICES[$key]['price'];
    }
}
$total_price = $room_total + $service_total;

// 4. บันทึกการจอง
$stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, checkin_date, checkout_date, total_price, status) 
                       VALUES (?, ?, ?, ?, ?, 'pending')");
$stmt->execute([$user_id, $room_id, $checkin, $checkout, $total_price]);
$booking_id = $pdo->lastInsertId();

// 5. บันทึกบริการเสริม (ถ้ามี)
if (!empty($valid_services)) {
    $stmt = $pdo->prepare("INSERT INTO booking_services (booking_id, service_name, price) VALUES (?, ?, ?)");
    foreach ($valid_services as $s) {
        $stmt->execute([$booking_id, $s['name'], $s['price']]);
    }
}

// 6. ส่งไปหน้าอัปโหลดสลิป
header("Location: upload_slip.php?booking_id=" . $booking_id);
exit();
?>
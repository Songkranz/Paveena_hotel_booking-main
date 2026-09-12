<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';

$booking_id = $_POST['booking_id'];
$user_id    = $_SESSION['user_id'];

// ด่านตรวจ: การจองนี้เป็นของคนที่ล็อกอินอยู่จริงไหม และยังยกเลิกได้ไหม
$stmt = $pdo->prepare("SELECT id, room_id, status FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking || !in_array($booking['status'], ['pending', 'confirmed'])) {
    header("Location: my_bookings.php?error=1");
    exit();
}

// 1) เปลี่ยนสถานะการจองเป็นยกเลิก
$stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
$stmt->execute([$booking_id]);

// 2) คืนห้องให้ว่าง เพื่อขายรอบใหม่ได้ทันที
$stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ? AND status <> 'maintenance'");
$stmt->execute([$booking['room_id']]);

// 3) ตีตกเฉพาะสลิปที่ "ยังไม่ได้ตรวจ" เท่านั้น
//    สลิปที่ verified แล้ว = เก็บเงินไปแล้ว ตามนโยบายไม่คืนเงิน จึงคงสถานะเดิมไว้
$stmt = $pdo->prepare("UPDATE payments SET payment_status = 'rejected'
                       WHERE booking_id = ? AND payment_status = 'pending'");
$stmt->execute([$booking_id]);

header("Location: my_bookings.php?cancelled=1");
exit();
?>
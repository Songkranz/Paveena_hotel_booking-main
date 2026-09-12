<?php
require_once '../includes/staff_auth_check.php';
require_once '../config/db_connect.php';

$payment_id = $_POST['payment_id'];
$booking_id = $_POST['booking_id'];
$action = $_POST['action'];
$staff_id = $_SESSION['user_id'];

if ($action === 'approve') {
    // อัปเดตสถานะการชำระเงิน
    $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'verified', verified_by = ? WHERE id = ?");
    $stmt->execute([$staff_id, $payment_id]);

    // อัปเดตสถานะการจองเป็นยืนยันแล้ว
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
    $stmt->execute([$booking_id]);

} elseif ($action === 'reject') {
    $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'rejected', verified_by = ? WHERE id = ?");
    $stmt->execute([$staff_id, $payment_id]);

    // การจองถูกยกเลิกไปด้วย เพราะสลิปไม่ผ่าน
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$booking_id]);
}

header("Location: pending_payments.php");
exit();
?>
<?php
require_once '../includes/staff_auth_check.php';
require_once '../config/db_connect.php';

$booking_id = $_POST['booking_id'];
$new_status = $_POST['new_status'];

// อัปเดตสถานะการจอง
$stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
$stmt->execute([$new_status, $booking_id]);

// ดึง room_id ของการจองนี้ มาอัปเดตสถานะห้องตามไปด้วย
$stmt = $pdo->prepare("SELECT room_id FROM bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$room_id = $stmt->fetchColumn();

if ($new_status === 'checked_in') {
    $stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
} else { // checked_out
    $stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
}
$stmt->execute([$room_id]);

header("Location: manage_bookings.php");
exit();
?>
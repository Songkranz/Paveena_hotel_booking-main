<?php
require_once '../includes/admin_auth_check.php';
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_rooms.php');
    exit();
}

$room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
$csrf_token = $_POST['csrf_token'] ?? '';

if (!$room_id || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    header('Location: manage_rooms.php?delete_error=invalid');
    exit();
}

$stmt = $pdo->prepare("SELECT rooms.image, rooms.image_2, rooms.image_3,
                              (SELECT COUNT(*) FROM bookings WHERE bookings.room_id = rooms.id) AS booking_count
                       FROM rooms
                       WHERE rooms.id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    header('Location: manage_rooms.php?delete_error=not_found');
    exit();
}

// เก็บประวัติการจองไว้ จึงอนุญาตให้ลบเฉพาะห้องที่ยังไม่เคยถูกจอง
if ((int)$room['booking_count'] > 0) {
    header('Location: manage_rooms.php?delete_error=has_bookings');
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);

    foreach ([$room['image'], $room['image_2'], $room['image_3']] as $image_name) {
        if ($image_name) {
            $image_path = __DIR__ . '/../assets/uploads/rooms/' . basename($image_name);
            if (is_file($image_path)) {
                unlink($image_path);
            }
        }
    }

    header('Location: manage_rooms.php?deleted=1');
    exit();
} catch (PDOException $e) {
    header('Location: manage_rooms.php?delete_error=failed');
    exit();
}
?>

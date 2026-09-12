<?php
require_once '../includes/admin_auth_check.php';
require_once '../config/db_connect.php';

$user_id = $_GET['id'];

// กันลบบัญชีตัวเอง (ป้องกันซ้ำอีกชั้น เผื่อมีคนพิมพ์ URL ตรง ๆ)
if ($user_id == $_SESSION['user_id']) {
    header("Location: manage_staff.php");
    exit();
}

// เคยอนุมัติสลิปไว้ไหม ถ้าเคยจะลบไม่ได้ เพราะมี FOREIGN KEY ผูกอยู่
$stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE verified_by = ?");
$stmt->execute([$user_id]);

if ($stmt->fetchColumn() > 0) {
    // ลดสิทธิ์เป็นลูกค้าแทนการลบ ประวัติการอนุมัติยังอยู่ครบ
    $stmt = $pdo->prepare("UPDATE users SET role = 'customer' WHERE id = ?");
    $stmt->execute([$user_id]);
} else {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
}

header("Location: manage_staff.php");
exit();
?>
<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าหลักลูกค้า</title>
</head>
<body>
    <h2>สวัสดีคุณ <?= htmlspecialchars($_SESSION['full_name']) ?></h2>
    <nav>
        <a href="search_rooms.php">ค้นหาห้องพัก</a> |
        <a href="my_bookings.php">การจองของฉัน</a> |
        <a href="logout.php">ออกจากระบบ</a>
    </nav>
</body>
</html>


<h2>xfkjvidikbhjtuikihruihytu</h2>
<?php
session_start();
require_once 'config/db_connect.php';

// ดึงประเภทห้อง + จำนวนห้องที่ใช้งานได้
$types = $pdo->query("SELECT room_types.*,
                             (SELECT COUNT(*) FROM rooms
                              WHERE rooms.room_type_id = room_types.id
                              AND rooms.status <> 'maintenance') AS room_count
                      FROM room_types
                      ORDER BY price_per_night ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paveena Hotel — จองห้องพักออนไลน์</title>
    <style>
        body { font-family: sans-serif; margin: 0; }
        .hero { background: #2c3e50; color: #fff; padding: 50px 30px; }
        .hero h1 { margin: 0 0 10px; }
        .container { padding: 30px; max-width: 1000px; margin: auto; }
        .card { border: 1px solid #ddd; border-radius: 6px; padding: 15px;
                margin-bottom: 15px; display: inline-block; width: 280px;
                vertical-align: top; margin-right: 15px; }
        .price { color: #c0392b; font-size: 22px; font-weight: bold; }
        .btn { display: inline-block; background: #2980b9; color: #fff;
               padding: 10px 20px; text-decoration: none; border-radius: 4px; }
        .btn-outline { background: transparent; border: 1px solid #fff; }
        nav a { color: #fff; margin-left: 12px; }
    </style>
</head>
<body>
    <div class="hero">
        <div style="text-align:right;">
            <nav>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span>สวัสดีคุณ <?= htmlspecialchars($_SESSION['full_name']) ?></span>
                    <?php
                    $home = $_SESSION['role'] === 'admin'  ? 'admin/dashboard.php'
                          : ($_SESSION['role'] === 'staff' ? 'staff/dashboard.php'
                          : 'customer/dashboard.php');
                    ?>
                    <a href="<?= $home ?>">เข้าสู่ระบบจัดการ</a>
                    <a href="customer/logout.php">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="customer/login.php">เข้าสู่ระบบ</a>
                    <a href="customer/register.php">สมัครสมาชิก</a>
                <?php endif; ?>
            </nav>
        </div>

        <h1>Paveena Hotel</h1>
        <p>ระบบจองห้องพักออนไลน์ — จองง่าย ชำระเงินสะดวก ยืนยันรวดเร็ว</p>
        <a class="btn btn-outline"
           href="<?= isset($_SESSION['user_id']) ? 'customer/search_rooms.php' : 'customer/login.php' ?>">
           ค้นหาห้องว่าง
        </a>
    </div>

    <div class="container">
        <h2>ประเภทห้องพัก</h2>
        <?php if (count($types) === 0): ?>
            <p>ยังไม่มีข้อมูลห้องพักในระบบ</p>
        <?php endif; ?>

        <?php foreach ($types as $t): ?>
            <div class="card">
                <h3><?= htmlspecialchars($t['type_name']) ?></h3>
                <p class="price"><?= number_format($t['price_per_night'], 2) ?> บาท<small> /คืน</small></p>
                <p><?= htmlspecialchars($t['description']) ?></p>
                <p style="color:#888; font-size:13px;">
                    เข้าพักได้ <?= $t['max_guests'] ?> คน | มี <?= $t['room_count'] ?> ห้อง
                </p>
            </div>
        <?php endforeach; ?>

        <h2>ขั้นตอนการจอง</h2>
        <ol>
            <li>สมัครสมาชิกและเข้าสู่ระบบ</li>
            <li>เลือกวันที่เข้าพัก–ออก แล้วค้นหาห้องว่าง</li>
            <li>เลือกห้องและบริการเสริม จากนั้นยืนยันการจอง</li>
            <li>โอนเงินและแนบสลิปในระบบ</li>
            <li>รอพนักงานตรวจสอบและยืนยันการจอง</li>
        </ol>

        <div style="border:1px solid #c00; padding:12px; background:#fff5f5; max-width:600px;">
            <strong>นโยบายการยกเลิก</strong>
            <p style="margin:5px 0;">เมื่อชำระเงินและได้รับการยืนยันแล้ว
            หากยกเลิกการจองในภายหลัง ทางโรงแรมขอสงวนสิทธิ์ไม่คืนเงินทุกกรณี</p>
        </div>
    </div>
</body>
</html>
<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>บัญชีของฉัน — Paveena Hotel</title>
    <link rel="stylesheet" href="../assets/site.css">
</head>
<body>
<div class="page-shell">
    <header class="site-header">
        <div class="header-top">
            <a class="brand" href="../index.php">
                <span class="brand-mark">PH</span>
                <span>PAVEENA HOTEL</span>
            </a>
            <div class="header-account">
                <span>สวัสดีคุณ <?= htmlspecialchars($_SESSION['full_name']) ?></span>
                <a href="logout.php">ออกจากระบบ</a>
            </div>
        </div>
        <nav class="site-nav" aria-label="เมนูหลัก">
            <a href="../index.php">หน้าแรก</a>
            <a href="search_rooms.php">ค้นหาห้อง</a>
            <a href="my_bookings.php">การจองของฉัน</a>
            <a class="active" href="dashboard.php">บัญชีของฉัน</a>
        </nav>
    </header>

    <main>
        <section class="customer-hero">
            <div class="customer-hero-content">
                <p class="eyebrow">Welcome to Paveena Hotel</p>
                <h1>สวัสดีคุณ <?= htmlspecialchars($_SESSION['full_name']) ?></h1>
                <p>จัดการทุกช่วงเวลาของการพักผ่อน ตั้งแต่ค้นหาห้องว่างไปจนถึงติดตามสถานะการจอง</p>
                <a class="button button-aqua" href="search_rooms.php">ค้นหาห้องพัก</a>
            </div>
        </section>

        <section class="content-page dashboard-section">
            <div class="section-heading">
                <div>
                    <p class="eyebrow" style="color:var(--aqua);">My account</p>
                    <h2>จัดการการเข้าพักของคุณ</h2>
                </div>
                <p>เลือกเมนูที่ต้องการด้านล่าง</p>
            </div>

            <div class="dashboard-grid">
                <a class="dashboard-card" href="search_rooms.php">
                    <span class="dashboard-card-number">01</span>
                    <div>
                        <h3>ค้นหาห้องพัก</h3>
                        <p>ตรวจสอบห้องว่างตามวันที่ พร้อมดูรูปและรายละเอียดห้อง</p>
                    </div>
                    <span class="dashboard-card-arrow">→</span>
                </a>
                <a class="dashboard-card" href="my_bookings.php">
                    <span class="dashboard-card-number">02</span>
                    <div>
                        <h3>การจองของฉัน</h3>
                        <p>ดูสถานะการจอง แนบสลิป หรือจัดการรายการที่กำลังดำเนินการ</p>
                    </div>
                    <span class="dashboard-card-arrow">→</span>
                </a>
                <a class="dashboard-card" href="../index.php#rooms">
                    <span class="dashboard-card-number">03</span>
                    <div>
                        <h3>สำรวจประเภทห้อง</h3>
                        <p>เลือกบรรยากาศและขนาดห้องที่เหมาะกับการเดินทางของคุณ</p>
                    </div>
                    <span class="dashboard-card-arrow">→</span>
                </a>
            </div>

            <div class="quick-guide">
                <div>
                    <p class="eyebrow">Easy booking</p>
                    <h2>จองง่ายใน 3 ขั้นตอน</h2>
                </div>
                <ol>
                    <li><span>1</span>เลือกวันเข้าพักและห้องที่ต้องการ</li>
                    <li><span>2</span>ยืนยันรายละเอียดการจอง</li>
                    <li><span>3</span>แนบสลิปและรอการตรวจสอบ</li>
                </ol>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <strong>PAVEENA HOTEL</strong>
        <span>การพักผ่อนที่เรียบง่ายและน่าจดจำ</span>
    </footer>
</div>
</body>
</html>

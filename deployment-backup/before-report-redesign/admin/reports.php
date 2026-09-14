<?php
require_once '../includes/admin_auth_check.php';
require_once '../config/db_connect.php';

$start = $_GET['start_date'] ?? date('Y-m-01');
$end   = $_GET['end_date']   ?? date('Y-m-d');

// 1. สรุปยอดรวม (ใช้ paid_at)
$stmt = $pdo->prepare("SELECT COUNT(id) AS total_bookings, IFNULL(SUM(amount), 0) AS total_revenue 
                       FROM payments 
                       WHERE payment_status = 'verified' 
                       AND paid_at BETWEEN ? AND ?");
$stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. แยกตามประเภทห้อง (ใช้ paid_at)
$stmt = $pdo->prepare("SELECT rt.type_name, COUNT(p.id) AS cnt, IFNULL(SUM(p.amount), 0) AS rev
                       FROM payments p
                       JOIN bookings b ON p.booking_id = b.id
                       JOIN rooms r ON b.room_id = r.id
                       JOIN room_types rt ON r.room_type_id = rt.id
                       WHERE p.payment_status = 'verified' 
                       AND p.paid_at BETWEEN ? AND ?
                       GROUP BY rt.id");
$stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);
$by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. รายได้จากค่าปรับ (ใช้ paid_at)
$stmt = $pdo->prepare("SELECT COUNT(p.id) AS cnt, IFNULL(SUM(p.amount), 0) AS amount
                       FROM payments p
                       JOIN bookings b ON p.booking_id = b.id
                       WHERE p.payment_status = 'verified' 
                       AND b.status = 'cancelled' 
                       AND p.paid_at BETWEEN ? AND ?");
$stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);
$forfeited = $stmt->fetch(PDO::FETCH_ASSOC);

// 4. สรุปสถานะการจอง
$status_stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM bookings GROUP BY status");
$stats = $status_stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. รายการจองล่าสุด 10 รายการ
$recent = $pdo->query("SELECT b.*, r.room_number, u.full_name 
                       FROM bookings b 
                       JOIN rooms r ON b.room_id = r.id 
                       JOIN users u ON b.user_id = u.id 
                       ORDER BY b.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

?>


<div style="margin-bottom: 20px;">
        <a href="dashboard.php">&laquo; กลับหน้าหลัก</a> | 
        <button onclick="window.print()">พิมพ์รายงาน</button>
    </div>

 <!-- 1. สรุปยอดรวม -->
<p>จำนวนการจองที่ชำระเงินแล้ว: <?= $summary['total_bookings'] ?> รายการ</p>
<p>รายได้รวม: <?= number_format($summary['total_revenue'], 2) ?> บาท</p>

<!-- 2. รายได้จากค่าปรับ (ยกเลิกหลังชำระเงิน) -->
<p>ยกเลิกหลังชำระเงิน (ไม่คืนเงิน): <?= $forfeited['cnt'] ?> รายการ</p>
<p>รวมรายได้ค่าปรับ: <?= number_format($forfeited['amount'], 2) ?> บาท</p>

<!-- 3. ตารางสรุปตามประเภทห้อง -->
<table>
    <tr><th>ประเภทห้อง</th><th>จำนวนการจอง</th><th>รายได้ (บาท)</th></tr>
    <?php foreach ($by_type as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['type_name']) ?></td>
        <td><?= $row['cnt'] ?></td>
        <td><?= number_format($row['rev'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<!-- 4. สรุปสถานะการจอง -->
<table>
    <tr><th>สถานะ</th><th>จำนวน</th></tr>
    <?php foreach ($stats as $s): ?>
    <tr>
        <td><?= htmlspecialchars($s['status']) ?></td>
        <td><?= $s['cnt'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<!-- 5. รายการจองล่าสุด 10 รายการ -->
<table>
    <tr><th>ผู้จอง</th><th>เลขห้อง</th><th>สถานะ</th><th>วันที่จอง</th></tr>
    <?php foreach ($recent as $r): ?>
    <tr>
        <td><?= htmlspecialchars($r['full_name']) ?></td>
        <td><?= htmlspecialchars($r['room_number']) ?></td>
        <td><?= htmlspecialchars($r['status']) ?></td>
        <td><?= $r['created_at'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
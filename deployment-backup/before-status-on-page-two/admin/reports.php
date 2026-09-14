<?php
require_once '../includes/admin_auth_check.php';
require_once '../config/db_connect.php';

$default_start = date('Y-m-01');
$default_end = date('Y-m-d');
$start = $_GET['start_date'] ?? $default_start;
$end = $_GET['end_date'] ?? $default_end;
$filter_error = '';

function isValidReportDate(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

if (!isValidReportDate($start) || !isValidReportDate($end)) {
    $filter_error = 'รูปแบบวันที่ไม่ถูกต้อง ระบบจึงแสดงข้อมูลของเดือนปัจจุบันแทน';
    $start = $default_start;
    $end = $default_end;
} elseif ($start > $end) {
    $filter_error = 'วันที่เริ่มต้นต้องไม่อยู่หลังวันที่สิ้นสุด ระบบจึงแสดงข้อมูลของเดือนปัจจุบันแทน';
    $start = $default_start;
    $end = $default_end;
} elseif ($end > $default_end) {
    $filter_error = 'วันที่สิ้นสุดต้องไม่เกินวันที่ปัจจุบัน ระบบจึงแสดงข้อมูลของเดือนปัจจุบันแทน';
    $start = $default_start;
    $end = $default_end;
}

$period_start = $start . ' 00:00:00';
$period_end = $end . ' 23:59:59';

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT booking_id) AS total_bookings,
                              IFNULL(SUM(amount), 0) AS total_revenue
                       FROM payments
                       WHERE payment_status = 'verified'
                       AND paid_at BETWEEN ? AND ?");
$stmt->execute([$period_start, $period_end]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT rt.type_name,
                              COUNT(DISTINCT p.booking_id) AS cnt,
                              IFNULL(SUM(p.amount), 0) AS rev
                       FROM payments p
                       JOIN bookings b ON p.booking_id = b.id
                       JOIN rooms r ON b.room_id = r.id
                       JOIN room_types rt ON r.room_type_id = rt.id
                       WHERE p.payment_status = 'verified'
                       AND p.paid_at BETWEEN ? AND ?
                       GROUP BY rt.id, rt.type_name
                       ORDER BY rev DESC, rt.type_name ASC");
$stmt->execute([$period_start, $period_end]);
$by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT p.booking_id) AS cnt,
                              IFNULL(SUM(p.amount), 0) AS amount
                       FROM payments p
                       JOIN bookings b ON p.booking_id = b.id
                       WHERE p.payment_status = 'verified'
                       AND b.status = 'cancelled'
                       AND p.paid_at BETWEEN ? AND ?");
$stmt->execute([$period_start, $period_end]);
$forfeited = $stmt->fetch(PDO::FETCH_ASSOC);

$status_stmt = $pdo->prepare("SELECT status, COUNT(*) AS cnt
                              FROM bookings
                              WHERE created_at BETWEEN ? AND ?
                              GROUP BY status
                              ORDER BY FIELD(status, 'pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled')");
$status_stmt->execute([$period_start, $period_end]);
$stats = $status_stmt->fetchAll(PDO::FETCH_ASSOC);

$recent_stmt = $pdo->prepare("SELECT b.id, b.total_price, b.status, b.created_at,
                                    r.room_number, u.full_name
                             FROM bookings b
                             JOIN rooms r ON b.room_id = r.id
                             JOIN users u ON b.user_id = u.id
                             WHERE b.created_at BETWEEN ? AND ?
                             ORDER BY b.created_at DESC
                             LIMIT 10");
$recent_stmt->execute([$period_start, $period_end]);
$recent = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

$status_thai = [
    'pending' => 'รอตรวจสอบ',
    'confirmed' => 'ยืนยันแล้ว',
    'checked_in' => 'เช็กอินแล้ว',
    'checked_out' => 'เช็กเอาต์แล้ว',
    'cancelled' => 'ยกเลิก'
];

$total_revenue = (float)$summary['total_revenue'];
$total_paid_bookings = (int)$summary['total_bookings'];
$average_revenue = $total_paid_bookings > 0 ? $total_revenue / $total_paid_bookings : 0;
$total_status_bookings = array_sum(array_map('intval', array_column($stats, 'cnt')));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รายงานสรุป — Paveena Hotel</title>
    <link rel="stylesheet" href="../assets/site.css">
</head>
<body class="report-body">
<div class="report-shell">
    <header class="report-header">
        <a class="report-brand" href="dashboard.php" aria-label="กลับหน้าหลักผู้ดูแลระบบ">
            <span>PH</span>
            <strong>PAVEENA HOTEL</strong>
        </a>
        <div class="report-header-actions">
            <a href="dashboard.php">กลับหน้าหลัก</a>
            <button type="button" onclick="window.print()">พิมพ์รายงาน</button>
        </div>
    </header>

    <main>
        <section class="report-heading-zone">
            <div>
                <p class="report-kicker">ADMINISTRATIVE REPORT</p>
                <h1>รายงานภาพรวมโรงแรม</h1>
                <p>สรุปยอดชำระเงิน ประเภทห้อง สถานะ และรายการจองในช่วงวันที่เลือก</p>
            </div>
            <div class="report-period">
                <span>ช่วงข้อมูล</span>
                <strong><?= date('d/m/Y', strtotime($start)) ?> - <?= date('d/m/Y', strtotime($end)) ?></strong>
            </div>
        </section>

        <section class="report-control-zone" aria-labelledby="filter-title">
            <div class="report-control-copy">
                <h2 id="filter-title">เลือกช่วงวันที่</h2>
                <p>ระบบกำหนดเป็นวันแรกของเดือนถึงวันนี้ให้โดยอัตโนมัติ</p>
            </div>
            <form class="report-filter" method="GET">
                <label>
                    <span>วันที่เริ่มต้น</span>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start) ?>" max="<?= date('Y-m-d') ?>" required>
                </label>
                <label>
                    <span>วันที่สิ้นสุด</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end) ?>" max="<?= date('Y-m-d') ?>" required>
                </label>
                <button type="submit">แสดงรายงาน</button>
            </form>
        </section>

        <?php if ($filter_error): ?>
            <div class="report-message" role="alert"><?= htmlspecialchars($filter_error) ?></div>
        <?php endif; ?>

        <section class="report-body-zone">
            <div class="report-summary" aria-label="ข้อมูลสรุป">
                <article>
                    <span>รายได้ที่ตรวจสอบแล้ว</span>
                    <strong><?= number_format($total_revenue, 2) ?></strong>
                    <small>บาท</small>
                </article>
                <article>
                    <span>การจองที่ชำระแล้ว</span>
                    <strong><?= number_format($total_paid_bookings) ?></strong>
                    <small>รายการ</small>
                </article>
                <article>
                    <span>ยอดยกเลิกไม่คืนเงิน</span>
                    <strong><?= number_format((float)$forfeited['amount'], 2) ?></strong>
                    <small><?= number_format((int)$forfeited['cnt']) ?> รายการ</small>
                </article>
                <article>
                    <span>รายได้เฉลี่ยต่อการจอง</span>
                    <strong><?= number_format($average_revenue, 2) ?></strong>
                    <small>บาท</small>
                </article>
            </div>

            <div class="report-two-column">
                <section class="report-section">
                    <div class="report-section-heading">
                        <div>
                            <p class="report-kicker">REVENUE</p>
                            <h2>รายได้ตามประเภทห้อง</h2>
                        </div>
                        <span><?= count($by_type) ?> ประเภท</span>
                    </div>
                    <?php if (!$by_type): ?>
                        <div class="report-empty">ยังไม่มีรายได้ที่ตรวจสอบแล้วในช่วงวันที่นี้</div>
                    <?php else: ?>
                        <div class="report-table-wrap">
                            <table class="report-table">
                                <thead>
                                <tr>
                                    <th>ประเภทห้อง</th>
                                    <th class="number">จำนวน</th>
                                    <th class="number">รายได้ (บาท)</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($by_type as $row): ?>
                                    <?php $share = $total_revenue > 0 ? ((float)$row['rev'] / $total_revenue) * 100 : 0; ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($row['type_name']) ?></strong>
                                            <span class="report-bar"><i style="width: <?= round($share, 1) ?>%"></i></span>
                                        </td>
                                        <td class="number"><?= number_format((int)$row['cnt']) ?></td>
                                        <td class="number"><?= number_format((float)$row['rev'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="report-section">
                    <div class="report-section-heading">
                        <div>
                            <p class="report-kicker">BOOKING STATUS</p>
                            <h2>สถานะการจอง</h2>
                        </div>
                        <span><?= number_format($total_status_bookings) ?> รายการ</span>
                    </div>
                    <?php if (!$stats): ?>
                        <div class="report-empty">ยังไม่มีการจองในช่วงวันที่นี้</div>
                    <?php else: ?>
                        <ul class="report-status-list">
                            <?php foreach ($stats as $row): ?>
                                <?php $status_share = $total_status_bookings > 0 ? ((int)$row['cnt'] / $total_status_bookings) * 100 : 0; ?>
                                <li>
                                    <div>
                                        <span><?= htmlspecialchars($status_thai[$row['status']] ?? $row['status']) ?></span>
                                        <strong><?= number_format((int)$row['cnt']) ?></strong>
                                    </div>
                                    <span class="report-bar"><i style="width: <?= round($status_share, 1) ?>%"></i></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>

            <section class="report-section report-recent">
                <div class="report-section-heading">
                    <div>
                        <p class="report-kicker">LATEST ACTIVITY</p>
                        <h2>การจองล่าสุดในช่วงที่เลือก</h2>
                    </div>
                    <span>สูงสุด 10 รายการ</span>
                </div>
                <?php if (!$recent): ?>
                    <div class="report-empty">ยังไม่มีรายการจองในช่วงวันที่นี้</div>
                <?php else: ?>
                    <div class="report-table-wrap">
                        <table class="report-table">
                            <thead>
                            <tr>
                                <th>เลขที่</th>
                                <th>ผู้จอง</th>
                                <th>ห้อง</th>
                                <th>สถานะ</th>
                                <th class="number">ยอดรวม (บาท)</th>
                                <th>วันที่จอง</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recent as $row): ?>
                                <tr>
                                    <td>#<?= (int)$row['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['room_number']) ?></td>
                                    <td><span class="report-status"><?= htmlspecialchars($status_thai[$row['status']] ?? $row['status']) ?></span></td>
                                    <td class="number"><?= number_format((float)$row['total_price'], 2) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </section>

        <section class="report-instruction-zone">
            <div>
                <p class="report-kicker">INSTRUCTION</p>
                <h2>วิธีใช้งานรายงาน</h2>
            </div>
            <p>เลือกวันที่เริ่มต้นและวันที่สิ้นสุด แล้วกด “แสดงรายงาน” ข้อมูลรายได้จะนับเฉพาะสลิปที่ตรวจสอบแล้ว ส่วนสถานะและรายการล่าสุดอ้างอิงวันที่สร้างการจอง</p>
            <button type="button" onclick="window.print()">พิมพ์หน้านี้</button>
        </section>
    </main>

    <footer class="report-footer">
        <span>จัดทำโดยระบบ Paveena Hotel</span>
        <span>สร้างรายงาน <?= date('d/m/Y H:i') ?> น.</span>
    </footer>
</div>
</body>
</html>

<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';

$booking_id = $_GET['booking_id'] ?? $_POST['booking_id'] ?? '';
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES['slip']) && $_FILES['slip']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "อัปโหลดได้เฉพาะไฟล์ jpg, jpeg, png เท่านั้น";
        } else {
            $new_filename = "slip_" . $booking_id . "_" . time() . "." . $ext;
            $upload_path = "../assets/uploads/slips/" . $new_filename;

            move_uploaded_file($_FILES['slip']['tmp_name'], $upload_path);

            $stmt = $pdo->prepare("SELECT total_price FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, slip_image, payment_status)
                                    VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$booking_id, $booking['total_price'], $new_filename]);

            header("Location: my_bookings.php?uploaded=1");
            exit();
        }
    } else {
        $error = "กรุณาเลือกไฟล์สลิป";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>แนบสลิป — Paveena Hotel</title>
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
                <a class="button" href="dashboard.php">บัญชีของฉัน</a>
                <a href="logout.php">ออกจากระบบ</a>
            </div>
        </div>
        <nav class="site-nav" aria-label="เมนูหลัก">
            <a href="../index.php">หน้าแรก</a>
            <a href="search_rooms.php">ค้นหาห้อง</a>
            <a class="active" href="my_bookings.php">การจองของฉัน</a>
        </nav>
    </header>

    <main class="content-page upload-page">
        <a class="back-link" href="my_bookings.php">← กลับไปยังการจองของฉัน</a>
        <div class="upload-layout">
            <section class="upload-card">
                <p class="eyebrow">Payment confirmation</p>
                <h1>แนบหลักฐานการโอนเงิน</h1>
                <p>รายการจอง #<?= htmlspecialchars($booking_id) ?> กรุณาเลือกรูปสลิปที่มองเห็นรายละเอียดชัดเจน</p>

                <?php if ($error): ?>
                    <div class="notice notice-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking_id) ?>">
                    <label class="upload-dropzone" for="slip">
                        <span class="upload-icon">↑</span>
                        <strong>เลือกรูปสลิปจากอุปกรณ์</strong>
                        <span id="selected-file">รองรับไฟล์ JPG, JPEG และ PNG</span>
                        <input type="file" id="slip" name="slip" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                    </label>
                    <button class="button button-aqua upload-submit" type="submit">อัปโหลดสลิป</button>
                </form>
            </section>

            <aside class="upload-aside">
                <p class="eyebrow">Before upload</p>
                <h2>ตรวจสอบก่อนส่ง</h2>
                <ul>
                    <li><span>01</span>ยอดเงินตรงกับรายการจอง</li>
                    <li><span>02</span>ชื่อผู้โอนและวันเวลามองเห็นชัดเจน</li>
                    <li><span>03</span>รูปไม่เบลอและไม่ถูกตัดขอบ</li>
                </ul>
                <p>หลังอัปโหลด พนักงานจะตรวจสอบและอัปเดตสถานะในหน้าการจองของฉัน</p>
            </aside>
        </div>
    </main>

    <footer class="site-footer">
        <strong>PAVEENA HOTEL</strong>
        <span>การพักผ่อนที่เรียบง่ายและน่าจดจำ</span>
    </footer>
</div>
<script>
const slipInput = document.getElementById('slip');
const selectedFile = document.getElementById('selected-file');
slipInput.addEventListener('change', function () {
    selectedFile.textContent = this.files.length ? this.files[0].name : 'รองรับไฟล์ JPG, JPEG และ PNG';
});
</script>
</body>
</html>

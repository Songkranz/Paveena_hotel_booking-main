<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';

$booking_id = $_GET['booking_id'] ?? $_POST['booking_id'];
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

            // ดึงราคารวมจากตารางการจอง มาใส่ในตาราง payments
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
    <title>แนบสลิปโอนเงิน</title>
</head>
<body>
    <h2>แนบหลักฐานการโอนเงิน</h2>
    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking_id) ?>">
        เลือกไฟล์สลิป: <input type="file" name="slip" accept=".jpg,.jpeg,.png" required><br><br>
        <button type="submit">อัปโหลด</button>
    </form>
</body>
</html>
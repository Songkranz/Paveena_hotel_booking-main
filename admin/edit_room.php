<?php
require_once '../includes/admin_auth_check.php';
require_once '../config/db_connect.php';

$room_id = $_GET['id'] ?? $_POST['room_id'];
$error   = "";

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    header("Location: manage_rooms.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_number  = trim($_POST['room_number']);
    $room_type_id = $_POST['room_type_id'];
    $image_name   = $room['image'];   // ค่าเดิมถ้าไม่อัปโหลดใหม่

    $check = $pdo->prepare("SELECT id FROM rooms WHERE room_number = ? AND id <> ?");
    $check->execute([$room_number, $room_id]);

    if ($check->rowCount() > 0) {
        $error = "เลขห้องนี้ถูกใช้กับห้องอื่นแล้ว";
    } else {
        // อัปโหลดรูปใหม่ถ้ามีการเลือกไฟล์
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $error = "อัปโหลดได้เฉพาะไฟล์ jpg, jpeg, png, webp";
            } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
                $error = "ไฟล์ต้องมีขนาดไม่เกิน 3 MB";
            } else {
                $image_name = "room_" . $room_id . "_" . time() . "." . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "../assets/uploads/rooms/" . $image_name);

                // ลบรูปเก่าทิ้งไม่ให้ไฟล์ขยะสะสม
                if ($room['image'] && file_exists("../assets/uploads/rooms/" . $room['image'])) {
                    unlink("../assets/uploads/rooms/" . $room['image']);
                }
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, room_type_id = ?, image = ? WHERE id = ?");
            $stmt->execute([$room_number, $room_type_id, $image_name, $room_id]);
            header("Location: manage_rooms.php?updated=1");
            exit();
        }
    }
}

$types = $pdo->query("SELECT * FROM room_types ORDER BY price_per_night ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขห้องพัก</title>
    <style>body { font-family: sans-serif; }</style>
</head>
<body>
    <a href="manage_rooms.php">&laquo; กลับ</a>
    <h2>แก้ไขห้อง <?= htmlspecialchars($room['room_number']) ?></h2>

    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">

        เลขห้อง: <input type="text" name="room_number"
                        value="<?= htmlspecialchars($room['room_number']) ?>" required><br><br>

        ประเภทห้อง:
        <select name="room_type_id" required>
            <?php foreach ($types as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $t['id'] == $room['room_type_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['type_name']) ?> (<?= number_format($t['price_per_night'], 2) ?> บาท)
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <?php if ($room['image']): ?>
            <p>รูปปัจจุบัน:</p>
            <img src="../assets/uploads/rooms/<?= htmlspecialchars($room['image']) ?>" width="250"><br><br>
        <?php else: ?>
            <p style="color:#888;">ยังไม่มีรูปห้องนี้</p>
        <?php endif; ?>

        เปลี่ยนรูป: <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"><br>
        <small style="color:#888;">ไม่เลือกไฟล์ = ใช้รูปเดิม | ขนาดไม่เกิน 3 MB</small><br><br>

        <button type="submit">บันทึกการแก้ไข</button>
    </form>
</body>
</html>
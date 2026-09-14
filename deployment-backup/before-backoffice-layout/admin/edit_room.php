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
    $image_names  = [$room['image'], $room['image_2'], $room['image_3']];
    $validated_images = [];
    $moved_image_paths = [];
    $old_images_to_delete = [];

    $check = $pdo->prepare("SELECT id FROM rooms WHERE room_number = ? AND id <> ?");
    $check->execute([$room_number, $room_id]);

    if ($check->rowCount() > 0) {
        $error = "เลขห้องนี้ถูกใช้กับห้องอื่นแล้ว";
    } else {
        $allowed_mimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if (isset($_FILES['images']) && is_array($_FILES['images']['error'])) {
            for ($slot = 0; $slot < 3; $slot++) {
                $upload_error = $_FILES['images']['error'][$slot] ?? UPLOAD_ERR_NO_FILE;
                if ($upload_error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($upload_error !== UPLOAD_ERR_OK) {
                    $error = "อัปโหลดรูปที่ " . ($slot + 1) . " ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง";
                    break;
                }
                if ($_FILES['images']['size'][$slot] > 3 * 1024 * 1024) {
                    $error = "รูปที่ " . ($slot + 1) . " ต้องมีขนาดไม่เกิน 3 MB";
                    break;
                }

                $image_info = getimagesize($_FILES['images']['tmp_name'][$slot]);
                if ($image_info === false || !isset($allowed_mimes[$image_info['mime']])) {
                    $error = "รูปที่ " . ($slot + 1) . " ต้องเป็นไฟล์ jpg, jpeg, png หรือ webp";
                    break;
                }

                $validated_images[$slot] = [
                    'tmp_name'  => $_FILES['images']['tmp_name'][$slot],
                    'extension' => $allowed_mimes[$image_info['mime']],
                ];
            }
        }

        if (!$error) {
            try {
                $pdo->beginTransaction();

                foreach ($validated_images as $slot => $image) {
                    $image_name = "room_" . $room_id . "_" . ($slot + 1) . "_" . time()
                                . "_" . bin2hex(random_bytes(3)) . "." . $image['extension'];
                    $image_path = __DIR__ . "/../assets/uploads/rooms/" . $image_name;

                    if (!move_uploaded_file($image['tmp_name'], $image_path)) {
                        throw new RuntimeException("ไม่สามารถบันทึกไฟล์รูปได้");
                    }

                    $moved_image_paths[] = $image_path;
                    if ($image_names[$slot]) {
                        $old_images_to_delete[] = $image_names[$slot];
                    }
                    $image_names[$slot] = $image_name;
                }

                $stmt = $pdo->prepare("UPDATE rooms
                                       SET room_number = ?, room_type_id = ?, image = ?, image_2 = ?, image_3 = ?
                                       WHERE id = ?");
                $stmt->execute([
                    $room_number, $room_type_id,
                    $image_names[0], $image_names[1], $image_names[2],
                    $room_id
                ]);
                $pdo->commit();

                foreach ($old_images_to_delete as $old_image_name) {
                    $old_image_path = __DIR__ . "/../assets/uploads/rooms/" . basename($old_image_name);
                    if (is_file($old_image_path)) {
                        unlink($old_image_path);
                    }
                }

                header("Location: manage_rooms.php?updated=1");
                exit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                foreach ($moved_image_paths as $moved_image_path) {
                    if (is_file($moved_image_path)) {
                        unlink($moved_image_path);
                    }
                }
                $error = "ไม่สามารถบันทึกการแก้ไขได้ กรุณาลองใหม่อีกครั้ง";
            }
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

        <?php foreach ([$room['image'], $room['image_2'], $room['image_3']] as $slot => $current_image): ?>
            <div style="border:1px solid #ddd; padding:12px; margin-bottom:12px; max-width:420px;">
                <strong>รูปที่ <?= $slot + 1 ?></strong><br><br>
                <?php if ($current_image): ?>
                    <img src="../assets/uploads/rooms/<?= htmlspecialchars($current_image) ?>"
                         alt="รูปห้อง <?= htmlspecialchars($room['room_number']) ?> รูปที่ <?= $slot + 1 ?>"
                         width="250" style="max-height:170px; object-fit:cover;"><br><br>
                <?php else: ?>
                    <span style="color:#888;">ยังไม่มีรูปในช่องนี้</span><br><br>
                <?php endif; ?>
                <input type="file" name="images[<?= $slot ?>]" accept=".jpg,.jpeg,.png,.webp">
            </div>
        <?php endforeach; ?>

        <small style="color:#888;">ไม่เลือกไฟล์ในช่องใด = ใช้รูปเดิมของช่องนั้น | รูปละไม่เกิน 3 MB</small><br><br>

        <button type="submit">บันทึกการแก้ไข</button>
    </form>
</body>
</html>

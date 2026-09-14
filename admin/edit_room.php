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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>แก้ไขห้องพัก</title>
    <link rel="stylesheet" href="../assets/backoffice.css">
</head>
<body class="backoffice-body">
    <header class="bo-header">
        <div class="bo-header-inner">
            <a class="bo-brand" href="dashboard.php">PAVEENA HOTEL · ADMIN</a>
            <nav class="bo-nav" aria-label="เมนูผู้ดูแลระบบ"><a href="dashboard.php">หน้าหลัก</a><a href="manage_rooms.php" aria-current="page">ห้องพัก</a><a href="manage_staff.php">พนักงาน</a><a href="reports.php">รายงาน</a><a href="../customer/logout.php">ออกจากระบบ</a></nav>
        </div>
    </header>
    <main class="bo-main">
    <a class="bo-back" href="manage_rooms.php">&larr; กลับหน้าจัดการห้องพัก</a>
    <header class="bo-page-heading"><div><h1>แก้ไขห้อง <?= htmlspecialchars($room['room_number']) ?></h1><p>แก้ไขเลขห้อง ประเภท และรูปภาพประจำห้อง</p></div></header>

    <?php if ($error): ?><p class="bo-alert bo-alert-error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <section class="bo-section">
    <form class="bo-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
        <div class="bo-form-grid">
            <label class="bo-field"><span>เลขห้อง</span><input type="text" name="room_number" value="<?= htmlspecialchars($room['room_number']) ?>" required></label>
            <label class="bo-field"><span>ประเภทห้อง</span><select name="room_type_id" required><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" <?= $t['id'] == $room['room_type_id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['type_name']) ?> (<?= number_format($t['price_per_night'], 2) ?> บาท)</option><?php endforeach; ?></select></label>
        </div>

        <h2>รูปห้อง</h2>
        <div class="bo-image-grid">
        <?php foreach ([$room['image'], $room['image_2'], $room['image_3']] as $slot => $current_image): ?>
            <figure class="bo-image-slot">
                <figcaption><strong>รูปที่ <?= $slot + 1 ?></strong></figcaption>
                <?php if ($current_image): ?>
                    <img src="../assets/uploads/rooms/<?= htmlspecialchars($current_image) ?>"
                         alt="รูปห้อง <?= htmlspecialchars($room['room_number']) ?> รูปที่ <?= $slot + 1 ?>">
                <?php else: ?>
                    <p class="bo-muted">ยังไม่มีรูปในช่องนี้</p>
                <?php endif; ?>
                <label class="bo-field"><span>เลือกรูปใหม่</span><input type="file" name="images[<?= $slot ?>]" accept=".jpg,.jpeg,.png,.webp"></label>
            </figure>
        <?php endforeach; ?>
        </div>

        <p class="bo-help">หากไม่เลือกไฟล์ ระบบจะใช้รูปเดิมของช่องนั้น · รองรับไฟล์รูปขนาดไม่เกิน 3 MB</p>
        <div class="bo-actions"><button class="bo-button" type="submit">บันทึกการแก้ไข</button><a class="bo-button bo-button-secondary" href="manage_rooms.php">ยกเลิก</a></div>
    </form>
    </section>
    </main>
</body>
</html>

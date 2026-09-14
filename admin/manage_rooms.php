<?php
require_once '../includes/admin_auth_check.php';
require_once '../config/db_connect.php';

$error = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// เพิ่มห้องใหม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_room'])) {
    $room_number  = trim($_POST['room_number']);
    $room_type_id = $_POST['room_type_id'];
    $validated_images = [];
    $moved_image_paths = [];

    $check = $pdo->prepare("SELECT id FROM rooms WHERE room_number = ?");
    $check->execute([$room_number]);

    if ($check->rowCount() > 0) {
        $error = "เลขห้อง $room_number มีอยู่แล้วในระบบ";
    } else {
        $allowed_mimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if (isset($_FILES['images']) && is_array($_FILES['images']['error'])) {
            $selected_count = count(array_filter(
                $_FILES['images']['error'],
                fn($upload_error) => $upload_error !== UPLOAD_ERR_NO_FILE
            ));

            if ($selected_count > 3) {
                $error = "เลือกภาพได้สูงสุด 3 รูปต่อห้อง";
            } else {
                foreach ($_FILES['images']['error'] as $index => $upload_error) {
                    if ($upload_error === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    if ($upload_error !== UPLOAD_ERR_OK) {
                        $error = "อัปโหลดรูปไม่สำเร็จ กรุณาลองใหม่อีกครั้ง";
                        break;
                    }
                    if ($_FILES['images']['size'][$index] > 3 * 1024 * 1024) {
                        $error = "ไฟล์รูปแต่ละรูปต้องมีขนาดไม่เกิน 3 MB";
                        break;
                    }

                    $image_info = getimagesize($_FILES['images']['tmp_name'][$index]);
                    if ($image_info === false || !isset($allowed_mimes[$image_info['mime']])) {
                        $error = "อัปโหลดได้เฉพาะไฟล์รูป jpg, jpeg, png, webp";
                        break;
                    }

                    $validated_images[$index] = [
                        'tmp_name'  => $_FILES['images']['tmp_name'][$index],
                        'extension' => $allowed_mimes[$image_info['mime']],
                    ];
                }
            }
        }

        if (!$error) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type_id, status, image)
                                       VALUES (?, ?, 'available', NULL)");
                $stmt->execute([$room_number, $room_type_id]);
                $room_id = $pdo->lastInsertId();

                $image_names = [null, null, null];
                foreach ($validated_images as $index => $image) {
                    $image_name = "room_" . $room_id . "_" . ($index + 1) . "_" . time() . "." . $image['extension'];
                    $image_path = __DIR__ . "/../assets/uploads/rooms/" . $image_name;

                    if (!move_uploaded_file($image['tmp_name'], $image_path)) {
                        throw new RuntimeException("ไม่สามารถบันทึกไฟล์รูปได้");
                    }
                    $moved_image_paths[] = $image_path;
                    $image_names[$index] = $image_name;
                }

                $stmt = $pdo->prepare("UPDATE rooms SET image = ?, image_2 = ?, image_3 = ? WHERE id = ?");
                $stmt->execute([$image_names[0], $image_names[1], $image_names[2], $room_id]);

                $pdo->commit();
                header("Location: manage_rooms.php?added=1");
                exit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                foreach ($moved_image_paths as $moved_image_path) {
                    if (file_exists($moved_image_path)) {
                        unlink($moved_image_path);
                    }
                }
                $error = "ไม่สามารถเพิ่มห้องได้ กรุณาลองใหม่อีกครั้ง";
            }
        }
    }
}

// เปลี่ยนสถานะห้อง
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_status'])) {
    $room_id    = $_POST['room_id'];
    $new_status = $_POST['new_status'];

    $chk = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status = 'checked_in'");
    $chk->execute([$room_id]);

    if ($chk->fetchColumn() > 0) {
        $error = "ห้องนี้มีลูกค้าเข้าพักอยู่ ไม่สามารถเปลี่ยนสถานะได้";
    } else {
        $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $room_id]);
        header("Location: manage_rooms.php?updated=1");
        exit();
    }
}

// ดึงข้อมูล
$types = $pdo->query("SELECT * FROM room_types ORDER BY price_per_night ASC")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $pdo->query("SELECT rooms.*, room_types.type_name, room_types.price_per_night,
                             (SELECT COUNT(*) FROM bookings WHERE bookings.room_id = rooms.id) AS booking_count
                      FROM rooms
                      JOIN room_types ON rooms.room_type_id = room_types.id
                      ORDER BY rooms.room_number ASC")->fetchAll(PDO::FETCH_ASSOC);

$status_thai = ['available' => 'ว่าง', 'occupied' => 'ไม่ว่าง', 'maintenance' => 'ปิดปรับปรุง'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการห้องพัก</title>
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
    <header class="bo-page-heading">
        <div><h1>จัดการห้องพัก</h1><p>เพิ่มห้อง เปลี่ยนสถานะ และตรวจสอบประวัติการจอง</p></div>
        <a class="bo-button bo-button-secondary" href="manage_room_types.php">จัดการประเภทห้องและราคา</a>
    </header>

    <?php if ($error): ?><p class="bo-alert bo-alert-error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if (isset($_GET['added'])): ?><p class="bo-alert bo-alert-success">เพิ่มห้องพักเรียบร้อยแล้ว</p><?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><p class="bo-alert bo-alert-success">อัปเดตข้อมูลเรียบร้อยแล้ว</p><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><p class="bo-alert bo-alert-success">ลบห้องพักเรียบร้อยแล้ว</p><?php endif; ?>
    <?php if (($_GET['delete_error'] ?? '') === 'has_bookings'): ?>
        <p class="bo-alert bo-alert-error" role="alert">ลบห้องไม่ได้ เนื่องจากมีประวัติการจองอยู่ในระบบ</p>
    <?php elseif (isset($_GET['delete_error'])): ?>
        <p class="bo-alert bo-alert-error" role="alert">ไม่สามารถลบห้องได้ กรุณาลองใหม่อีกครั้ง</p>
    <?php endif; ?>

    <section class="bo-section" aria-labelledby="add-room-title">
        <h2 id="add-room-title">เพิ่มห้องพักใหม่</h2>
        <form class="bo-form" method="POST" enctype="multipart/form-data">
            <div class="bo-form-grid">
                <label class="bo-field"><span>เลขห้อง</span><input type="text" name="room_number" required></label>
                <label class="bo-field"><span>ประเภทห้อง</span><select name="room_type_id" required><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['type_name']) ?> (<?= number_format($t['price_per_night'], 2) ?> บาท)</option><?php endforeach; ?></select></label>
                <label class="bo-field bo-field-full"><span>รูปห้อง (สูงสุด 3 รูป)</span><input type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple></label>
            </div>
            <p class="bo-help">รองรับไฟล์ jpg, jpeg, png และ webp รูปละไม่เกิน 3 MB · บน Windows ใช้ Ctrl หรือ Shift เพื่อเลือกหลายรูป</p>
            <div class="bo-actions"><button class="bo-button" type="submit" name="add_room" value="1">เพิ่มห้อง</button></div>
        </form>
    </section>

    <section class="bo-section" aria-labelledby="room-list-title">
    <h2 id="room-list-title">ห้องพักทั้งหมด (<?= count($rooms) ?> ห้อง)</h2>
    <div class="bo-table-wrap">
    <table class="bo-table">
        <thead>
            <tr>
                <th>เลขห้อง</th>
                <th>ประเภท</th>
                <th>ราคา/คืน</th>
                <th>สถานะ</th>
                <th>เปลี่ยนสถานะ</th>
                <th>ประวัติจอง</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $r): ?>
            <tr>
                <td>
                    <?php if ($r['image']): ?>
                        <img src="../assets/uploads/rooms/<?= htmlspecialchars($r['image']) ?>" class="bo-room-thumb" alt="รูปห้อง <?= htmlspecialchars($r['room_number']) ?>">
                    <?php endif; ?>
                    <strong><?= htmlspecialchars($r['room_number']) ?></strong>
                </td>
                <td><?= htmlspecialchars($r['type_name']) ?></td>
                <td class="bo-number"><?= number_format($r['price_per_night'], 2) ?></td>
                <td><span class="bo-status"><?= htmlspecialchars($status_thai[$r['status']]) ?></span></td>
                <td>
                    <form class="bo-inline-form" method="POST">
                        <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                        <select name="new_status">
                            <option value="available"   <?= $r['status']=='available'   ? 'selected' : '' ?>>ว่าง</option>
                            <option value="occupied"    <?= $r['status']=='occupied'    ? 'selected' : '' ?>>ไม่ว่าง</option>
                            <option value="maintenance" <?= $r['status']=='maintenance' ? 'selected' : '' ?>>ปิดปรับปรุง</option>
                        </select>
                        <button class="bo-button" type="submit" name="change_status" value="1">บันทึก</button>
                    </form>
                </td>
                <td><?= $r['booking_count'] ?> ครั้ง</td>
                <td>
                    <div class="bo-actions">
                    <a class="bo-button bo-button-secondary" href="edit_room.php?id=<?= $r['id'] ?>">แก้ไข</a>
                    <?php if ((int)$r['booking_count'] === 0): ?>
                        <form method="POST" action="delete_room.php"
                              onsubmit="return confirm('ยืนยันลบห้อง <?= htmlspecialchars($r['room_number'], ENT_QUOTES) ?> ?');">
                            <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <button class="bo-button bo-button-danger" type="submit">ลบ</button>
                        </form>
                    <?php else: ?>
                        <span class="bo-muted" title="ห้องนี้มีประวัติการจอง">ลบไม่ได้</span>
                    <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </section>
    </main>
</body>
</html>

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
    $image_name   = null;
    $image_path   = null;

    $check = $pdo->prepare("SELECT id FROM rooms WHERE room_number = ?");
    $check->execute([$room_number]);

    if ($check->rowCount() > 0) {
        $error = "เลขห้อง $room_number มีอยู่แล้วในระบบ";
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = "อัปโหลดรูปไม่สำเร็จ กรุณาลองใหม่อีกครั้ง";
            } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
                $error = "ไฟล์รูปต้องมีขนาดไม่เกิน 3 MB";
            } else {
                $image_info = getimagesize($_FILES['image']['tmp_name']);
                $allowed_mimes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                ];

                if ($image_info === false || !isset($allowed_mimes[$image_info['mime']])) {
                    $error = "อัปโหลดได้เฉพาะไฟล์รูป jpg, jpeg, png, webp";
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

                if (isset($image_info) && $image_info !== false) {
                    $image_name = "room_" . $room_id . "_" . time() . "." . $allowed_mimes[$image_info['mime']];
                    $image_path = "../assets/uploads/rooms/" . $image_name;

                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                        throw new RuntimeException("ไม่สามารถบันทึกไฟล์รูปได้");
                    }

                    $stmt = $pdo->prepare("UPDATE rooms SET image = ? WHERE id = ?");
                    $stmt->execute([$image_name, $room_id]);
                }

                $pdo->commit();
                header("Location: manage_rooms.php?added=1");
                exit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($image_path && file_exists($image_path)) {
                    unlink($image_path);
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
$status_color = ['available' => 'green', 'occupied' => '#c60', 'maintenance' => '#999'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการห้องพัก</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background: #f0f0f0; }
        .panel { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; max-width: 700px; }
        .room-img { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; display: block; margin: 0 auto 5px; }
    </style>
</head>
<body>
    <a href="dashboard.php">&laquo; กลับหน้าหลัก</a> |
    <a href="manage_room_types.php">จัดการประเภทห้อง/ราคา</a>

    <h2>เพิ่มห้องพักใหม่</h2>

    <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if (isset($_GET['added'])): ?><p style="color:green;">เพิ่มห้องพักเรียบร้อยแล้ว</p><?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><p style="color:green;">อัปเดตข้อมูลเรียบร้อยแล้ว</p><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><p style="color:green;">ลบห้องพักเรียบร้อยแล้ว</p><?php endif; ?>
    <?php if (($_GET['delete_error'] ?? '') === 'has_bookings'): ?>
        <p style="color:red;">ลบห้องไม่ได้ เนื่องจากมีประวัติการจองอยู่ในระบบ</p>
    <?php elseif (isset($_GET['delete_error'])): ?>
        <p style="color:red;">ไม่สามารถลบห้องได้ กรุณาลองใหม่อีกครั้ง</p>
    <?php endif; ?>

    <div class="panel">
        <form method="POST" enctype="multipart/form-data">
            เลขห้อง: <input type="text" name="room_number" required>
            ประเภท:
            <select name="room_type_id" required>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['type_name']) ?> (<?= number_format($t['price_per_night'], 2) ?> บาท)</option>
                <?php endforeach; ?>
            </select>
            รูปห้อง: <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"><br><br>
            <small style="color:#888;">รองรับ jpg, jpeg, png, webp ขนาดไม่เกิน 3 MB</small><br><br>
            <button type="submit" name="add_room" value="1">เพิ่มห้อง</button>
        </form>
    </div>

    <h2>ห้องพักทั้งหมด (<?= count($rooms) ?> ห้อง)</h2>
    <table>
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
                        <img src="../assets/uploads/rooms/<?= htmlspecialchars($r['image']) ?>" class="room-img">
                    <?php endif; ?>
                    <strong><?= htmlspecialchars($r['room_number']) ?></strong>
                </td>
                <td><?= htmlspecialchars($r['type_name']) ?></td>
                <td><?= number_format($r['price_per_night'], 2) ?></td>
                <td style="color:<?= $status_color[$r['status']] ?>;">
                    <?= $status_thai[$r['status']] ?>
                </td>
                <td>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                        <select name="new_status">
                            <option value="available"   <?= $r['status']=='available'   ? 'selected' : '' ?>>ว่าง</option>
                            <option value="occupied"    <?= $r['status']=='occupied'    ? 'selected' : '' ?>>ไม่ว่าง</option>
                            <option value="maintenance" <?= $r['status']=='maintenance' ? 'selected' : '' ?>>ปิดปรับปรุง</option>
                        </select>
                        <button type="submit" name="change_status" value="1">บันทึก</button>
                    </form>
                </td>
                <td><?= $r['booking_count'] ?> ครั้ง</td>
                <td>
                    <a href="edit_room.php?id=<?= $r['id'] ?>">แก้ไข</a>
                    <?php if ((int)$r['booking_count'] === 0): ?>
                        |
                        <form method="POST" action="delete_room.php" style="display:inline;"
                              onsubmit="return confirm('ยืนยันลบห้อง <?= htmlspecialchars($r['room_number'], ENT_QUOTES) ?> ?');">
                            <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <button type="submit" style="color:red; cursor:pointer; border:0; background:none; padding:0;">ลบ</button>
                        </form>
                    <?php else: ?>
                        | <span style="color:#999;" title="ห้องนี้มีประวัติการจอง">ลบไม่ได้</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>

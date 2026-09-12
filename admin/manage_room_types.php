<?php
require_once '../includes/admin_auth_check.php';
require_once '../config/db_connect.php';

$error = "";

// เพิ่มประเภทห้องใหม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_type'])) {
    $stmt = $pdo->prepare("INSERT INTO room_types (type_name, price_per_night, description, max_guests)
                           VALUES (?, ?, ?, ?)");
    $stmt->execute([
        trim($_POST['type_name']),
        $_POST['price_per_night'],
        trim($_POST['description']),
        $_POST['max_guests']
    ]);
    header("Location: manage_room_types.php?added=1");
    exit();
}

// แก้ไขประเภทห้อง
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_type'])) {
    $stmt = $pdo->prepare("UPDATE room_types
                           SET type_name = ?, price_per_night = ?, description = ?, max_guests = ?
                           WHERE id = ?");
    $stmt->execute([
        trim($_POST['type_name']),
        $_POST['price_per_night'],
        trim($_POST['description']),
        $_POST['max_guests'],
        $_POST['type_id']
    ]);
    header("Location: manage_room_types.php?updated=1");
    exit();
}

$types = $pdo->query("SELECT room_types.*,
                             (SELECT COUNT(*) FROM rooms WHERE rooms.room_type_id = room_types.id) AS room_count
                      FROM room_types
                      ORDER BY price_per_night ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการประเภทห้องพัก</title>
    <style>
        body { font-family: sans-serif; }
        .panel { border: 1px solid #ccc; padding: 15px; margin-bottom: 15px; max-width: 700px; }
        input[type=text], textarea { width: 300px; }
    </style>
</head>
<body>
    <a href="manage_rooms.php">&laquo; กลับหน้าจัดการห้องพัก</a>

    <h2>เพิ่มประเภทห้องใหม่</h2>
    <?php if (isset($_GET['added'])): ?>
        <p style="color:green;">เพิ่มประเภทห้องเรียบร้อยแล้ว</p>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <p style="color:green;">แก้ไขเรียบร้อยแล้ว</p>
    <?php endif; ?>

    <div class="panel">
        <form method="POST">
            ชื่อประเภท: <input type="text" name="type_name" required placeholder="เช่น ห้องคู่"><br><br>
            ราคา/คืน: <input type="number" name="price_per_night" step="0.01" min="0" required><br><br>
            พักได้สูงสุด: <input type="number" name="max_guests" min="1" value="1" required> คน<br><br>
            รายละเอียด:<br><textarea name="description" rows="2"></textarea><br><br>
            <button type="submit" name="add_type" value="1">เพิ่มประเภทห้อง</button>
        </form>
    </div>

    <h2>ประเภทห้องทั้งหมด (<?= count($types) ?> ประเภท)</h2>

    <?php foreach ($types as $t): ?>
        <div class="panel">
            <form method="POST">
                <input type="hidden" name="type_id" value="<?= $t['id'] ?>">
                ชื่อประเภท: <input type="text" name="type_name"
                                   value="<?= htmlspecialchars($t['type_name']) ?>" required><br><br>
                ราคา/คืน: <input type="number" name="price_per_night" step="0.01" min="0"
                                 value="<?= $t['price_per_night'] ?>" required><br><br>
                พักได้สูงสุด: <input type="number" name="max_guests" min="1"
                                     value="<?= $t['max_guests'] ?>" required> คน<br><br>
                รายละเอียด:<br>
                <textarea name="description" rows="2"><?= htmlspecialchars($t['description']) ?></textarea><br><br>
                <small style="color:#888;">มีห้องประเภทนี้ <?= $t['room_count'] ?> ห้อง</small><br><br>
                <button type="submit" name="update_type" value="1">บันทึกการแก้ไข</button>
            </form>
        </div>
    <?php endforeach; ?>
</body>
</html>
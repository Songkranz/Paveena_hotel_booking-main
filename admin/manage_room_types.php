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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการประเภทห้องพัก</title>
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
        <header class="bo-page-heading"><div><h1>ประเภทห้องและราคา</h1><p>กำหนดชื่อ ราคา จำนวนผู้เข้าพัก และรายละเอียดของแต่ละประเภท</p></div></header>

        <?php if (isset($_GET['added'])): ?><p class="bo-alert bo-alert-success">เพิ่มประเภทห้องเรียบร้อยแล้ว</p><?php endif; ?>
        <?php if (isset($_GET['updated'])): ?><p class="bo-alert bo-alert-success">แก้ไขเรียบร้อยแล้ว</p><?php endif; ?>

        <section class="bo-section" aria-labelledby="add-type-title">
            <h2 id="add-type-title">เพิ่มประเภทห้องใหม่</h2>
            <form class="bo-form" method="POST">
                <div class="bo-form-grid">
                    <label class="bo-field"><span>ชื่อประเภท</span><input type="text" name="type_name" required placeholder="เช่น ห้องคู่"></label>
                    <label class="bo-field"><span>ราคาต่อคืน (บาท)</span><input type="number" name="price_per_night" step="0.01" min="0" required></label>
                    <label class="bo-field"><span>จำนวนผู้เข้าพักสูงสุด</span><input type="number" name="max_guests" min="1" value="1" required></label>
                    <label class="bo-field bo-field-full"><span>รายละเอียด</span><textarea name="description" rows="3"></textarea></label>
                </div>
                <div class="bo-actions"><button class="bo-button" type="submit" name="add_type" value="1">เพิ่มประเภทห้อง</button></div>
            </form>
        </section>

        <section class="bo-section" aria-labelledby="type-list-title">
            <h2 id="type-list-title">ประเภทห้องทั้งหมด (<?= count($types) ?> ประเภท)</h2>
            <div class="bo-card-list">
            <?php foreach ($types as $t): ?>
                <article class="bo-card">
                    <form class="bo-form" method="POST">
                        <input type="hidden" name="type_id" value="<?= $t['id'] ?>">
                        <div class="bo-form-grid">
                            <label class="bo-field"><span>ชื่อประเภท</span><input type="text" name="type_name" value="<?= htmlspecialchars($t['type_name']) ?>" required></label>
                            <label class="bo-field"><span>ราคาต่อคืน (บาท)</span><input type="number" name="price_per_night" step="0.01" min="0" value="<?= $t['price_per_night'] ?>" required></label>
                            <label class="bo-field"><span>จำนวนผู้เข้าพักสูงสุด</span><input type="number" name="max_guests" min="1" value="<?= $t['max_guests'] ?>" required></label>
                            <label class="bo-field bo-field-full"><span>รายละเอียด</span><textarea name="description" rows="3"><?= htmlspecialchars($t['description']) ?></textarea></label>
                        </div>
                        <p class="bo-help">มีห้องประเภทนี้ <?= number_format($t['room_count']) ?> ห้อง</p>
                        <div class="bo-actions"><button class="bo-button" type="submit" name="update_type" value="1">บันทึกการแก้ไข</button></div>
                    </form>
                </article>
            <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>

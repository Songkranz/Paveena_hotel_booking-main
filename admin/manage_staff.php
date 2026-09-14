<?php
require_once '../includes/admin_auth_check.php';
require_once '../config/db_connect.php';

$error = "";
$success = "";

// เพิ่มพนักงานใหม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_staff'])) {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = $_POST['password'];
    $role      = $_POST['role'];

    if (strlen($password) < 6) {
        $error = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $error = "อีเมลนี้ถูกใช้แล้ว";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role)
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone, $hashed, $role]);
            header("Location: manage_staff.php?added=1");
            exit();
        }
    }
}

// ดึงเฉพาะพนักงานและแอดมิน (ไม่เอาลูกค้า)
$staffs = $pdo->query("SELECT id, full_name, email, phone, role, created_at
                       FROM users
                       WHERE role IN ('staff', 'admin')
                       ORDER BY role ASC, created_at ASC")->fetchAll(PDO::FETCH_ASSOC);

$role_thai = ['staff' => 'พนักงาน', 'admin' => 'ผู้ดูแลระบบ'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการพนักงาน</title>
    <link rel="stylesheet" href="../assets/backoffice.css">
</head>
<body class="backoffice-body">
    <header class="bo-header">
        <div class="bo-header-inner">
            <a class="bo-brand" href="dashboard.php">PAVEENA HOTEL · ADMIN</a>
            <nav class="bo-nav" aria-label="เมนูผู้ดูแลระบบ">
                <a href="dashboard.php">หน้าหลัก</a><a href="manage_rooms.php">ห้องพัก</a>
                <a href="manage_staff.php" aria-current="page">พนักงาน</a><a href="reports.php">รายงาน</a>
                <a href="../customer/logout.php">ออกจากระบบ</a>
            </nav>
        </div>
    </header>
    <main class="bo-main">
        <header class="bo-page-heading"><div><h1>จัดการพนักงาน</h1><p>เพิ่มบัญชีและตรวจสอบสิทธิ์ของผู้ใช้งานภายใน</p></div></header>

        <?php if ($error): ?><p class="bo-alert bo-alert-error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if (isset($_GET['added'])): ?><p class="bo-alert bo-alert-success">เพิ่มบัญชีสำเร็จ</p><?php endif; ?>

        <section class="bo-section" aria-labelledby="add-staff-title">
            <h2 id="add-staff-title">เพิ่มพนักงานใหม่</h2>
            <form class="bo-form" method="POST">
                <div class="bo-form-grid">
                    <label class="bo-field"><span>ชื่อ-นามสกุล</span><input type="text" name="full_name" autocomplete="name" required></label>
                    <label class="bo-field"><span>อีเมล</span><input type="email" name="email" autocomplete="email" required></label>
                    <label class="bo-field"><span>เบอร์โทรศัพท์</span><input type="tel" name="phone" autocomplete="tel"></label>
                    <label class="bo-field"><span>รหัสผ่าน</span><input type="password" name="password" autocomplete="new-password" minlength="6" required></label>
                    <label class="bo-field"><span>ตำแหน่ง</span><select name="role" required><option value="staff">พนักงาน</option><option value="admin">ผู้ดูแลระบบ</option></select></label>
                </div>
                <div class="bo-actions"><button class="bo-button" type="submit" name="add_staff" value="1">เพิ่มบัญชี</button></div>
            </form>
        </section>

        <section class="bo-section" aria-labelledby="staff-list-title">
            <h2 id="staff-list-title">บัญชีพนักงานทั้งหมด (<?= count($staffs) ?> คน)</h2>
            <div class="bo-table-wrap">
                <table class="bo-table">
                    <thead><tr><th>ชื่อ</th><th>อีเมล</th><th>เบอร์โทร</th><th>ตำแหน่ง</th><th>จัดการ</th></tr></thead>
                    <tbody>
                    <?php foreach ($staffs as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                            <td><?= htmlspecialchars($s['phone'] ?: '-') ?></td>
                            <td><span class="bo-status"><?= htmlspecialchars($role_thai[$s['role']]) ?></span></td>
                            <td>
                                <?php if ($s['id'] == $_SESSION['user_id']): ?>
                                    <span class="bo-muted">บัญชีของคุณ</span>
                                <?php else: ?>
                                    <a class="bo-button bo-button-danger" href="delete_staff.php?id=<?= $s['id'] ?>" onclick="return confirm('ยืนยันลบบัญชี <?= htmlspecialchars($s['full_name'], ENT_QUOTES) ?> ?');">ลบ</a>
                                <?php endif; ?>
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

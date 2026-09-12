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
    <title>จัดการพนักงาน</title>
</head>
<body>
    <a href="dashboard.php">&laquo; กลับหน้าหลัก</a>
    <h2>เพิ่มพนักงานใหม่</h2>

    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['added'])): ?>
        <p style="color:green;">เพิ่มบัญชีสำเร็จ</p>
    <?php endif; ?>

    <form method="POST">
        ชื่อ-นามสกุล: <input type="text" name="full_name" required><br><br>
        อีเมล: <input type="email" name="email" required><br><br>
        เบอร์โทร: <input type="text" name="phone"><br><br>
        รหัสผ่าน: <input type="password" name="password" required minlength="6"><br><br>
        ตำแหน่ง:
        <select name="role" required>
            <option value="staff">พนักงาน</option>
            <option value="admin">ผู้ดูแลระบบ</option>
        </select><br><br>
        <button type="submit" name="add_staff" value="1">เพิ่มบัญชี</button>
    </form>

    <h2>บัญชีพนักงานทั้งหมด (<?= count($staffs) ?> คน)</h2>
    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>ชื่อ</th><th>อีเมล</th><th>เบอร์โทร</th><th>ตำแหน่ง</th><th>จัดการ</th>
        </tr>
        <?php foreach ($staffs as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td><?= htmlspecialchars($s['phone']) ?></td>
                <td><?= $role_thai[$s['role']] ?></td>
                <td>
                    <?php if ($s['id'] == $_SESSION['user_id']): ?>
                        <em>บัญชีของคุณ</em>
                    <?php else: ?>
                        <a href="delete_staff.php?id=<?= $s['id'] ?>"
                           onclick="return confirm('ยืนยันลบบัญชี <?= htmlspecialchars($s['full_name']) ?> ?');">ลบ</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
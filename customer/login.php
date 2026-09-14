<?php
session_start();
require_once '../config/db_connect.php';

$error = "";
$success = isset($_GET['registered']) ? "สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ" : "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'customer') {
            header("Location: dashboard.php");
        } elseif ($user['role'] == 'staff') {
            header("Location: ../staff/dashboard.php");
        } else {
            header("Location: ../admin/dashboard.php");
        }
        exit();
    } else {
        $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เข้าสู่ระบบ — Paveena Hotel</title>
    <link rel="stylesheet" href="../assets/site.css">
</head>
<body class="auth-body">
<main class="auth-shell">
    <section class="auth-visual">
        <a class="brand auth-brand" href="../index.php">
            <span class="brand-mark">PH</span>
            <span>PAVEENA HOTEL</span>
        </a>
        <div class="auth-visual-copy">
            <p class="eyebrow">Your memorable stay</p>
            <h1>ยินดีต้อนรับ<br>กลับมาอีกครั้ง</h1>
            <p>เข้าสู่ระบบเพื่อค้นหาห้องพัก จัดการการจอง และติดตามสถานะการชำระเงินได้ในที่เดียว</p>
        </div>
        <div class="auth-highlights">
            <span>เช็กห้องว่างแบบทันที</span>
            <span>จัดการการจองได้ง่าย</span>
        </div>
    </section>

    <section class="auth-panel">
        <div class="auth-panel-inner">
            <p class="eyebrow">Member access</p>
            <h2>เข้าสู่ระบบ</h2>
            <p class="auth-intro">กรอกอีเมลและรหัสผ่านเพื่อเข้าสู่บัญชีของคุณ</p>

            <?php if ($error): ?>
                <div class="notice notice-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="notice notice-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST">
                <div class="form-group">
                    <label for="email">อีเมล</label>
                    <input type="email" id="email" name="email" placeholder="example@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่าน" required autocomplete="current-password">
                </div>
                <button class="button button-aqua auth-submit" type="submit">เข้าสู่ระบบ</button>
            </form>

            <p class="auth-switch">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
            <a class="auth-home-link" href="../index.php">← กลับหน้าแรก</a>
        </div>
    </section>
</main>
</body>
</html>

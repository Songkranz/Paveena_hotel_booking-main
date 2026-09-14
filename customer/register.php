<?php
require_once '../config/db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "รหัสผ่านไม่ตรงกัน";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $error = "อีเมลนี้ถูกใช้สมัครแล้ว";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->execute([$full_name, $email, $phone, $hashed_password]);

            header("Location: login.php?registered=1");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>สมัครสมาชิก — Paveena Hotel</title>
    <link rel="stylesheet" href="../assets/site.css">
</head>
<body class="auth-body">
<main class="auth-shell auth-shell-register">
    <section class="auth-visual">
        <a class="brand auth-brand" href="../index.php">
            <span class="brand-mark">PH</span>
            <span>PAVEENA HOTEL</span>
        </a>
        <div class="auth-visual-copy">
            <p class="eyebrow">Begin your journey</p>
            <h1>เริ่มต้นวันพักผ่อน<br>ในแบบของคุณ</h1>
            <p>สร้างบัญชีเพื่อจองห้องพัก ดูรายการจอง และแนบหลักฐานการชำระเงินได้สะดวกยิ่งขึ้น</p>
        </div>
        <div class="auth-highlights">
            <span>สมัครง่าย ไม่กี่ขั้นตอน</span>
            <span>ข้อมูลการจองอยู่ในบัญชีเดียว</span>
        </div>
    </section>

    <section class="auth-panel">
        <div class="auth-panel-inner">
            <p class="eyebrow">Create account</p>
            <h2>สมัครสมาชิก</h2>
            <p class="auth-intro">กรอกข้อมูลด้านล่างเพื่อสร้างบัญชีลูกค้า</p>

            <?php if ($error): ?>
                <div class="notice notice-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST">
                <div class="form-group">
                    <label for="full_name">ชื่อ-นามสกุล</label>
                    <input type="text" id="full_name" name="full_name" placeholder="ชื่อ นามสกุล"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required autocomplete="name">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">อีเมล</label>
                        <input type="email" id="email" name="email" placeholder="example@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label for="phone">เบอร์โทร</label>
                        <input type="tel" id="phone" name="phone" placeholder="08x-xxx-xxxx"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" autocomplete="tel">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">รหัสผ่าน</label>
                        <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่าน" required autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="กรอกอีกครั้ง" required autocomplete="new-password">
                    </div>
                </div>
                <button class="button button-aqua auth-submit" type="submit">สร้างบัญชี</button>
            </form>

            <p class="auth-switch">มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
            <a class="auth-home-link" href="../index.php">← กลับหน้าแรก</a>
        </div>
    </section>
</main>
</body>
</html>

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <!-- เรียกใช้ฟอนต์ Prompt จาก Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* กำหนดจังหวะแอนิเมชันแบบ Apple (นุ่มและหน่วงตอนปลาย) */
            --apple-ease: cubic-bezier(0.16, 1, 0.3, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Prompt', sans-serif;
        }
        
        body {
            background: linear-gradient(rgba(76, 29, 149, 0.75), rgba(46, 16, 101, 0.85)), 
                        url('image_318dd3.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* แอนิเมชันตอนโหลดการ์ด: เลื่อนขึ้น + ค่อยๆ ชัดขึ้นจากการเบลอ (ไม่มีการย่อ/ขยาย) */
        @keyframes smoothAppear {
            0% {
                opacity: 0;
                transform: translateY(40px); /* เริ่มต้นจากตำแหน่งที่ต่ำกว่าปกติ 40px */
                filter: blur(12px); /* เริ่มต้นด้วยความเบลอ */
            }
            100% {
                opacity: 1;
                transform: translateY(0); /* กลับสู่ตำแหน่งปกติ */
                filter: blur(0); /* ชัดเจน 100% */
            }
        }

        .login-card {
            background-color: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            width: 100%;
            max-width: 400px;
            padding: 40px 30px;
            border-radius: 20px; 
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
            
            /* เรียกใช้แอนิเมชัน (เพิ่มเวลาให้เห็นการเบลอชัดขึ้นเป็น 1.2s) */
            animation: smoothAppear 1.2s var(--apple-ease) forwards;
        }

        .login-card h2 {
            text-align: center;
            color: #4c1d95; 
            margin-bottom: 25px;
            font-size: 26px;
            font-weight: 600;
        }

        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            font-weight: 500;
            animation: smoothAppear 1s var(--apple-ease) forwards;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #047857;
            border: 1px solid #6ee7b7;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            background-color: #f9fafb;
            transition: border-color 0.4s var(--apple-ease), box-shadow 0.4s var(--apple-ease), background-color 0.4s var(--apple-ease);
        }

        .form-group input:focus {
            outline: none;
            border-color: #8b5cf6; 
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
            background-color: #ffffff;
        }

        .btn-submit {
            width: 100%;
            background-color: #f59e0b; 
            color: #111827; 
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2);
            transition: transform 0.4s var(--apple-ease), background-color 0.4s var(--apple-ease), box-shadow 0.4s var(--apple-ease);
        }

        .btn-submit:hover {
            background-color: #fbbf24; 
            transform: translateY(-2px); 
            box-shadow: 0 8px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-submit:active {
            transform: scale(0.96) translateY(0);
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
        }

        .register-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #4b5563;
        }

        .register-link a {
            color: #6d28d9; 
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s var(--apple-ease);
        }

        .register-link a:hover {
            color: #4c1d95;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <h2>เข้าสู่ระบบ</h2>
        
        <!-- แสดงข้อความแจ้งเตือน -->
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- ฟอร์มเข้าสู่ระบบ -->
        <form method="POST">
            <div class="form-group">
                <label for="email">อีเมล</label>
                <input type="email" id="email" name="email" placeholder="example@email.com" required>
            </div>
            
            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn-submit">เข้าสู่ระบบ -></button>
        </form>

        <div class="register-link">
            ยังไม่มีบัญชีใช่หรือไม่? <a href="register.php">สมัครสมาชิกเลย</a>
        </div>
    </div>

</body>
</html>
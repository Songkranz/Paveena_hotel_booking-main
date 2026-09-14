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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก</title>
    <!-- เรียกใช้ฟอนต์ Prompt จาก Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* กำหนดจังหวะแอนิเมชันแบบ Apple */
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
            padding: 20px; /* เพิ่ม padding เผื่อหน้าจอมือถือ */
        }

        /* แอนิเมชันตอนโหลดการ์ด: เลื่อนขึ้น + ค่อยๆ ชัดขึ้นจากการเบลอ */
        @keyframes smoothAppear {
            0% {
                opacity: 0;
                transform: translateY(40px);
                filter: blur(12px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }

        .register-card {
            background-color: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            width: 100%;
            max-width: 450px; /* ขยายความกว้างขึ้นนิดหน่อยสำหรับฟอร์มสมัคร */
            padding: 40px 30px;
            border-radius: 20px; 
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
            
            /* เรียกใช้แอนิเมชัน */
            animation: smoothAppear 1.2s var(--apple-ease) forwards;
        }

        .register-card h2 {
            text-align: center;
            color: #3a1471; 
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

        .form-group {
            margin-bottom: 18px;
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
            padding: 12px 16px;
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

        /* จัดกลุ่ม input รหัสผ่านให้อยู่บรรทัดเดียวกันในหน้าจอใหญ่ */
        .password-group {
            display: flex;
            gap: 15px;
        }
        .password-group .form-group {
            flex: 1;
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
            margin-top: 15px;
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

        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #4b5563;
        }

        .login-link a {
            color: #6d28d9; 
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s var(--apple-ease);
        }

        .login-link a:hover {
            color: #4c1d95;
            text-decoration: underline;
        }

        /* รองรับหน้าจอมือถือ */
        @media (max-width: 480px) {
            .password-group {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>

    <div class="register-card">
        <h2>สมัครสมาชิก</h2>

        <!-- แสดงข้อความแจ้งเตือนเมื่อเกิดข้อผิดพลาด -->
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ฟอร์มสมัครสมาชิก -->
        <form method="POST">
            <div class="form-group">
                <label for="full_name">ชื่อ-นามสกุล</label>
                <input type="text" id="full_name" name="full_name" placeholder="ชื่อ นามสกุล" required>
            </div>
            
            <div class="form-group">
                <label for="email">อีเมล</label>
                <input type="email" id="email" name="email" placeholder="example@email.com" required>
            </div>

            <div class="form-group">
                <label for="phone">เบอร์โทร</label>
                <input type="text" id="phone" name="phone" placeholder="08x-xxx-xxxx">
            </div>
            
            <!-- จัดกลุ่มรหัสผ่านไว้คู่กันเพื่อให้ฟอร์มไม่ยาวเกินไป -->
            <div class="password-group">
                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">สมัครสมาชิก</button>
        </form>

        <div class="login-link">
            มีบัญชีอยู่แล้วใช่หรือไม่? <a href="login.php">เข้าสู่ระบบเลย</a>
        </div>
    </div>

</body>
</html>
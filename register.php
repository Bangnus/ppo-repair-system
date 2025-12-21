<?php
/**
 * Register Page
 * User registration with Tailwind CSS
 */
require_once __DIR__ . '/config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');

    // Validation
    if (empty($username) || empty($password) || empty($fullname)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (strlen($username) < 3) {
        $error = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif ($password !== $confirmPassword) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } else {
        // Check duplicate username
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetchColumn() > 0) {
            $error = 'ชื่อผู้ใช้นี้มีอยู่แล้ว กรุณาเลือกชื่อใหม่';
        } else {
            // Insert new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$username, $hashedPassword, $fullname]);

            $success = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก | IT Repair System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Kanit', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #ecfdf5 0%, #fef3c7 50%, #d1fae5 100%);
            min-height: 100vh;
        }

        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .slide-up {
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
</head>

<body class="gradient-bg flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="glass rounded-3xl shadow-2xl p-8 slide-up border border-white/50">

            <!-- Logo -->
            <div class="text-center mb-6">
                <div
                    class="w-24 h-24 mx-auto rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-xl float-animation">
                    <span class="text-5xl">📝</span>
                </div>
            </div>

            <!-- Title -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-emerald-700">สมัครสมาชิก</h1>
                <p class="text-gray-500 text-sm mt-1">สร้างบัญชีเพื่อแจ้งซ่อมอุปกรณ์</p>
            </div>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div
                    class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-600 text-sm flex items-center gap-2">
                    <span>✅</span>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
                <div class="text-center">
                    <a href="login.php"
                        class="inline-block px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                        ไปหน้าเข้าสู่ระบบ
                    </a>
                </div>
            <?php else: ?>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div
                        class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm flex items-center gap-2">
                        <span>❌</span>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" class="space-y-4">
                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">👤 ชื่อผู้ใช้ <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="username" required minlength="3"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none transition-all bg-white/70"
                            placeholder="อย่างน้อย 3 ตัวอักษร" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>

                    <!-- Fullname -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📛 ชื่อ-นามสกุล <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="fullname" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none transition-all bg-white/70"
                            placeholder="ชื่อจริง นามสกุล" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">🔑 รหัสผ่าน <span
                                class="text-red-500">*</span></label>
                        <input type="password" name="password" required minlength="6"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none transition-all bg-white/70"
                            placeholder="อย่างน้อย 6 ตัวอักษร">
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">🔑 ยืนยันรหัสผ่าน <span
                                class="text-red-500">*</span></label>
                        <input type="password" name="confirm_password" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none transition-all bg-white/70"
                            placeholder="กรอกรหัสผ่านอีกครั้ง">
                    </div>

                    <!-- Submit -->
                    <button type="submit"
                        class="w-full py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                        สมัครสมาชิก
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-gray-500 text-sm">
                        มีบัญชีอยู่แล้ว?
                        <a href="login.php" class="text-emerald-600 font-medium hover:underline">เข้าสู่ระบบ</a>
                    </p>
                </div>

            <?php endif; ?>

            <!-- Footer -->
            <p class="text-center text-xs text-gray-400 mt-6">
                © <?= date('Y') ?> IT Repair System
            </p>
        </div>
    </div>
</body>

</html>
<?php
/**
 * Header Component with Admin Sidebar
 * Organized menu categories
 */
if (!isset($pdo)) {
    require_once __DIR__ . '/auth.php';
}
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);

// Determine base path
$isInAdminFolder = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$basePath = $isInAdminFolder ? '../' : '';
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'ระบบแจ้งซ่อม' ?> | IT Repair System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 300: '#6ee7b7', 400: '#34d399', 500: '#10b981', 600: '#059669', 700: '#047857', 800: '#065f46', 900: '#064e3b' }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Kanit', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #ecfdf5 0%, #fef3c7 50%, #d1fae5 100%);
        }

        .sidebar-gradient {
            background: linear-gradient(180deg, #064e3b 0%, #047857 100%);
        }
    </style>
</head>

<body class="gradient-bg min-h-screen">

    <?php if (isLoggedIn() && isStaff()): ?>
        <?php $roleInfo = getRoleInfo($_SESSION['role']); ?>
        <!-- ========== STAFF LAYOUT (Admin/Manager/Supervisor) ========== -->
        <div class="flex">
            <!-- Admin Sidebar -->
            <aside class="sidebar-gradient w-64 min-h-screen fixed left-0 top-0 shadow-2xl z-40 hidden lg:block">
                <!-- Logo -->
                <div class="p-5 border-b border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl overflow-hidden shadow-lg border-2 border-white/30 bg-white">
                            <img src="<?= $basePath ?>images/ppo2.jpg" alt="Logo" class="w-full h-full object-cover"
                                onerror="this.parentElement.innerHTML='🔧'">
                        </div>
                        <div>
                            <h1 class="text-base font-bold text-white">ระบบแจ้งซ่อม</h1>
                            <p class="text-xs text-emerald-200"><?= $roleInfo['icon'] ?>     <?= $roleInfo['label'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- User Info -->
                <div class="px-4 py-3 border-b border-white/10">
                    <div class="flex items-center gap-2 px-2 py-2 bg-white/10 rounded-lg">
                        <div
                            class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white text-sm font-bold">
                            <?= mb_substr($currentUser['fullname'] ?? '', 0, 1) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate"><?= e($currentUser['fullname'] ?? '') ?></p>
                            <p class="text-xs text-emerald-300"><?= $roleInfo['icon'] ?>     <?= $roleInfo['label'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Menu -->
                <nav class="p-3 space-y-1 overflow-y-auto" style="max-height: calc(100vh - 220px);">

                    <!-- หน้าหลัก -->
                    <a href="<?= $basePath ?>admin/dashboard.php"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= $currentPage === 'dashboard.php' ? 'bg-white/20 text-white' : 'text-emerald-100 hover:bg-white/10' ?>">
                        <span class="text-lg">📊</span><span class="text-sm font-medium">แดชบอร์ด</span>
                    </a>

                    <!-- แจ้งซ่อมของฉัน -->
                    <div class="pt-3">
                        <p class="px-3 py-1 text-[10px] text-emerald-400 font-semibold uppercase tracking-wider">
                            แจ้งซ่อมของฉัน</p>
                    </div>
                    <a href="<?= $basePath ?>create_repair.php"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= $currentPage === 'create_repair.php' ? 'bg-white/20 text-white' : 'text-emerald-100 hover:bg-white/10' ?>">
                        <span class="text-lg">➕</span><span class="text-sm font-medium">แจ้งซ่อมใหม่</span>
                    </a>
                    <a href="<?= $basePath ?>my_repairs.php"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= $currentPage === 'my_repairs.php' ? 'bg-white/20 text-white' : 'text-emerald-100 hover:bg-white/10' ?>">
                        <span class="text-lg">📋</span><span class="text-sm font-medium">รายการของฉัน</span>
                    </a>

                    <!-- จัดการรายการซ่อม -->
                    <div class="pt-3">
                        <p class="px-3 py-1 text-[10px] text-emerald-400 font-semibold uppercase tracking-wider">
                            จัดการรายการซ่อม</p>
                    </div>
                    <a href="<?= $basePath ?>admin/all_repairs.php"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= $currentPage === 'all_repairs.php' ? 'bg-white/20 text-white' : 'text-emerald-100 hover:bg-white/10' ?>">
                        <span class="text-lg">📑</span><span class="text-sm font-medium">รายการทั้งหมด</span>
                    </a>


                    <!-- จัดการระบบ (Admin Only) -->
                    <?php if (isAdmin()): ?>
                        <div class="pt-3">
                            <p class="px-3 py-1 text-[10px] text-emerald-400 font-semibold uppercase tracking-wider">จัดการระบบ
                            </p>
                        </div>
                        <a href="<?= $basePath ?>admin/manage_users.php"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= $currentPage === 'manage_users.php' ? 'bg-white/20 text-white' : 'text-emerald-100 hover:bg-white/10' ?>">
                            <span class="text-lg">👥</span><span class="text-sm font-medium">จัดการผู้ใช้</span>
                        </a>
                        <a href="<?= $basePath ?>admin/manage_departments.php"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= $currentPage === 'manage_departments.php' ? 'bg-white/20 text-white' : 'text-emerald-100 hover:bg-white/10' ?>">
                            <span class="text-lg">🏢</span><span class="text-sm font-medium">จัดการแผนก</span>
                        </a>
                        <a href="<?= $basePath ?>admin/manage_devices.php"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= $currentPage === 'manage_devices.php' ? 'bg-white/20 text-white' : 'text-emerald-100 hover:bg-white/10' ?>">
                            <span class="text-lg">💻</span><span class="text-sm font-medium">จัดการอุปกรณ์</span>
                        </a>
                    <?php endif; ?>
                </nav>

                <!-- Logout -->
                <div class="absolute bottom-0 left-0 right-0 p-3 border-t border-white/10">
                    <a href="<?= $basePath ?>logout.php"
                        class="flex items-center justify-center gap-2 w-full py-2.5 bg-red-500/20 hover:bg-red-500/30 text-red-200 rounded-lg transition-all text-sm">
                        <span>🚪</span><span class="font-medium">ออกจากระบบ</span>
                    </a>
                </div>
            </aside>

            <!-- Mobile Header for Admin -->
            <div class="lg:hidden fixed top-0 left-0 right-0 z-50">
                <nav class="glass shadow-lg border-b border-white/20">
                    <div class="px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button onclick="toggleMobileMenu()" class="p-2 rounded-lg bg-emerald-500 text-white">
                                <span id="menuIcon">☰</span>
                            </button>
                            <span class="font-bold text-emerald-700">ระบบแจ้งซ่อม</span>
                        </div>
                        <a href="<?= $basePath ?>logout.php"
                            class="px-3 py-1.5 bg-red-500 text-white text-sm rounded-lg">ออก</a>
                    </div>
                </nav>
                <div id="mobileMenu" class="hidden sidebar-gradient shadow-xl max-h-[80vh] overflow-y-auto">
                    <div class="p-4 space-y-1">
                        <a href="<?= $basePath ?>admin/dashboard.php"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-white hover:bg-white/10">📊
                            แดชบอร์ด</a>
                        <p class="px-4 pt-3 text-xs text-emerald-400 font-semibold">แจ้งซ่อมของฉัน</p>
                        <a href="<?= $basePath ?>create_repair.php"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-white hover:bg-white/10">➕
                            แจ้งซ่อมใหม่</a>
                        <a href="<?= $basePath ?>my_repairs.php"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-white hover:bg-white/10">📋
                            รายการของฉัน</a>
                        <p class="px-4 pt-3 text-xs text-emerald-400 font-semibold">จัดการรายการซ่อม</p>
                        <a href="<?= $basePath ?>admin/all_repairs.php"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-white hover:bg-white/10">📑
                            รายการทั้งหมด</a>
                        <?php if (isAdmin()): ?>
                            <p class="px-4 pt-3 text-xs text-emerald-400 font-semibold">จัดการระบบ</p>
                            <a href="<?= $basePath ?>admin/manage_users.php"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl text-white hover:bg-white/10">👥
                                จัดการผู้ใช้</a>
                            <a href="<?= $basePath ?>admin/manage_departments.php"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl text-white hover:bg-white/10">🏢
                                จัดการแผนก</a>
                            <a href="<?= $basePath ?>admin/manage_devices.php"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl text-white hover:bg-white/10">💻
                                จัดการอุปกรณ์</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Main Content Area (Admin) -->
            <main class="lg:ml-64 flex-1 min-h-screen lg:pt-0 pt-16">
                <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 py-8">

                <?php elseif (isLoggedIn()): ?>
                    <!-- ========== USER LAYOUT ========== -->
                    <div>
                        <!-- User Navigation (Top Navbar) -->
                        <nav class="glass sticky top-0 z-50 shadow-lg border-b border-white/20">
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                                <div class="flex justify-between h-16">
                                    <!-- Logo -->
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-full overflow-hidden shadow-lg border-2 border-primary-200">
                                            <img src="images/ppo2.jpg" alt="Logo" class="w-full h-full object-cover"
                                                onerror="this.parentElement.innerHTML='🔧'">
                                        </div>
                                        <div>
                                            <h1 class="text-lg font-bold text-primary-700">ระบบแจ้งซ่อม</h1>
                                            <p class="text-xs text-gray-500 -mt-1">IT Repair System</p>
                                        </div>
                                    </div>

                                    <!-- Menu -->
                                    <div class="hidden md:flex items-center gap-1">
                                        <a href="dashboard.php"
                                            class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $currentPage === 'dashboard.php' ? 'bg-primary-500 text-white shadow-md' : 'text-gray-600 hover:bg-primary-100' ?>">📊
                                            แดชบอร์ด</a>
                                        <a href="create_repair.php"
                                            class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $currentPage === 'create_repair.php' ? 'bg-primary-500 text-white shadow-md' : 'text-gray-600 hover:bg-primary-100' ?>">➕
                                            แจ้งซ่อม</a>
                                        <a href="my_repairs.php"
                                            class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $currentPage === 'my_repairs.php' ? 'bg-primary-500 text-white shadow-md' : 'text-gray-600 hover:bg-primary-100' ?>">📋
                                            รายการของฉัน</a>
                                    </div>

                                    <!-- User Info -->
                                    <div class="flex items-center gap-3">
                                        <div class="text-right hidden sm:block">
                                            <p class="text-sm font-semibold text-gray-700">
                                                <?= e($currentUser['fullname'] ?? '') ?>
                                            </p>
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">👤
                                                User</span>
                                        </div>
                                        <a href="logout.php"
                                            class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition-all shadow-md">ออกจากระบบ</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Menu for User -->
                            <div class="md:hidden border-t border-white/20 px-4 py-2 flex flex-wrap gap-2">
                                <a href="dashboard.php"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium <?= $currentPage === 'dashboard.php' ? 'bg-primary-500 text-white' : 'bg-white/50 text-gray-600' ?>">📊
                                    แดชบอร์ด</a>
                                <a href="create_repair.php"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium <?= $currentPage === 'create_repair.php' ? 'bg-primary-500 text-white' : 'bg-white/50 text-gray-600' ?>">➕
                                    แจ้งซ่อม</a>
                                <a href="my_repairs.php"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium <?= $currentPage === 'my_repairs.php' ? 'bg-primary-500 text-white' : 'bg-white/50 text-gray-600' ?>">📋
                                    รายการของฉัน</a>
                            </div>
                        </nav>

                        <!-- Main Content Area (User) -->
                        <main>
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                            <?php endif; ?>

                            <script>
                                function toggleMobileMenu() {
                                    const menu = document.getElementById('mobileMenu');
                                    const icon = document.getElementById('menuIcon');
                                    if (menu && icon) {
                                        menu.classList.toggle('hidden');
                                        icon.textContent = menu.classList.contains('hidden') ? '☰' : '✕';
                                    }
                                }
                            </script>
<?php
/**
 * User Dashboard
 * Main page for regular users after login
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

// Redirect staff to admin dashboard
if (isStaff()) {
    header('Location: admin/dashboard.php');
    exit();
}

$pageTitle = 'แดชบอร์ด';
$userId = $_SESSION['user_id'];

// Count repairs by status for this user
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM repairs WHERE user_id = ? GROUP BY status");
$stmt->execute([$userId]);
$stats = ['pending' => 0, 'in_progress' => 0, 'completed' => 0];
while ($row = $stmt->fetch()) {
    $stats[$row['status']] = $row['count'];
}
$total = array_sum($stats);

// Recent repairs for this user
$stmt = $pdo->prepare("
    SELECT r.*, u.fullname, d.name as department_name 
    FROM repairs r 
    JOIN users u ON r.user_id = u.id 
    JOIN departments d ON r.department_id = d.id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC LIMIT 5
");
$stmt->execute([$userId]);
$recentRepairs = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Welcome Section -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">
        สวัสดี, <?= e($currentUser['fullname']) ?> 👋
    </h1>
    <p class="text-gray-500 mt-1">ยินดีต้อนรับสู่ระบบแจ้งซ่อมอุปกรณ์ IT</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <!-- Total -->
    <div class="glass rounded-2xl p-6 border border-white/50 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">รายการทั้งหมด</p>
                <p class="text-3xl font-bold text-gray-800"><?= $total ?></p>
            </div>
            <div
                class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                <span class="text-2xl">📋</span>
            </div>
        </div>
    </div>

    <!-- Pending -->
    <div class="glass rounded-2xl p-6 border border-white/50 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">รอดำเนินการ</p>
                <p class="text-3xl font-bold text-amber-600"><?= $stats['pending'] ?></p>
            </div>
            <div
                class="w-14 h-14 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                <span class="text-2xl">⏳</span>
            </div>
        </div>
    </div>

    <!-- In Progress -->
    <div class="glass rounded-2xl p-6 border border-white/50 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">กำลังดำเนินการ</p>
                <p class="text-3xl font-bold text-blue-600"><?= $stats['in_progress'] ?></p>
            </div>
            <div
                class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                <span class="text-2xl">🔧</span>
            </div>
        </div>
    </div>

    <!-- Completed -->
    <div class="glass rounded-2xl p-6 border border-white/50 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">เสร็จสิ้น</p>
                <p class="text-3xl font-bold text-emerald-600"><?= $stats['completed'] ?></p>
            </div>
            <div
                class="w-14 h-14 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center">
                <span class="text-2xl">✅</span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <a href="create_repair.php"
        class="glass rounded-2xl p-6 border border-white/50 shadow-lg hover:shadow-xl transition-all group">
        <div class="flex items-center gap-4">
            <div
                class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                <span class="text-3xl">➕</span>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">แจ้งซ่อมใหม่</h3>
                <p class="text-sm text-gray-500">เพิ่มรายการแจ้งซ่อมอุปกรณ์</p>
            </div>
        </div>
    </a>

    <a href="my_repairs.php"
        class="glass rounded-2xl p-6 border border-white/50 shadow-lg hover:shadow-xl transition-all group">
        <div class="flex items-center gap-4">
            <div
                class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                <span class="text-3xl">📋</span>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">รายการของฉัน</h3>
                <p class="text-sm text-gray-500">ดูสถานะการแจ้งซ่อมทั้งหมด</p>
            </div>
        </div>
    </a>
</div>

<!-- Recent Repairs -->
<div class="glass rounded-2xl border border-white/50 shadow-lg overflow-hidden">
    <div class="p-6 border-b border-gray-100">
        <h2 class="text-xl font-bold text-gray-800">📜 รายการล่าสุด</h2>
    </div>

    <?php if (empty($recentRepairs)): ?>
        <div class="p-12 text-center">
            <span class="text-6xl">📭</span>
            <p class="mt-4 text-gray-500">ยังไม่มีรายการแจ้งซ่อม</p>
            <a href="create_repair.php"
                class="inline-block mt-4 px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                ➕ แจ้งซ่อมเลย
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">อุปกรณ์</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ปัญหา</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">วันที่</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    $statusColors = [
                        'pending' => 'bg-amber-100 text-amber-700',
                        'in_progress' => 'bg-blue-100 text-blue-700',
                        'completed' => 'bg-emerald-100 text-emerald-700'
                    ];
                    $statusLabels = [
                        'pending' => '⏳ รอดำเนินการ',
                        'in_progress' => '🔧 กำลังดำเนินการ',
                        'completed' => '✅ เสร็จสิ้น'
                    ];
                    foreach ($recentRepairs as $repair): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-gray-700 font-medium"><?= e($repair['device_type']) ?></td>
                            <td class="px-6 py-4 text-gray-600 max-w-xs truncate"><?= e($repair['problem']) ?></td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-medium <?= $statusColors[$repair['status']] ?>">
                                    <?= $statusLabels[$repair['status']] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= thaiDate($repair['created_at'], true) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
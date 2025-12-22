<?php
/**
 * Staff Dashboard (Admin, Manager, Supervisor)
 * Balanced clean design
 */
require_once __DIR__ . '/../includes/auth.php';
requireStaff();

$pageTitle = 'แดชบอร์ด';

// Get statistics
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM repairs GROUP BY status");
$stats = ['pending' => 0, 'in_progress' => 0, 'completed' => 0];
while ($row = $stmt->fetch()) {
    $stats[$row['status']] = $row['count'];
}
$total = array_sum($stats);

// Recent repairs
$recentRepairs = $pdo->query("
    SELECT r.*, u.fullname, d.name as department_name 
    FROM repairs r 
    JOIN users u ON r.user_id = u.id 
    JOIN departments d ON r.department_id = d.id 
    ORDER BY r.created_at DESC LIMIT 8
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">📊 แดชบอร์ด</h1>
        <p class="text-gray-500">ภาพรวมระบบแจ้งซ่อม</p>
    </div>
    <a href="all_repairs.php"
        class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-medium rounded-xl shadow-md hover:shadow-lg transition-all text-sm">
        📑 ดูรายการทั้งหมด
    </a>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="glass rounded-2xl p-5 border border-white/50 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">รายการทั้งหมด</p>
                <p class="text-3xl font-bold text-gray-800 mt-1"><?= $total ?></p>
            </div>
            <div
                class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                <span class="text-xl">📋</span>
            </div>
        </div>
    </div>
    <div class="glass rounded-2xl p-5 border border-white/50 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">รอดำเนินการ</p>
                <p class="text-3xl font-bold text-amber-600 mt-1"><?= $stats['pending'] ?></p>
            </div>
            <div
                class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                <span class="text-xl">⏳</span>
            </div>
        </div>
    </div>
    <div class="glass rounded-2xl p-5 border border-white/50 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">กำลังซ่อม</p>
                <p class="text-3xl font-bold text-blue-600 mt-1"><?= $stats['in_progress'] ?></p>
            </div>
            <div
                class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                <span class="text-xl">🔧</span>
            </div>
        </div>
    </div>
    <div class="glass rounded-2xl p-5 border border-white/50 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">เสร็จสิ้น</p>
                <p class="text-3xl font-bold text-emerald-600 mt-1"><?= $stats['completed'] ?></p>
            </div>
            <div
                class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center">
                <span class="text-xl">✅</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Repairs Table -->
<div class="glass rounded-2xl border border-white/50 shadow-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">📜 รายการล่าสุด</h2>
        <span class="text-sm text-gray-400"><?= count($recentRepairs) ?> รายการ</span>
    </div>

    <?php if (empty($recentRepairs)): ?>
        <div class="p-12 text-center">
            <span class="text-5xl">📭</span>
            <p class="mt-3 text-gray-500">ยังไม่มีรายการแจ้งซ่อม</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ผู้แจ้ง</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">อุปกรณ์</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">
                            ปัญหา</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">สถานะ</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">
                            วันที่</th>
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
                        'pending' => '⏳ รอ',
                        'in_progress' => '🔧 ซ่อม',
                        'completed' => '✅ เสร็จ'
                    ];
                    foreach ($recentRepairs as $repair): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-3">
                                <div class="font-medium text-gray-800"><?= e($repair['fullname']) ?></div>
                                <div class="text-xs text-gray-500"><?= e($repair['department_name']) ?></div>
                            </td>
                            <td class="px-5 py-3 text-gray-700"><?= e($repair['device_type']) ?></td>
                            <td class="px-5 py-3 text-gray-600 max-w-[200px] truncate hidden sm:table-cell">
                                <?= e($repair['problem']) ?>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="px-2.5 py-1 rounded-full text-xs font-medium <?= $statusColors[$repair['status']] ?>">
                                    <?= $statusLabels[$repair['status']] ?>
                                </span>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-500 hidden md:table-cell">
                                <?= thaiDate($repair['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
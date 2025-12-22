<?php
/**
 * My Repairs
 * View user's own repair requests
 */
$pageTitle = 'รายการของฉัน';
require_once __DIR__ . '/includes/header.php';
requireLogin();

// Get user's repairs
$stmt = $pdo->prepare("
    SELECT r.*, d.name as department_name 
    FROM repairs r 
    JOIN departments d ON r.department_id = d.id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$repairs = $stmt->fetchAll();

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
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">📋 รายการของฉัน</h1>
        <p class="text-gray-500">รายการแจ้งซ่อมทั้งหมดของคุณ</p>
    </div>
    <a href="create_repair.php"
        class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold rounded-xl shadow-lg">
        ➕ แจ้งซ่อมใหม่
    </a>
</div>

<?php if (empty($repairs)): ?>
    <div class="glass rounded-2xl border border-white/50 shadow-lg p-12 text-center">
        <span class="text-6xl">📭</span>
        <h2 class="text-xl font-semibold text-gray-700 mt-4">ยังไม่มีรายการแจ้งซ่อม</h2>
        <p class="text-gray-500 mt-2">คลิกปุ่มด้านบนเพื่อเริ่มแจ้งซ่อมอุปกรณ์</p>
    </div>
<?php else: ?>
    <div class="glass rounded-2xl border border-white/50 shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-emerald-600 to-emerald-700 text-white">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase">ID</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase">อุปกรณ์</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase hidden sm:table-cell">ปัญหา</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase">แผนก</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase">สถานะ</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase hidden md:table-cell">วันที่</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($repairs as $repair): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-4 font-mono text-sm text-gray-500">
                                #<?= str_pad($repair['id'], 4, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-800"><?= e($repair['device_type']) ?></div>
                                <?php if ($repair['device_detail']): ?>
                                    <div class="text-xs text-gray-500"><?= e($repair['device_detail']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 max-w-xs hidden sm:table-cell">
                                <p class="text-sm text-gray-600 truncate"><?= e($repair['problem']) ?></p>
                            </td>
                            <td class="px-4 py-4 text-gray-600"><?= e($repair['department_name']) ?></td>
                            <td class="px-4 py-4">
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-medium <?= $statusColors[$repair['status']] ?>">
                                    <?= $statusLabels[$repair['status']] ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500 hidden md:table-cell">
                                <?= thaiDate($repair['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
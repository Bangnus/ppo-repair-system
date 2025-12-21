<?php
/**
 * All Repairs (Admin Only)
 * View all repairs and approve status changes
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Handle approval - BEFORE including header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve'])) {
    $repairId = (int) $_POST['repair_id'];
    $newStatus = $_POST['new_status'];
    $adminId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM approvals WHERE repair_id = ? AND admin_id = ? AND new_status = ?");
    $stmt->execute([$repairId, $adminId, $newStatus]);
    $alreadyApproved = $stmt->fetchColumn() > 0;

    if (!$alreadyApproved) {
        $stmt = $pdo->prepare("INSERT INTO approvals (repair_id, admin_id, new_status) VALUES (?, ?, ?)");
        $stmt->execute([$repairId, $adminId, $newStatus]);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM approvals WHERE repair_id = ? AND new_status = ?");
        $stmt->execute([$repairId, $newStatus]);
        $approvalCount = $stmt->fetchColumn();

        if ($approvalCount >= 2) {
            $stmt = $pdo->prepare("UPDATE repairs SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $repairId]);

            $stmt = $pdo->prepare("DELETE FROM approvals WHERE repair_id = ? AND new_status = ?");
            $stmt->execute([$repairId, $newStatus]);

            $_SESSION['toast'] = ['message' => 'อัปเดตสถานะเรียบร้อยแล้ว (2/2 admin ยืนยัน)', 'type' => 'success'];
        } else {
            $_SESSION['toast'] = ['message' => 'ยืนยันแล้ว 1/2 รอ Admin อีก 1 คน', 'type' => 'warning'];
        }
    } else {
        $_SESSION['toast'] = ['message' => 'คุณได้ยืนยันรายการนี้ไปแล้ว', 'type' => 'error'];
    }

    header('Location: all_repairs.php');
    exit();
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $repairId = (int) $_POST['repair_id'];
    $stmt = $pdo->prepare("DELETE FROM repairs WHERE id = ?");
    $stmt->execute([$repairId]);

    $_SESSION['toast'] = ['message' => 'ลบรายการเรียบร้อยแล้ว', 'type' => 'success'];
    header('Location: all_repairs.php');
    exit();
}

// NOW include header
$pageTitle = 'รายการทั้งหมด';
require_once __DIR__ . '/../includes/header.php';

// Get all repairs with approval counts
$repairs = $pdo->query("
    SELECT r.*, u.fullname, d.name as department_name,
           (SELECT COUNT(*) FROM approvals a WHERE a.repair_id = r.id AND a.new_status = 'in_progress') as in_progress_approvals,
           (SELECT COUNT(*) FROM approvals a WHERE a.repair_id = r.id AND a.new_status = 'completed') as completed_approvals
    FROM repairs r 
    JOIN users u ON r.user_id = u.id 
    JOIN departments d ON r.department_id = d.id 
    ORDER BY 
        CASE r.status 
            WHEN 'pending' THEN 1 
            WHEN 'in_progress' THEN 2 
            ELSE 3 
        END,
        r.created_at DESC
")->fetchAll();

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
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">📑 รายการแจ้งซ่อมทั้งหมด</h1>
    <p class="text-gray-500">จัดการและยืนยันการเปลี่ยนสถานะ (ต้องการ Admin 2 คนยืนยัน)</p>
</div>

<!-- Info Card -->
<div class="glass rounded-xl border border-amber-200 bg-amber-50/50 p-4 mb-6 flex items-center gap-3">
    <span class="text-2xl">💡</span>
    <div>
        <p class="font-medium text-amber-800">ระบบยืนยัน 2 Admin</p>
        <p class="text-sm text-amber-600">การเปลี่ยนสถานะต้องได้รับการยืนยันจาก Admin 2 คน จึงจะมีผล</p>
    </div>
</div>

<?php if (empty($repairs)): ?>
    <div class="glass rounded-2xl border border-white/50 shadow-lg p-12 text-center">
        <span class="text-6xl">📭</span>
        <h2 class="text-xl font-semibold text-gray-700 mt-4">ยังไม่มีรายการแจ้งซ่อม</h2>
    </div>
<?php else: ?>
    <div class="glass rounded-2xl border border-white/50 shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-emerald-600 to-emerald-700 text-white">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase">ID</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold uppercase">รูปภาพ</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase">ผู้แจ้ง</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase">อุปกรณ์</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase">ปัญหา</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase">สถานะ</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase">รอยืนยัน</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($repairs as $repair): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-4 font-mono text-sm text-gray-500">
                                #<?= str_pad($repair['id'], 4, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <?php if ($repair['image_base64']): ?>
                                    <button onclick="showImageModal('<?= $repair['id'] ?>')" class="group relative">
                                        <img src="<?= $repair['image_base64'] ?>"
                                            class="w-16 h-16 object-cover rounded-lg shadow-md border-2 border-white hover:border-emerald-400 transition-all cursor-pointer hover:scale-105">
                                        <span
                                            class="absolute inset-0 flex items-center justify-center bg-black/30 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="text-white text-lg">🔍</span>
                                        </span>
                                    </button>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">ไม่มีรูป</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-800"><?= e($repair['fullname']) ?></div>
                                <div class="text-xs text-gray-500"><?= e($repair['department_name']) ?></div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-700"><?= e($repair['device_type']) ?></div>
                                <?php if ($repair['device_detail']): ?>
                                    <div class="text-xs text-gray-500"><?= e($repair['device_detail']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 max-w-xs">
                                <p class="text-sm text-gray-600 truncate"><?= e($repair['problem']) ?></p>
                            </td>
                            <td class="px-4 py-4">
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-medium <?= $statusColors[$repair['status']] ?>"><?= $statusLabels[$repair['status']] ?></span>
                            </td>
                            <td class="px-4 py-4 text-xs">
                                <?php if ($repair['in_progress_approvals'] > 0 && $repair['status'] === 'pending'): ?>
                                    <span class="text-blue-600">🔵 กำลังซ่อม: <?= $repair['in_progress_approvals'] ?>/2</span>
                                <?php elseif ($repair['completed_approvals'] > 0 && $repair['status'] === 'in_progress'): ?>
                                    <span class="text-emerald-600">🟢 เสร็จ: <?= $repair['completed_approvals'] ?>/2</span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <?php if ($repair['status'] === 'pending'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="repair_id" value="<?= $repair['id'] ?>">
                                            <input type="hidden" name="new_status" value="in_progress">
                                            <button name="approve"
                                                class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg">🔧
                                                รับงาน</button>
                                        </form>
                                    <?php elseif ($repair['status'] === 'in_progress'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="repair_id" value="<?= $repair['id'] ?>">
                                            <input type="hidden" name="new_status" value="completed">
                                            <button name="approve"
                                                class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium rounded-lg">✅
                                                เสร็จสิ้น</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">เสร็จแล้ว</span>
                                    <?php endif; ?>
                                    <form method="POST" class="inline" id="deleteForm<?= $repair['id'] ?>">
                                        <input type="hidden" name="repair_id" value="<?= $repair['id'] ?>">
                                        <input type="hidden" name="delete" value="1">
                                        <button type="button" onclick="confirmDelete(<?= $repair['id'] ?>)"
                                            class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-lg">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Image Preview Modal -->
<div id="imageModal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4"
    onclick="hideImageModal()">
    <div class="relative max-w-4xl max-h-[90vh]" onclick="event.stopPropagation()">
        <button onclick="hideImageModal()"
            class="absolute -top-10 right-0 text-white hover:text-red-400 text-3xl">✕</button>
        <img id="modalImage" src="" class="max-w-full max-h-[80vh] rounded-2xl shadow-2xl border-4 border-white">
    </div>
</div>

<?php foreach ($repairs as $repair): ?>
    <?php if ($repair['image_base64']): ?>
        <div id="imgData-<?= $repair['id'] ?>" class="hidden"><?= $repair['image_base64'] ?></div>
    <?php endif; ?>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function showImageModal(id) {
        const imgData = document.getElementById('imgData-' + id);
        if (imgData) {
            document.getElementById('modalImage').src = imgData.textContent;
            document.getElementById('imageModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }
    function hideImageModal() {
        document.getElementById('imageModal').classList.add('hidden');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideImageModal(); });

    function confirmDelete(id) {
        Swal.fire({
            title: 'ลบรายการ?',
            text: 'ต้องการลบรายการแจ้งซ่อม #' + String(id).padStart(4, '0') + ' หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'ตกลง',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm' + id).submit();
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
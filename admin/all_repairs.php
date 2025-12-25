<?php
/**
 * All Repairs (Staff: Admin, Manager, Supervisor)
 * View all repairs and approve status changes
 */
require_once __DIR__ . '/../includes/auth.php';
requireStaff();

// Handle approval - BEFORE including header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve'])) {
    $repairId = (int) $_POST['repair_id'];
    $newStatus = $_POST['new_status'];
    $adminId = $_SESSION['user_id'];

    // Get repair type fields (only for in_progress status)
    $repairType = isset($_POST['repair_type']) ? $_POST['repair_type'] : null;
    $repairNotes = isset($_POST['repair_notes']) ? trim($_POST['repair_notes']) : null;
    $repairDetails = isset($_POST['repair_details']) ? trim($_POST['repair_details']) : null;

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
            // Update status and repair type fields
            if ($newStatus === 'in_progress' && $repairType) {
                $stmt = $pdo->prepare("UPDATE repairs SET status = ?, repair_type = ?, repair_notes = ?, repair_details = ? WHERE id = ?");
                $stmt->execute([$newStatus, $repairType, $repairNotes, $repairDetails, $repairId]);
            } else {
                $stmt = $pdo->prepare("UPDATE repairs SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $repairId]);
            }

            $stmt = $pdo->prepare("DELETE FROM approvals WHERE repair_id = ? AND new_status = ?");
            $stmt->execute([$repairId, $newStatus]);

            $_SESSION['toast'] = ['message' => 'อัปเดตสถานะเรียบร้อยแล้ว (2/2 admin ยืนยัน)', 'type' => 'success'];
        } else {
            // Save repair type info for first approval too
            if ($newStatus === 'in_progress' && $repairType) {
                $stmt = $pdo->prepare("UPDATE repairs SET repair_type = ?, repair_notes = ?, repair_details = ? WHERE id = ?");
                $stmt->execute([$repairType, $repairNotes, $repairDetails, $repairId]);
            }
            $_SESSION['toast'] = ['message' => 'ยืนยันแล้ว รอผู้อนุมัติอีก 1 คน', 'type' => 'warning'];
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
                        <th class="px-4 py-4 text-left text-xs font-semibold uppercase">ประเภทซ่อม</th>
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
                            <td class="px-4 py-4">
                                <?php if ($repair['repair_type']): ?>
                                    <?php if ($repair['repair_type'] === 'self_repair'): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">🔧
                                            ซ่อมเอง</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">🏭
                                            ส่งซ่อม</span>
                                    <?php endif; ?>
                                    <?php if ($repair['repair_notes'] || $repair['repair_details']): ?>
                                        <button type="button" onclick="showRepairDetailsModal(<?= $repair['id'] ?>)"
                                            class="ml-1 text-gray-400 hover:text-gray-600" title="ดูรายละเอียด">📋</button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-xs">
                                <?php if ($repair['in_progress_approvals'] > 0 && $repair['status'] === 'pending'): ?>
                                    <span class="text-blue-600">🔵 ยืนยันซ่อม: <?= $repair['in_progress_approvals'] ?>/2</span>
                                <?php elseif ($repair['completed_approvals'] > 0 && $repair['status'] === 'in_progress'): ?>
                                    <span class="text-emerald-600">🟢 เสร็จ: <?= $repair['completed_approvals'] ?>/2</span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <?php if ($repair['status'] === 'pending' && isAdmin()): ?>
                                        <?php if ($repair['in_progress_approvals'] == 0): ?>
                                            <!-- First admin - show modal to fill repair type info -->
                                            <button type="button" onclick="showAcceptModal(<?= $repair['id'] ?>)"
                                                class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg">🔧
                                                รับงาน</button>
                                        <?php else: ?>
                                            <!-- Second admin - just confirm, no modal needed -->
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="repair_id" value="<?= $repair['id'] ?>">
                                                <input type="hidden" name="new_status" value="in_progress">
                                                <button name="approve"
                                                    class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg">
                                                    ยืนยันรับงาน</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php elseif ($repair['status'] === 'in_progress' && (isManager() || isSupervisor())): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="repair_id" value="<?= $repair['id'] ?>">
                                            <input type="hidden" name="new_status" value="completed">
                                            <button name="approve"
                                                class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium rounded-lg">
                                                ยืนยันเสร็จ</button>
                                        </form>
                                    <?php elseif ($repair['status'] === 'pending'): ?>
                                        <span class="text-amber-500 text-xs">⏳ รอ Admin รับงาน</span>
                                    <?php elseif ($repair['status'] === 'in_progress'): ?>
                                        <span class="text-blue-500 text-xs">🔧 รอยืนยันเสร็จ</span>
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
    <!-- Store repair notes/details for modal -->
    <div id="repairNotes-<?= $repair['id'] ?>" class="hidden"><?= e($repair['repair_notes'] ?? '') ?></div>
    <div id="repairDetails-<?= $repair['id'] ?>" class="hidden"><?= e($repair['repair_details'] ?? '') ?></div>
    <div id="repairType-<?= $repair['id'] ?>" class="hidden"><?= e($repair['repair_type'] ?? '') ?></div>
    <!-- Store device info for accept modal -->
    <div id="deviceType-<?= $repair['id'] ?>" class="hidden"><?= e($repair['device_type']) ?></div>
    <div id="deviceDetail-<?= $repair['id'] ?>" class="hidden"><?= e($repair['device_detail'] ?? '') ?></div>
    <div id="problemDesc-<?= $repair['id'] ?>" class="hidden"><?= e($repair['problem']) ?></div>
    <div id="reporterName-<?= $repair['id'] ?>" class="hidden"><?= e($repair['fullname']) ?></div>
    <div id="deptName-<?= $repair['id'] ?>" class="hidden"><?= e($repair['department_name']) ?></div>
<?php endforeach; ?>

<!-- Accept Repair Modal -->
<div id="acceptModal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4"
    onclick="hideAcceptModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">🔧 รับงานซ่อม <span id="acceptRepairIdDisplay"
                    class="text-gray-500 text-base font-normal"></span></h3>
            <button onclick="hideAcceptModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <!-- Device Info Section -->
        <div class="mb-4 p-4 bg-blue-50 rounded-xl border border-blue-100">
            <h4 class="text-sm font-semibold text-blue-800 mb-2">📋 ข้อมูลการแจ้งซ่อม</h4>
            <div class="space-y-2 text-sm">
                <div class="flex">
                    <span class="text-gray-500 w-24">ผู้แจ้ง:</span>
                    <span id="modalReporter" class="text-gray-800 font-medium"></span>
                </div>
                <div class="flex">
                    <span class="text-gray-500 w-24">แผนก:</span>
                    <span id="modalDept" class="text-gray-800"></span>
                </div>
                <div class="flex">
                    <span class="text-gray-500 w-24">อุปกรณ์:</span>
                    <span id="modalDevice" class="text-gray-800 font-medium"></span>
                </div>
                <div id="modalDeviceDetailRow" class="flex hidden">
                    <span class="text-gray-500 w-24">รายละเอียด:</span>
                    <span id="modalDeviceDetail" class="text-gray-800"></span>
                </div>
                <div class="flex">
                    <span class="text-gray-500 w-24">ปัญหา:</span>
                    <span id="modalProblem" class="text-gray-800"></span>
                </div>
            </div>
        </div>

        <form method="POST" id="acceptForm">
            <input type="hidden" name="repair_id" id="acceptRepairId">
            <input type="hidden" name="new_status" value="in_progress">

            <!-- Repair Type -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">ประเภทการซ่อม <span
                        class="text-red-500">*</span></label>
                <select name="repair_type" id="repairTypeSelect" required
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    onchange="toggleNotesField()">
                    <option value="">-- เลือกประเภท --</option>
                    <option value="self_repair">🔧 ซ่อมเอง</option>
                    <option value="outsource">🏭 ส่งซ่อม</option>
                </select>
            </div>

            <!-- Notes (shown when outsource is selected) -->
            <div id="notesField" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุ
                    (ส่งซ่อมที่ไหน/ข้อมูลเพิ่มเติม)</label>
                <input type="text" name="repair_notes" id="repairNotesInput"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="เช่น ส่งซ่อมที่ร้าน ABC">
            </div>

            <!-- Repair Details -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">รายละเอียดการซ่อม / การดำเนินการ</label>
                <textarea name="repair_details" id="repairDetailsInput" rows="3"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
                    placeholder="บันทึกสิ่งที่จะทำหรือข้อมูลการซ่อม..."></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="hideAcceptModal()"
                    class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-all">
                    ยกเลิก
                </button>
                <button type="submit" name="approve"
                    class="flex-1 px-4 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition-all font-medium">
                    🔧 ยืนยันรับงาน
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Repair Details View Modal -->
<div id="repairDetailsModal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4"
    onclick="hideRepairDetailsModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">📋 รายละเอียดการซ่อม</h3>
            <button onclick="hideRepairDetailsModal()"
                class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <div class="space-y-4">
            <div id="detailsTypeDisplay" class="p-3 bg-gray-50 rounded-xl">
                <label class="block text-xs font-medium text-gray-500 mb-1">ประเภทการซ่อม</label>
                <p id="detailsType" class="text-gray-800 font-medium"></p>
            </div>

            <div id="detailsNotesDisplay" class="p-3 bg-gray-50 rounded-xl hidden">
                <label class="block text-xs font-medium text-gray-500 mb-1">หมายเหตุ</label>
                <p id="detailsNotes" class="text-gray-800"></p>
            </div>

            <div id="detailsActionsDisplay" class="p-3 bg-gray-50 rounded-xl hidden">
                <label class="block text-xs font-medium text-gray-500 mb-1">รายละเอียดการซ่อม / การดำเนินการ</label>
                <p id="detailsActions" class="text-gray-800 whitespace-pre-wrap"></p>
            </div>
        </div>

        <button onclick="hideRepairDetailsModal()"
            class="w-full mt-6 px-4 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all">
            ปิด
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Image Modal
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

    // Accept Modal
    function showAcceptModal(id) {
        document.getElementById('acceptRepairId').value = id;
        document.getElementById('acceptRepairIdDisplay').textContent = '#' + String(id).padStart(4, '0');
        document.getElementById('repairTypeSelect').value = '';
        document.getElementById('repairNotesInput').value = '';
        document.getElementById('repairDetailsInput').value = '';
        document.getElementById('notesField').classList.add('hidden');

        // Populate device info
        const reporter = document.getElementById('reporterName-' + id)?.textContent || '';
        const dept = document.getElementById('deptName-' + id)?.textContent || '';
        const device = document.getElementById('deviceType-' + id)?.textContent || '';
        const deviceDetail = document.getElementById('deviceDetail-' + id)?.textContent || '';
        const problem = document.getElementById('problemDesc-' + id)?.textContent || '';

        document.getElementById('modalReporter').textContent = reporter;
        document.getElementById('modalDept').textContent = dept;
        document.getElementById('modalDevice').textContent = device;
        document.getElementById('modalProblem').textContent = problem;

        if (deviceDetail) {
            document.getElementById('modalDeviceDetail').textContent = deviceDetail;
            document.getElementById('modalDeviceDetailRow').classList.remove('hidden');
        } else {
            document.getElementById('modalDeviceDetailRow').classList.add('hidden');
        }

        document.getElementById('acceptModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function hideAcceptModal() {
        document.getElementById('acceptModal').classList.add('hidden');
        document.body.style.overflow = '';
    }
    function toggleNotesField() {
        const select = document.getElementById('repairTypeSelect');
        const notesField = document.getElementById('notesField');
        if (select.value === 'outsource') {
            notesField.classList.remove('hidden');
        } else {
            notesField.classList.add('hidden');
        }
    }

    // Repair Details Modal
    function showRepairDetailsModal(id) {
        const type = document.getElementById('repairType-' + id)?.textContent || '';
        const notes = document.getElementById('repairNotes-' + id)?.textContent || '';
        const details = document.getElementById('repairDetails-' + id)?.textContent || '';

        // Display type
        const typeText = type === 'self_repair' ? '🔧 ซ่อมเอง' : '🏭 ส่งซ่อม';
        document.getElementById('detailsType').textContent = typeText;

        // Display notes if exists
        if (notes) {
            document.getElementById('detailsNotes').textContent = notes;
            document.getElementById('detailsNotesDisplay').classList.remove('hidden');
        } else {
            document.getElementById('detailsNotesDisplay').classList.add('hidden');
        }

        // Display details if exists
        if (details) {
            document.getElementById('detailsActions').textContent = details;
            document.getElementById('detailsActionsDisplay').classList.remove('hidden');
        } else {
            document.getElementById('detailsActionsDisplay').classList.add('hidden');
        }

        document.getElementById('repairDetailsModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function hideRepairDetailsModal() {
        document.getElementById('repairDetailsModal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Keyboard handlers
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            hideImageModal();
            hideAcceptModal();
            hideRepairDetailsModal();
        }
    });

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
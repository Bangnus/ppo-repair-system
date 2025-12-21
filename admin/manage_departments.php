<?php
/**
 * Manage Departments (Admin Only)
 * CRUD for departments
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
        $stmt->execute([$name]);
        $_SESSION['toast'] = ['message' => 'เพิ่มแผนกเรียบร้อยแล้ว', 'type' => 'success'];
    }
    header('Location: manage_departments.php');
    exit();
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id = (int) $_POST['id'];
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE departments SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        $_SESSION['toast'] = ['message' => 'แก้ไขแผนกเรียบร้อยแล้ว', 'type' => 'success'];
    }
    header('Location: manage_departments.php');
    exit();
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = (int) $_POST['id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE department_id = ?");
    $stmt->execute([$id]);
    $repairCount = $stmt->fetchColumn();

    if ($repairCount > 0) {
        $_SESSION['toast'] = ['message' => 'ไม่สามารถลบได้ แผนกนี้มีการแจ้งซ่อมอยู่', 'type' => 'error'];
    } else {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['toast'] = ['message' => 'ลบแผนกเรียบร้อยแล้ว', 'type' => 'success'];
    }
    header('Location: manage_departments.php');
    exit();
}

$pageTitle = 'จัดการแผนก';
require_once __DIR__ . '/../includes/header.php';

$departments = $pdo->query("
    SELECT d.*, (SELECT COUNT(*) FROM repairs WHERE department_id = d.id) as repair_count
    FROM departments d ORDER BY d.name
")->fetchAll();
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">🏢 จัดการแผนก</h1>
        <p class="text-gray-500">เพิ่ม แก้ไข หรือลบแผนกในระบบ</p>
    </div>
    <button onclick="showAddModal()"
        class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold rounded-xl shadow-lg">
        ➕ เพิ่มแผนก
    </button>
</div>

<?php if (empty($departments)): ?>
    <div class="glass rounded-2xl border border-white/50 shadow-lg p-12 text-center">
        <span class="text-6xl">📭</span>
        <h2 class="text-xl font-semibold text-gray-700 mt-4">ยังไม่มีแผนก</h2>
        <p class="text-gray-500 mt-2">คลิก "เพิ่มแผนก" เพื่อเริ่มต้น</p>
    </div>
<?php else: ?>
    <div class="glass rounded-2xl border border-white/50 shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-emerald-600 to-emerald-700 text-white">
                    <tr>
                        <th class="px-6 py-4 text-center text-xs font-semibold uppercase w-16">#</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase">ชื่อแผนก</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold uppercase">รายการแจ้งซ่อม</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $index = 0; foreach ($departments as $dept): $index++; ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-center text-gray-500 font-medium"><?= $index ?></td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-800"><?= e($dept['name']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-600"><?= $dept['repair_count'] ?> รายการ</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="showEditModal(<?= $dept['id'] ?>, '<?= e($dept['name']) ?>')"
                                        class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg">✏️</button>
                                    <?php if ($dept['repair_count'] == 0): ?>
                                        <form method="POST" class="inline" id="deleteForm<?= $dept['id'] ?>">
                                            <input type="hidden" name="id" value="<?= $dept['id'] ?>">
                                            <input type="hidden" name="delete" value="1">
                                            <button type="button"
                                                onclick="confirmDelete(<?= $dept['id'] ?>, '<?= e($dept['name']) ?>')"
                                                class="p-2 text-red-500 hover:bg-red-50 rounded-lg">🗑️</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="glass rounded-2xl shadow-2xl p-6 w-full max-w-md" onclick="event.stopPropagation()">
        <h3 class="text-xl font-bold text-gray-800 mb-6">➕ เพิ่มแผนกใหม่</h3>
        <form method="POST">
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อแผนก</label>
                <input type="text" name="name" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 outline-none"
                    placeholder="เช่น IT, การเงิน">
            </div>
            <div class="flex gap-3">
                <button name="add"
                    class="flex-1 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold rounded-xl">บันทึก</button>
                <button type="button" onclick="hideAddModal()"
                    class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="glass rounded-2xl shadow-2xl p-6 w-full max-w-md" onclick="event.stopPropagation()">
        <h3 class="text-xl font-bold text-gray-800 mb-6">✏️ แก้ไขแผนก</h3>
        <form method="POST">
            <input type="hidden" name="id" id="editId">
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อแผนก</label>
                <input type="text" name="name" id="editName" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 outline-none">
            </div>
            <div class="flex gap-3">
                <button name="edit"
                    class="flex-1 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold rounded-xl">บันทึก</button>
                <button type="button" onclick="hideEditModal()"
                    class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function showAddModal() { document.getElementById('addModal').classList.remove('hidden'); }
    function hideAddModal() { document.getElementById('addModal').classList.add('hidden'); }
    function showEditModal(id, name) {
        document.getElementById('editId').value = id;
        document.getElementById('editName').value = name;
        document.getElementById('editModal').classList.remove('hidden');
    }
    function hideEditModal() { document.getElementById('editModal').classList.add('hidden'); }
    document.getElementById('addModal').addEventListener('click', function (e) { if (e.target === this) hideAddModal(); });
    document.getElementById('editModal').addEventListener('click', function (e) { if (e.target === this) hideEditModal(); });

    function confirmDelete(id, name) {
        Swal.fire({
            title: 'ลบแผนก?',
            text: 'ต้องการลบ "' + name + '" หรือไม่?',
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
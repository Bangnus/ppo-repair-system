<?php
/**
 * Manage Departments (Admin Only)
 * CRUD for departments
 */
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

// Handle Add - BEFORE including header
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

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE department_id = ?");
    $stmt->execute([$id]);
    $userCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE department_id = ?");
    $stmt->execute([$id]);
    $repairCount = $stmt->fetchColumn();

    if ($userCount > 0 || $repairCount > 0) {
        $_SESSION['toast'] = ['message' => 'ไม่สามารถลบได้ แผนกนี้มีข้อมูลผู้ใช้หรือการแจ้งซ่อม', 'type' => 'error'];
    } else {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['toast'] = ['message' => 'ลบแผนกเรียบร้อยแล้ว', 'type' => 'success'];
    }
    header('Location: manage_departments.php');
    exit();
}

// NOW include header
$pageTitle = 'จัดการแผนก';
require_once __DIR__ . '/includes/header.php';

// Get all departments with counts
$departments = $pdo->query("
    SELECT d.*, 
           (SELECT COUNT(*) FROM users WHERE department_id = d.id) as user_count,
           (SELECT COUNT(*) FROM repairs WHERE department_id = d.id) as repair_count
    FROM departments d 
    ORDER BY d.name
")->fetchAll();
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">🏢 จัดการแผนก</h1>
        <p class="text-gray-500">เพิ่ม แก้ไข หรือลบแผนกในระบบ</p>
    </div>
    <button onclick="showAddModal()"
        class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
        ➕ เพิ่มแผนก
    </button>
</div>

<!-- Departments Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($departments as $dept): ?>
        <div class="glass rounded-2xl border border-white/50 shadow-lg p-6 hover:shadow-xl transition-all">
            <div class="flex items-start justify-between mb-4">
                <div
                    class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center">
                    <span class="text-2xl">🏢</span>
                </div>
                <div class="flex gap-2">
                    <button onclick="showEditModal(<?= $dept['id'] ?>, '<?= e($dept['name']) ?>')"
                        class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors">
                        ✏️
                    </button>
                    <?php if ($dept['user_count'] == 0 && $dept['repair_count'] == 0): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('ต้องการลบแผนกนี้?')">
                            <input type="hidden" name="id" value="<?= $dept['id'] ?>">
                            <button name="delete"
                                class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">🗑️</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-2"><?= e($dept['name']) ?></h3>
            <div class="flex gap-4 text-sm text-gray-500">
                <span>👥 <?= $dept['user_count'] ?> คน</span>
                <span>📋 <?= $dept['repair_count'] ?> รายการ</span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="glass rounded-2xl shadow-2xl p-6 w-full max-w-md" onclick="event.stopPropagation()">
        <h3 class="text-xl font-bold text-gray-800 mb-6">➕ เพิ่มแผนกใหม่</h3>
        <form method="POST">
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อแผนก</label>
                <input type="text" name="name" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none"
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
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none">
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
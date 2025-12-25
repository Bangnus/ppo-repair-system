<?php
/**
 * Manage Users (Admin Only)
 * CRUD for users
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = trim($_POST['fullname']);
    $role = $_POST['role'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['toast'] = ['message' => 'ชื่อผู้ใช้นี้มีอยู่แล้ว', 'type' => 'error'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password, $fullname, $role]);
        $_SESSION['toast'] = ['message' => 'เพิ่มผู้ใช้เรียบร้อยแล้ว', 'type' => 'success'];
    }
    header('Location: manage_users.php');
    exit();
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id = (int) $_POST['id'];
    $fullname = trim($_POST['fullname']);
    $role = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, role = ?, password = ? WHERE id = ?");
        $stmt->execute([$fullname, $role, $password, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, role = ? WHERE id = ?");
        $stmt->execute([$fullname, $role, $id]);
    }

    $_SESSION['toast'] = ['message' => 'แก้ไขผู้ใช้เรียบร้อยแล้ว', 'type' => 'success'];
    header('Location: manage_users.php');
    exit();
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = (int) $_POST['id'];

    if ($id === $_SESSION['user_id']) {
        $_SESSION['toast'] = ['message' => 'ไม่สามารถลบตัวเองได้', 'type' => 'error'];
    } else {
        // Get user info
        $stmt = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $userName = $stmt->fetchColumn();

        // Get repair count before deletion
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE user_id = ?");
        $stmt->execute([$id]);
        $repairCount = $stmt->fetchColumn();

        // Get repair IDs for this user
        $stmt = $pdo->prepare("SELECT id FROM repairs WHERE user_id = ?");
        $stmt->execute([$id]);
        $repairIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Delete approvals for user's repairs
        if (!empty($repairIds)) {
            $placeholders = implode(',', array_fill(0, count($repairIds), '?'));
            $stmt = $pdo->prepare("DELETE FROM approvals WHERE repair_id IN ($placeholders)");
            $stmt->execute($repairIds);
        }

        // Delete approvals made by this user (as admin)
        $stmt = $pdo->prepare("DELETE FROM approvals WHERE admin_id = ?");
        $stmt->execute([$id]);

        // Delete all repairs by this user
        $stmt = $pdo->prepare("DELETE FROM repairs WHERE user_id = ?");
        $stmt->execute([$id]);

        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        if ($repairCount > 0) {
            $_SESSION['toast'] = ['message' => 'ลบผู้ใช้ "' . $userName . '" พร้อมรายการแจ้งซ่อม ' . $repairCount . ' รายการเรียบร้อยแล้ว', 'type' => 'success'];
        } else {
            $_SESSION['toast'] = ['message' => 'ลบผู้ใช้เรียบร้อยแล้ว', 'type' => 'success'];
        }
    }
    header('Location: manage_users.php');
    exit();
}

$pageTitle = 'จัดการผู้ใช้';
require_once __DIR__ . '/../includes/header.php';

$users = $pdo->query("
    SELECT u.*, (SELECT COUNT(*) FROM repairs WHERE user_id = u.id) as repair_count
    FROM users u 
    ORDER BY FIELD(u.role, 'admin', 'manager', 'supervisor', 'user'), u.fullname
")->fetchAll();

$rowColors = [
    'admin' => 'bg-emerald-50/50',
    'manager' => 'bg-purple-50/50',
    'supervisor' => 'bg-blue-50/50',
    'user' => ''
];
$avatarColors = [
    'admin' => 'from-emerald-400 to-emerald-600',
    'manager' => 'from-purple-400 to-purple-600',
    'supervisor' => 'from-blue-400 to-blue-600',
    'user' => 'from-amber-400 to-amber-600'
];
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">👥 จัดการผู้ใช้</h1>
        <p class="text-gray-500">เพิ่ม แก้ไข หรือลบผู้ใช้ในระบบ</p>
    </div>
    <button onclick="showAddModal()"
        class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold rounded-xl shadow-lg">
        ➕ เพิ่มผู้ใช้
    </button>
</div>

<div class="glass rounded-2xl border border-white/50 shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-emerald-600 to-emerald-700 text-white">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase">ผู้ใช้</th>
                    <!-- <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Username</th> -->
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase">บทบาท</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase">แจ้งซ่อม</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $user):
                    $roleInfo = getRoleInfo($user['role']);
                    $colorMap = ['emerald' => 'bg-emerald-100 text-emerald-700', 'purple' => 'bg-purple-100 text-purple-700', 'blue' => 'bg-blue-100 text-blue-700', 'amber' => 'bg-amber-100 text-amber-700'];
                    $isCurrentUser = $user['id'] === $_SESSION['user_id'];
                    $rowClass = $isCurrentUser
                        ? 'bg-yellow-100 border-l-4 border-yellow-500'
                        : 'hover:bg-gray-100/50 ' . ($rowColors[$user['role']] ?? '');
                    ?>
                    <tr class="transition-colors <?= $rowClass ?>">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-gradient-to-br <?= $avatarColors[$user['role']] ?? 'from-gray-400 to-gray-600' ?> flex items-center justify-center text-white font-bold">
                                    <?= mb_substr($user['fullname'], 0, 1) ?>
                                </div>
                                <span class="font-medium text-gray-800"><?= e($user['fullname']) ?></span>
                            </div>
                        </td>
                        <!-- <td class="px-6 py-4 font-mono text-sm text-gray-600"><?= e($user['username']) ?></td> -->
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?= $colorMap[$roleInfo['color']] ?>">
                                <?= $roleInfo['icon'] ?>     <?= $roleInfo['label'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600"><?= $user['repair_count'] ?> รายการ</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    onclick='showEditModal(<?= json_encode(["id" => $user["id"], "fullname" => $user["fullname"], "role" => $user["role"]]) ?>)'
                                    class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg">✏️</button>
                                <?php if ($user['id'] !== $_SESSION['user_id'] && $user['role'] !== 'admin'): ?>
                                    <form method="POST" class="inline" id="deleteForm<?= $user['id'] ?>">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="delete" value="1">
                                        <button type="button"
                                            onclick="confirmDelete(<?= $user['id'] ?>, '<?= e($user['fullname']) ?>', <?= $user['repair_count'] ?>)"
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

<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="glass rounded-2xl shadow-2xl p-6 w-full max-w-md" onclick="event.stopPropagation()">
        <h3 class="text-xl font-bold text-gray-800 mb-6">➕ เพิ่มผู้ใช้ใหม่</h3>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อผู้ใช้ (Username) *</label>
                <input type="text" name="username" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่าน *</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อ-นามสกุล *</label>
                <input type="text" name="fullname" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">บทบาท *</label>
                <select name="role" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 outline-none">
                    <option value="user">👤 User</option>
                    <option value="supervisor">🔰 Supervisor</option>
                    <option value="manager">💼 Manager</option>
                    <option value="admin">👑 Admin</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
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
        <h3 class="text-xl font-bold text-gray-800 mb-6">✏️ แก้ไขผู้ใช้</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="id" id="editId">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่านใหม่ (เว้นว่างถ้าไม่เปลี่ยน)</label>
                <input type="password" name="password"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 outline-none"
                    placeholder="••••••••">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อ-นามสกุล *</label>
                <input type="text" name="fullname" id="editFullname" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">บทบาท *</label>
                <select name="role" id="editRole" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 outline-none">
                    <option value="user">👤 User</option>
                    <option value="supervisor">🔰 Supervisor</option>
                    <option value="manager">💼 Manager</option>
                    <option value="admin">👑 Admin</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
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
    function showEditModal(user) {
        document.getElementById('editId').value = user.id;
        document.getElementById('editFullname').value = user.fullname;
        document.getElementById('editRole').value = user.role;
        document.getElementById('editModal').classList.remove('hidden');
    }
    function hideEditModal() { document.getElementById('editModal').classList.add('hidden'); }
    document.getElementById('addModal').addEventListener('click', function (e) { if (e.target === this) hideAddModal(); });
    document.getElementById('editModal').addEventListener('click', function (e) { if (e.target === this) hideEditModal(); });

    function confirmDelete(id, name, repairCount) {
        if (repairCount > 0) {
            // Has repairs - require typing name to confirm
            Swal.fire({
                title: '⚠️ ยืนยันการลบ',
                html: `
                    <div class="text-left">
                        <p class="text-red-600 font-semibold ">คำเตือน: "${name}" มีรายการแจ้งซ่อม ${repairCount} รายการ</p>
                        <p class="text-gray-600 mb-4 text-sm">การลบจะลบรายการแจ้งซ่อมทั้งหมดด้วย</p>
                        <p class="text-sm text-gray-500 mb-2">พิมพ์ <strong class="text-red-600">${name}</strong> เพื่อยืนยัน:</p>
                    </div>
                `,
                input: 'text',
                inputPlaceholder: 'พิมพ์ชื่อเพื่อยืนยัน...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '🗑️ ลบผู้ใช้',
                cancelButtonText: 'ยกเลิก',
                inputValidator: (value) => {
                    if (value !== name) {
                        return 'ชื่อไม่ตรงกัน กรุณาพิมพ์ให้ถูกต้อง';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteForm' + id).submit();
                }
            });
        } else {
            // No repairs - simple confirmation
            Swal.fire({
                title: 'ลบผู้ใช้?',
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
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
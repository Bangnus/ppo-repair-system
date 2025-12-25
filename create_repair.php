<?php
/**
 * Create Repair Request
 * Form to submit new repair
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

// Get device types from database
$deviceTypes = $pdo->query("SELECT name FROM devices ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

// Handle form submission BEFORE including header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departmentId = (int) $_POST['department_id'];
    $deviceType = trim($_POST['device_type']);
    $deviceDetail = trim($_POST['device_detail'] ?? '');
    $problem = trim($_POST['problem']);

    // Handle image upload as Base64 with size limit
    $imageBase64 = null;
    if (!empty($_FILES['image']['tmp_name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['image']['tmp_name']);
        $maxFileSizeKB = 500; // Maximum file size in KB (500KB)

        if (in_array($fileType, $allowedTypes)) {
            // Check file size
            $fileSizeKB = $_FILES['image']['size'] / 1024;

            if ($fileSizeKB > $maxFileSizeKB) {
                $_SESSION['toast'] = ['message' => 'รูปภาพมีขนาดใหญ่เกินไป (สูงสุด ' . $maxFileSizeKB . 'KB) กรุณาย่อขนาดรูปก่อนอัพโหลด', 'type' => 'error'];
                header('Location: create_repair.php');
                exit();
            }

            $imageData = file_get_contents($_FILES['image']['tmp_name']);
            $imageBase64 = 'data:' . $fileType . ';base64,' . base64_encode($imageData);
        }
    }

    // Insert to database
    $stmt = $pdo->prepare("INSERT INTO repairs (user_id, department_id, device_type, device_detail, problem, image_base64) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $departmentId,
        $deviceType,
        $deviceDetail,
        $problem,
        $imageBase64
    ]);

    $_SESSION['toast'] = ['message' => 'แจ้งซ่อมเรียบร้อยแล้ว', 'type' => 'success'];
    header('Location: my_repairs.php');
    exit();
}

// Now include header (after redirect logic)
$pageTitle = 'แจ้งซ่อม';
require_once __DIR__ . '/includes/header.php';

// Get departments
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
?>

<div class="max-w-2xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800">แจ้งซ่อมอุปกรณ์</h1>
        <p class="text-gray-500 mt-1">กรอกข้อมูลเพื่อแจ้งซ่อมอุปกรณ์ IT</p>
    </div>

    <!-- Form Card -->
    <div class="glass rounded-2xl border border-white/50 shadow-xl p-8">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">

            <!-- Reporter Info -->
            <div class="p-4 bg-gray-50 rounded-xl">
                <p class="text-sm text-gray-500">👤 ผู้แจ้ง</p>
                <p class="font-semibold text-gray-800"><?= e($currentUser['fullname']) ?></p>
            </div>

            <!-- Department -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">🏢 แผนก / ฝ่าย <span
                        class="text-red-500">*</span></label>
                <select name="department_id" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none transition-all bg-white">
                    <option value="">-- เลือกแผนก --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>"><?= e($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Device Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">💻 ประเภทอุปกรณ์ <span
                        class="text-red-500">*</span></label>
                <select name="device_type" id="deviceType" required onchange="toggleDeviceDetail()"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none transition-all bg-white">
                    <option value="">-- เลือกประเภทอุปกรณ์ --</option>
                    <?php foreach ($deviceTypes as $type): ?>
                        <option value="<?= e($type) ?>"><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Device Detail (conditional) -->
            <div id="deviceDetailField" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">📝 รายละเอียดอุปกรณ์</label>
                <input type="text" name="device_detail"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none transition-all bg-white"
                    placeholder="เช่น หมายเลขกล้อง, ชื่ออุปกรณ์">
            </div>

            <!-- Problem -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">🛠️ อาการ / ปัญหาที่พบ <span
                        class="text-red-500">*</span></label>
                <textarea name="problem" required rows="4"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none transition-all bg-white resize-none"
                    placeholder="อธิบายปัญหาหรืออาการที่พบ..."></textarea>
            </div>

            <!-- Image Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">📸 แนบรูปภาพ (ไม่บังคับ)</label>
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-emerald-500 transition-colors cursor-pointer"
                    onclick="document.getElementById('imageInput').click()">
                    <input type="file" name="image" id="imageInput" accept="image/*" class="hidden"
                        onchange="previewImage(this)">
                    <div id="uploadPlaceholder">
                        <span class="text-4xl">📷</span>
                        <p class="mt-2 text-sm text-gray-500">คลิกเพื่อเลือกรูปภาพ</p>
                        <p class="text-xs text-gray-400">รองรับ JPG, PNG, GIF, WebP</p>
                    </div>
                    <div id="imagePreview" class="hidden">
                        <img id="previewImg" class="max-h-48 mx-auto rounded-lg shadow-md">
                        <button type="button" onclick="event.stopPropagation(); clearImage()"
                            class="mt-2 text-red-500 text-sm hover:underline">❌ ลบรูป</button>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-4 pt-4">
                <button type="submit"
                    class="flex-1 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                    📨 ส่งแจ้งซ่อม
                </button>
                <a href="dashboard.php"
                    class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-all text-center">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleDeviceDetail() {
        const deviceType = document.getElementById('deviceType').value;
        const detailField = document.getElementById('deviceDetailField');

        if (deviceType === 'กล้องวงจรปิด' || deviceType === 'อื่น ๆ') {
            detailField.classList.remove('hidden');
        } else {
            detailField.classList.add('hidden');
        }
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('uploadPlaceholder').classList.add('hidden');
                document.getElementById('imagePreview').classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function clearImage() {
        document.getElementById('imageInput').value = '';
        document.getElementById('uploadPlaceholder').classList.remove('hidden');
        document.getElementById('imagePreview').classList.add('hidden');
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
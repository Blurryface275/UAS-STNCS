<?php
require_once '../auth.php';
require_once '../Database.php';
require_once '../models/Task.php';
require_once '../models/User.php';
require_once '../models/Verification.php';
requireRole(['Admin', 'Direktur', 'Manager', 'Supervisor', 'Staff']);

$database = new Database();
$db = $database->getConnection();

$task = new Task($db);
$userModel = new User($db);
$verification = new Verification($db);

$message = '';
$userRole = $_SESSION['user_role'] ?? 'Staff';
$userPowerLevel = $_SESSION['power_level'] ?? 1;
$currentUserId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_task']) && $userPowerLevel > 1) {
        $assigned_user_id = $_POST['assignee_id'] ?? '';
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $aktivitas = trim($_POST['aktivitas'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $deadline = $_POST['deadline'] ?? '';

        if ($aktivitas === '' || $deskripsi === '' || $deadline === '' || empty($assigned_user_id)) {
            $message = 'Data task belum lengkap.';
        } elseif (strtotime($deadline) <= time()) {
            $message = 'Deadline harus lebih besar dari waktu sekarang.';
        } else {
            $message = $task->create($currentUserId, $assigned_user_id, $tanggal, $aktivitas, $deskripsi, $deadline)
                ? 'Task berhasil ditugaskan.'
                : 'Task gagal ditambahkan.';
        }
    } elseif (isset($_POST['submit_attachment']) && $userPowerLevel < 5) {
        $task_id = $_POST['task_id'] ?? null;
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;

        if (!$task_id || empty($_FILES['lampiran']['name'])) {
            $message = 'File lampiran wajib diunggah.';
        } elseif ($latitude === '' || $longitude === '' || $latitude === null || $longitude === null) {
            $message = 'Lokasi GPS belum terdeteksi. Izinkan akses lokasi lalu coba lagi.';
        } else {
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
            $originalName = $_FILES['lampiran']['name'];
            $tmpName = $_FILES['lampiran']['tmp_name'];
            $fileSize = $_FILES['lampiran']['size'];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions)) {
                $message = 'Format file tidak valid. Gunakan PDF, JPG, JPEG, atau PNG.';
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $message = 'Ukuran file maksimal 5MB.';
            } else {
                $uploadDir = '../uploads/tasks/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $safeFileName = 'task_' . $task_id . '_user_' . $currentUserId . '_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $safeFileName;
                $dbPath = 'uploads/tasks/' . $safeFileName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $fileHash = hash_file('sha256', $targetPath);
                    $submittedAt = date('Y-m-d H:i:s');

                    $success = $task->submitAttachment(
                        $task_id,
                        $currentUserId,
                        $dbPath,
                        $fileHash,
                        $latitude,
                        $longitude,
                        $submittedAt
                    );

                    $message = $success
                        ? 'Lampiran berhasil dikumpulkan. Hash SHA-256 dan lokasi berhasil disimpan.'
                        : 'Gagal menyimpan data lampiran. Pastikan tugas ini milik Anda.';
                } else {
                    $message = 'Upload file gagal.';
                }
            }
        }
    } elseif (isset($_POST['submit_verification']) && $userPowerLevel < 5) {
        $task_id = $_POST['task_id'] ?? null;

        if ($task_id) {
            $checkOwner = $db->prepare("SELECT assignee_id, status, file_lampiran FROM tasks WHERE id = ?");
            $checkOwner->execute([$task_id]);
            $taskData = $checkOwner->fetch(PDO::FETCH_ASSOC);

            if ($taskData && $taskData['assignee_id'] == $currentUserId && $taskData['status'] === 'Selesai' && !empty($taskData['file_lampiran'])) {
                $message = $verification->create($task_id, $currentUserId)
                    ? 'Verifikasi berhasil diajukan.'
                    : 'Verifikasi gagal diajukan atau sudah pernah diajukan.';
            } else {
                $message = 'Tugas belum selesai, belum ada lampiran, atau bukan milik Anda.';
            }
        }
    }
}

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$records_per_page = 5;
$from_record_num = ($records_per_page * $page) - $records_per_page;

$filterPowerLevel = ($userPowerLevel < 5) ? $userPowerLevel : null;
$filterUserId = ($userPowerLevel < 5) ? $currentUserId : null;

$stmt = $task->readPaging($from_record_num, $records_per_page, $filterPowerLevel, $filterUserId);
$total_rows = $task->count($filterPowerLevel, $filterUserId);
$total_pages = ceil($total_rows / $records_per_page);

$usersList = null;
if ($userPowerLevel === 5) {
    $usersList = $userModel->getAllUsers();
} elseif ($userPowerLevel > 1) {
    $usersList = $userModel->getSubordinates($userPowerLevel);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks | Sistem Presensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-fingerprint fa-2x" style="color: var(--primary-light);"></i>
            <h2>Presensi<span style="color: white; font-weight: 300;">Pro</span></h2>
        </div>
        <ul class="nav-links">
            <li><a href="../index.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin'): ?>
                <li><a href="../users/index.php"><i class="fa-solid fa-users"></i> Data Karyawan</a></li>
            <?php endif; ?>
            <li><a href="../kehadiran/index.php"><i class="fa-solid fa-clock-rotate-left"></i> Kehadiran</a></li>
            <li><a href="index.php" class="active"><i class="fa-solid fa-list-check"></i> Tasks / Aktivitas</a></li>
            <?php if (isset($_SESSION['power_level']) && $_SESSION['power_level'] > 1): ?>
                <li><a href="../verification/index.php"><i class="fa-solid fa-clipboard-check"></i> Verifikasi</a></li>
            <?php endif; ?>
            <li style="margin-top: auto; padding-top: 20px;">
                <a href="../logout.php" style="color: #ef4444;">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                </a>
            </li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="welcome-msg">
                <h1>Daftar Task / Aktivitas</h1>
                <p>Laporan pekerjaan harian karyawan.</p>
            </div>
        </header>

        <div class="section-card">
            <?php if ($message): ?>
                <div style="margin-bottom: 15px; padding: 12px; background: #dcfce7; color: #166534; border-radius: 8px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($userPowerLevel > 1): ?>
                <div
                    style="margin-bottom: 25px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
                    <h2 style="margin-bottom: 15px;">Tugaskan Task Baru</h2>

                    <form method="POST" style="display: grid; gap: 12px;">
                        <input type="hidden" name="submit_task" value="1">

                        <div>
                            <label>Karyawan</label>
                            <select name="assignee_id" required
                                style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                                <option value="">-- Pilih Karyawan --</option>
                                <?php if ($usersList): ?>
                                    <?php while ($u = $usersList->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo htmlspecialchars($u['id']); ?>">
                                            <?php echo htmlspecialchars($u['nama'] . ' (' . $u['role_name'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div>
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required
                                style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                        </div>

                        <div>
                            <label>Aktivitas</label>
                            <input type="text" name="aktivitas" placeholder="Contoh: Coding Backend" required
                                style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                        </div>

                        <div>
                            <label>Deskripsi</label>
                            <textarea name="deskripsi" placeholder="Jelaskan pekerjaan" required
                                style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;"></textarea>
                        </div>

                        <div>
                            <label>Deadline Pekerjaan</label>
                            <input type="datetime-local" name="deadline" required
                                style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                        </div>

                        <button type="submit"
                            style="padding: 10px 16px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">
                            <i class="fa-solid fa-paper-plane"></i> Tugaskan
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ASSIGNEE</th>
                            <th>TANGGAL MULAI</th>
                            <th>AKTIVITAS</th>
                            <th>DEADLINE</th>
                            <th>LAMPIRAN</th>
                            <th>HASH & GPS</th>
                            <th>STATUS</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($row['user_name'] ?? '-'); ?>
                                    </div>
                                    <small>ID: <?php echo htmlspecialchars($row['assignee_id']); ?></small>
                                </td>

                                <td><?php echo htmlspecialchars($row['tanggal']); ?></td>

                                <td>
                                    <span
                                        style="font-weight: 500;"><?php echo htmlspecialchars($row['aktivitas']); ?></span><br>
                                    <small><?php echo htmlspecialchars($row['deskripsi']); ?></small>
                                </td>

                                <td style="color: #991b1b; font-weight: 500;">
                                    <i class="fa-solid fa-clock"></i>
                                    <?php echo !empty($row['deadline']) ? date('d M Y H:i', strtotime($row['deadline'])) : '-'; ?>
                                </td>

                                <td>
                                    <?php if (!empty($row['file_lampiran'])): ?>
                                        <a href="../<?php echo htmlspecialchars($row['file_lampiran']); ?>" target="_blank"
                                            style="color:#2563eb;">
                                            <i class="fa-solid fa-file"></i> Lihat File
                                        </a>
                                        <br>
                                        <small><?php echo htmlspecialchars($row['submitted_at'] ?? '-'); ?></small>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">Belum ada</span>
                                    <?php endif; ?>
                                </td>

                                <td style="max-width: 240px;">
                                    <?php if (!empty($row['file_hash'])): ?>
                                        <?php
                                        $shortHash = substr($row['file_hash'], 0, 12) . '...' . substr($row['file_hash'], -8);
                                        $mapsUrl = 'https://www.google.com/maps?q=' . $row['latitude'] . ',' . $row['longitude'];
                                        ?>

                                        <small><strong>SHA-256:</strong></small><br>
                                        <code title="<?php echo htmlspecialchars($row['file_hash']); ?>"
                                            style="font-size: 0.75rem; background:#f3f4f6; padding:4px 6px; border-radius:6px; display:inline-block;">
                            <?php echo htmlspecialchars($shortHash); ?>
                        </code>

                                        <br><br>

                                        <small><strong>GPS:</strong></small><br>
                                        <a href="<?php echo htmlspecialchars($mapsUrl); ?>" target="_blank"
                                            style="color:#2563eb; font-size:0.8rem;">
                                            <i class="fa-solid fa-location-dot"></i> Lihat Maps
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">Belum ada</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($row['status'] == 'Selesai'): ?>
                                        <span class="status-badge" style="background:#dcfce7; color:#166534;">Selesai</span>
                                    <?php elseif ($row['verification_status']): ?>
                                        <span class="status-badge" style="background:#e5e7eb; color:#374151;">
                                            <?php echo htmlspecialchars($row['verification_status']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge" style="background:#fef3c7; color:#92400e;">
                                            <?php echo htmlspecialchars($row['status'] ?? 'Pending'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($userPowerLevel < 5 && $row['assignee_id'] == $currentUserId && empty($row['file_lampiran'])): ?>
                                        <form method="POST" enctype="multipart/form-data" class="upload-task-form"
                                            style="display:flex; flex-direction:column; gap:6px; min-width:190px;">
                                            <input type="hidden" name="submit_attachment" value="1">
                                            <input type="hidden" name="task_id"
                                                value="<?php echo htmlspecialchars($row['id']); ?>">
                                            <input type="hidden" name="latitude" class="latitude-input">
                                            <input type="hidden" name="longitude" class="longitude-input">

                                            <input type="file" name="lampiran" accept=".pdf,.jpg,.jpeg,.png" required
                                                style="font-size: 0.8rem;">

                                            <button type="submit"
                                                style="padding: 6px 12px; background: #16a34a; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                                                <i class="fa-solid fa-upload"></i> Kumpulkan Lampiran
                                            </button>
                                        </form>
                                    <?php elseif ($userPowerLevel < 5 && $row['assignee_id'] == $currentUserId && $row['status'] === 'Selesai' && !$row['verification_status']): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="submit_verification" value="1">
                                            <input type="hidden" name="task_id"
                                                value="<?php echo htmlspecialchars($row['id']); ?>">
                                            <button type="submit"
                                                style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                                                Ajukan Verifikasi
                                            </button>
                                        </form>
                                    <?php elseif ($row['verification_status']): ?>
                                        <span style="color: var(--text-muted);">Sudah diajukan</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="page-link">
                            <i class="fa-solid fa-chevron-left"></i> Prev
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="page-link">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.querySelectorAll('.upload-task-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                const latitudeInput = form.querySelector('.latitude-input');
                const longitudeInput = form.querySelector('.longitude-input');

                if (latitudeInput.value && longitudeInput.value) {
                    return true;
                }

                event.preventDefault();

                if (!navigator.geolocation) {
                    alert('Browser tidak mendukung Geolocation.');
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        latitudeInput.value = position.coords.latitude;
                        longitudeInput.value = position.coords.longitude;
                        form.submit();
                    },
                    function (error) {
                        alert('Gagal mengambil lokasi. Izinkan akses lokasi terlebih dahulu.');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            });
        });
    </script>
</body>

</html>
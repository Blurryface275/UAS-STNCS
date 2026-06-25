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
        $assigned_user_id = $_POST['users_id'] ?? $currentUserId;
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $aktivitas = trim($_POST['aktivitas'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $durasi_jam = $_POST['durasi_jam'] ?? 0;

        if ($aktivitas === '' || $deskripsi === '' || $durasi_jam <= 0) {
            $message = 'Data task belum lengkap.';
        } else {
            $message = $task->create($assigned_user_id, $tanggal, $aktivitas, $deskripsi, $durasi_jam)
                ? 'Task berhasil ditugaskan.'
                : 'Task gagal ditambahkan.';
        }
    } elseif (isset($_POST['submit_verification']) && $userPowerLevel < 5) {
        $task_id = $_POST['task_id'] ?? null;
        if ($task_id) {
            $checkOwner = $db->prepare("SELECT users_id FROM tasks WHERE id = ?");
            $checkOwner->execute([$task_id]);
            $taskData = $checkOwner->fetch(PDO::FETCH_ASSOC);
            
            if ($taskData && $taskData['users_id'] == $currentUserId) {
                $message = $verification->create($task_id, $currentUserId)
                    ? 'Verifikasi berhasil diajukan.'
                    : 'Verifikasi gagal diajukan.';
            } else {
                $message = 'Akses ditolak. Tugas ini bukan milik Anda.';
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
                            <select name="users_id" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                                <option value="">-- Pilih Karyawan --</option>
                                <?php while ($u = $usersList->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo htmlspecialchars($u['id']); ?>">
                                        <?php echo htmlspecialchars($u['nama'] . ' (' . $u['role_name'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
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
                            <label>Durasi Jam</label>
                            <input type="number" step="0.5" min="0.5" name="durasi_jam" placeholder="Contoh: 3.5" required
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
                            <th>ID USER</th>
                            <th>NAMA USER</th>
                            <th>TANGGAL</th>
                            <th>AKTIVITAS</th>
                            <th>DESKRIPSI</th>
                            <th>DURASI (JAM)</th>
                            <th>STATUS VERIFIKASI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['users_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['tanggal']); ?></td>
                                <td>
                                    <span style="font-weight: 500;">
                                        <?php echo htmlspecialchars($row['aktivitas']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                                <td><?php echo htmlspecialchars($row['durasi_jam']); ?> Jam</td>
                                <td>
                                    <?php if ($row['verification_status']): ?>
                                        <span class="status-badge" style="background:#e5e7eb; color:#374151;">
                                            <?php echo htmlspecialchars($row['verification_status']); ?>
                                        </span>
                                    <?php elseif ($userPowerLevel < 5 && $row['users_id'] == $currentUserId): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="submit_verification" value="1">
                                            <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                            <button type="submit" style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                                                Ajukan Verifikasi
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="status-badge" style="background:#fee2e2; color:#991b1b;">Belum Diajukan</span>
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
</body>

</html>
<?php
require_once '../auth.php';
require_once '../Database.php';
require_once '../models/Verification.php';
requireRole(['Admin', 'Manager', 'Supervisor']);

$database = new Database();
$db = $database->getConnection();

$verification = new Verification($db);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['verification_id'] ?? null;
    $status = $_POST['status'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');

    if ($id && in_array($status, ['Disetujui', 'Ditolak'])) {
        if ($catatan === '') {
            $catatan = $status === 'Disetujui' ? 'Task disetujui.' : 'Task ditolak.';
        }

        $message = $verification->updateStatus($id, $status, $catatan)
            ? 'Status verifikasi berhasil diperbarui.'
            : 'Status verifikasi gagal diperbarui.';
    }
}

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$records_per_page = 5;
$from_record_num = ($records_per_page * $page) - $records_per_page;

$stmt = $verification->readPaging($from_record_num, $records_per_page);
$total_rows = $verification->count();
$total_pages = ceil($total_rows / $records_per_page);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi | Sistem Presensi</title>
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
            <li><a href="../users/index.php"><i class="fa-solid fa-users"></i> Data Karyawan</a></li>
            <li><a href="../kehadiran/index.php"><i class="fa-solid fa-clock-rotate-left"></i> Kehadiran</a></li>
            <li><a href="../tasks/index.php"><i class="fa-solid fa-list-check"></i> Tasks / Aktivitas</a></li>
            <li><a href="index.php" class="active"><i class="fa-solid fa-clipboard-check"></i> Verifikasi</a></li>
            <li style="margin-top: auto; padding-top: 20px;"><a href="../logout.php" style="color: #ef4444;"><i
                        class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="welcome-msg">
                <h1>Verifikasi Pekerjaan</h1>
                <p>Persetujuan dan penolakan hasil task/aktivitas.</p>
            </div>
        </header>

        <div class="section-card">
            <?php if ($message): ?>
                <div style="margin-bottom: 15px; padding: 12px; background: #dcfce7; color: #166534; border-radius: 8px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID TASK</th>
                            <th>NAMA USER</th>
                            <th>TANGGAL</th>
                            <th>AKTIVITAS</th>
                            <th>DURASI</th>
                            <th>CATATAN</th>
                            <th>STATUS</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($row['tasks_idtasks']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['tanggal'] ?? '-'); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['aktivitas'] ?? '-'); ?></strong><br>
                                    <small><?php echo htmlspecialchars($row['deskripsi'] ?? '-'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['durasi_jam'] ?? '-'); ?> Jam</td>
                                <td><?php echo htmlspecialchars($row['catatan']); ?></td>
                                <td>
                                    <?php if ($row['status'] == 'Disetujui'): ?>
                                        <span class="status-badge" style="background:#D1FAE5; color:#065F46;">Disetujui</span>
                                    <?php elseif ($row['status'] == 'Pending'): ?>
                                        <span class="status-badge" style="background:#FEF3C7; color:#92400E;">Pending</span>
                                    <?php else: ?>
                                        <span class="status-badge" style="background:#FEE2E2; color:#991B1B;">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'Pending'): ?>
                                        <form method="POST"
                                            style="display:flex; flex-direction:column; gap:6px; min-width:180px;">
                                            <input type="hidden" name="verification_id"
                                                value="<?php echo htmlspecialchars($row['id']); ?>">

                                            <input type="text" name="catatan" placeholder="Catatan"
                                                style="padding:7px; border:1px solid #d1d5db; border-radius:6px;">

                                            <button type="submit" name="status" value="Disetujui"
                                                style="padding:7px; background:#22c55e; color:white; border:none; border-radius:6px; cursor:pointer;">
                                                <i class="fa-solid fa-check"></i> Setujui
                                            </button>

                                            <button type="submit" name="status" value="Ditolak"
                                                style="padding:7px; background:#ef4444; color:white; border:none; border-radius:6px; cursor:pointer;">
                                                <i class="fa-solid fa-xmark"></i> Tolak
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">Sudah diproses</span>
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
                        <a href="?page=<?php echo $page - 1; ?>" class="page-link"><i class="fa-solid fa-chevron-left"></i>
                            Prev</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>"
                            class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="page-link">Next <i
                                class="fa-solid fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>
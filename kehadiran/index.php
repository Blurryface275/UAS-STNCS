<?php
require_once '../auth.php';
require_once '../Database.php';
require_once '../models/Kehadiran.php';
requireRole(['Admin', 'Direktur', 'Manager', 'Supervisor', 'Staff']);

$database = new Database();
$db = $database->getConnection();

$kehadiran = new Kehadiran($db);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];

    if (isset($_POST['clock_in'])) {
        $message = $kehadiran->clockIn($userId)
            ? 'Clock-in berhasil.'
            : 'Clock-in gagal. Kamu sudah clock-in hari ini.';
    }

    if (isset($_POST['clock_out'])) {
        $message = $kehadiran->clockOut($userId)
            ? 'Clock-out berhasil.'
            : 'Clock-out gagal. Kamu belum clock-in atau sudah clock-out.';
    }
}

$todayAttendance = $kehadiran->getTodayAttendance($_SESSION['user_id']);
$userPowerLevel = $_SESSION['power_level'] ?? 1;
$currentUserId = $_SESSION['user_id'];

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$records_per_page = 5;
$from_record_num = ($records_per_page * $page) - $records_per_page;

$filterPowerLevel = ($userPowerLevel < 5) ? $userPowerLevel : null;
$filterUserId = ($userPowerLevel < 5) ? $currentUserId : null;

$stmt = $kehadiran->readPaging($from_record_num, $records_per_page, $filterPowerLevel, $filterUserId);
$total_rows = $kehadiran->count($filterPowerLevel, $filterUserId);
$total_pages = ceil($total_rows / $records_per_page);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kehadiran | Sistem Presensi</title>
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
            <li><a href="index.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> Kehadiran</a></li>
            <li><a href="../tasks/index.php"><i class="fa-solid fa-list-check"></i> Tasks / Aktivitas</a></li>
            <?php if (isset($_SESSION['power_level']) && $_SESSION['power_level'] > 1): ?>
                <li><a href="../verification/index.php"><i class="fa-solid fa-clipboard-check"></i> Verifikasi</a></li>
            <?php endif; ?>
            <li style="margin-top: auto; padding-top: 20px;"><a href="../logout.php" style="color: #ef4444;"><i
                        class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="welcome-msg">
                <h1>Log Kehadiran</h1>
                <p>Melihat dan mencatat data check-in/out karyawan.</p>
            </div>
        </header>

        <div class="section-header">
            <h2>Data Kehadiran Terbaru</h2>
        </div>

        <?php if ($message): ?>
            <div style="margin-bottom: 15px; padding: 12px; background: #dcfce7; color: #166534; border-radius: 8px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 15px; color: var(--text-dark);">Pencatatan Kehadiran Anda</h3>
            <form method="POST" style="display: flex; gap: 10px;">
                <?php if (!$todayAttendance): ?>
                    <button type="submit" name="clock_in"
                        style="padding: 10px 20px; background: #22c55e; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        <i class="fa-solid fa-right-to-bracket"></i> Clock In Sekarang
                    </button>
                <?php elseif (empty($todayAttendance['clock_out'])): ?>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="padding: 10px 16px; background: #e0f2fe; color: #0369a1; border-radius: 8px; font-weight: 500;">
                            Waktu Masuk: <?php echo date('H:i', strtotime($todayAttendance['clock_in'])); ?>
                        </div>
                        <button type="submit" name="clock_out"
                            style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                            <i class="fa-solid fa-right-from-bracket"></i> Clock Out Selesai Kerja
                        </button>
                    </div>
                <?php else: ?>
                    <div style="padding: 12px 20px; background: #f3f4f6; color: #4b5563; border-radius: 8px; font-weight: 500;">
                        <i class="fa-solid fa-check-circle" style="color: #22c55e;"></i> Kehadiran hari ini sudah lengkap (Masuk: <?php echo date('H:i', strtotime($todayAttendance['clock_in'])); ?>, Pulang: <?php echo date('H:i', strtotime($todayAttendance['clock_out'])); ?>). Selamat beristirahat!
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>NAMA</th>
                        <th>TANGGAL</th>
                        <th>CLOCK IN</th>
                        <th>CLOCK OUT</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td style="font-weight: 500; color: var(--text-dark);">
                                <?php echo htmlspecialchars($row['user_name'] ?? ''); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['tanggal'] ?? ''); ?></td>
                            <td>
                                <?php if (!empty($row['clock_in'])): ?>
                                    <span style="color: #166534;"><i class="fa-solid fa-clock"></i> <?php echo date('H:i', strtotime($row['clock_in'])); ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['clock_out'])): ?>
                                    <span style="color: #991b1b;"><i class="fa-solid fa-clock"></i> <?php echo date('H:i', strtotime($row['clock_out'])); ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['clock_in']) && !empty($row['clock_out'])): ?>
                                    <span class="status-badge" style="background: #dcfce7; color: #166534;">Selesai</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background: #fef08a; color: #854d0e;">Bekerja</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($stmt->rowCount() === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 20px;">Belum ada data kehadiran divisi Anda.</td>
                        </tr>
                    <?php endif; ?>
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
                    <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="page-link">Next <i class="fa-solid fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</body>

</html>
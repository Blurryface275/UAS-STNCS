<?php
require_once '../auth.php';
require_once '../Database.php';
require_once '../models/Kehadiran.php';
requireRole(['Admin', 'Karyawan']);

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
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$records_per_page = 5;
$from_record_num = ($records_per_page * $page) - $records_per_page;

$stmt = $kehadiran->readPaging($from_record_num, $records_per_page);
$total_rows = $kehadiran->count();
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
            <li><a href="../users/index.php"><i class="fa-solid fa-users"></i> Data Karyawan</a></li>
            <li><a href="index.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> Kehadiran</a></li>
            <li><a href="../tasks/index.php"><i class="fa-solid fa-list-check"></i> Tasks / Aktivitas</a></li>
            <li><a href="../verification/index.php"><i class="fa-solid fa-clipboard-check"></i> Verifikasi</a></li>
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

        <?php if ($_SESSION['user_role'] === 'Karyawan'): ?>
            <form method="POST" style="margin-bottom: 20px; display: flex; gap: 10px;">
                <?php if (!$todayAttendance): ?>
                    <button type="submit" name="clock_in"
                        style="padding: 10px 16px; background: #22c55e; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        <i class="fa-solid fa-right-to-bracket"></i> Clock In
                    </button>
                <?php elseif (empty($todayAttendance['clock_out'])): ?>
                    <button type="submit" name="clock_out"
                        style="padding: 10px 16px; background: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        <i class="fa-solid fa-right-from-bracket"></i> Clock Out
                    </button>
                <?php else: ?>
                    <div style="padding: 10px 16px; background: #e5e7eb; border-radius: 8px;">
                        Kehadiran hari ini sudah lengkap.
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>

    </main>
</body>

</html>
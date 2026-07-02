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
    $action        = $_POST['action'] ?? '';
    $userId        = $_SESSION['user_id'];
    $latitude_in   = $_POST['latitude_in'] ?? null;
    $longitude_in  = $_POST['longitude_in'] ?? null;
    $latitude_out  = $_POST['latitude_out'] ?? null;
    $longitude_out = $_POST['longitude_out'] ?? null;

    if ($action === 'clock_in') {
        $message = $kehadiran->clockIn($userId, $latitude_in, $longitude_in)
            ? 'Clock-in berhasil.'
            : 'Clock-in gagal. Kamu sudah clock-in hari ini.';
    }

    if ($action === 'clock_out') {
        $message = $kehadiran->clockOut($userId, $latitude_out, $longitude_out)
            ? 'Clock-out berhasil.'
            : 'Clock-out gagal. Kamu belum clock-in atau sudah clock-out.';
    }
}

$todayAttendance = $kehadiran->getTodayAttendance($_SESSION['user_id']);
$userPowerLevel  = $_SESSION['power_level'] ?? 1;
$currentUserId   = $_SESSION['user_id'];
$division        = $_SESSION['user_division'];
$userRole        = $_SESSION['user_role'];

$filterPowerLevel = ($userPowerLevel < 5) ? $userPowerLevel : null;
$filterUserId     = ($userPowerLevel < 5) ? $currentUserId : null;

$page             = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$records_per_page = 5;
$from_record_num  = ($records_per_page * $page) - $records_per_page;
$filter           = $_GET['filter'] ?? 'all';

switch ($filter) {
    case 'pribadi':
        $stmt       = $kehadiran->getAttendanceByUserPaging($from_record_num, $records_per_page, $currentUserId);
        $total_rows = $kehadiran->countByUser($currentUserId);
        break;

    case 'karyawan':
        $stmt       = $kehadiran->readPagingByTipeUser($from_record_num, $records_per_page, $division, $_SESSION['user_role_id']);
        $total_rows = $kehadiran->countByTipeUser($division, $_SESSION['user_role_id']);
        break;

    default: // semua
        $stmt       = $kehadiran->readPagingAll($from_record_num, $records_per_page, $division, $_SESSION['user_role_id'], $currentUserId);
        $total_rows = $kehadiran->countAll($division, $_SESSION['user_role_id'], $currentUserId);
        break;
}
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

        <div class="card-container">
            <div class="card-absensi">
                <h3>Pencatatan Kehadiran Anda</h3>
                <form id="attendanceForm" method="POST" class="form-absensi">
                    <input type="hidden" name="action" id="action">
                    <?php if (!$todayAttendance): ?>
                        <button type="submit" id="btnClockIn" name="clock_in" class="btn-clockin">
                            <span class="btn-main">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                Clock In Sekarang
                            </span>
                            <small>(Pastikan lokasi GPS aktif)</small>
                        </button>
                        <input type="hidden" name="latitude_in" id="latitude_in">
                        <input type="hidden" name="longitude_in" id="longitude_in">
                    <?php elseif (empty($todayAttendance['clock_out'])): ?>
                        <div class="info-masuk">
                            Waktu Masuk: <?php echo date('H:i', strtotime($todayAttendance['clock_in'])); ?>
                        </div>
                        <button type="submit" id="btnClockOut" name="clock_out" class="btn-clockout">
                            <span class="btn-main">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                Clock Out Selesai Kerja
                            </span>
                        </button>
                        <input type="hidden" name="latitude_out" id="latitude_out">
                        <input type="hidden" name="longitude_out" id="longitude_out">
                    <?php else: ?>
                        <div class="info-lengkap">
                            <i class="fa-solid fa-check-circle"></i> Kehadiran hari ini sudah lengkap
                            (Masuk: <?php echo date('H:i', strtotime($todayAttendance['clock_in'])); ?>,
                            Pulang: <?php echo date('H:i', strtotime($todayAttendance['clock_out'])); ?>).
                            Selamat beristirahat!
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($_SESSION['user_role_id'] != 5): ?>
                <div class="card-absensi">
                    <h3>Log Kehadiran</h3>
                    <div class="btn-row">
                        <a href="index.php" class="btn-log-semua">
                            <i class="fa-solid fa-users"></i> Semua
                        </a>
                        <a href="index.php?filter=pribadi" class="btn-log-pribadi">
                            <i class="fa-solid fa-user-clock"></i> Pribadi
                        </a>
                        <a href="index.php?filter=karyawan" class="btn-log-karyawan">
                            <i class="fa-solid fa-users"></i> Karyawan
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <h3 class="table-title"></h3>
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>NAMA</th>
                        <th>TANGGAL</th>
                        <th>CLOCK IN</th>
                        <th>Lokasi In</th>
                        <th>CLOCK OUT</th>
                        <th>Lokasi Out</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td class="name-cell">
                                <?php echo htmlspecialchars($row['user_name'] ?? ''); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['tanggal'] ?? ''); ?></td>
                            <td>
                                <?php if (!empty($row['clock_in'])): ?>
                                    <span class="clock-in">
                                        <i class="fa-solid fa-clock"></i>
                                        <?php echo date('H:i', strtotime($row['clock_in'])); ?>
                                    </span>
                                <?php else: ?> -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['latitude_in']) && !empty($row['longitude_in'])): ?>
                                    <a href="https://www.google.com/maps?q=<?php echo $row['latitude_in']; ?>,<?php echo $row['longitude_in']; ?>" target="_blank" class="map-link in">
                                        Lihat di Maps
                                    </a>
                                <?php else: ?> -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['clock_out'])): ?>
                                    <span class="clock-out">
                                        <i class="fa-solid fa-clock"></i>
                                        <?php echo date('H:i', strtotime($row['clock_out'])); ?>
                                    </span>
                                <?php else: ?> -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['latitude_out']) && !empty($row['longitude_out'])): ?>
                                    <a href="https://www.google.com/maps?q=<?php echo $row['latitude_out']; ?>,<?php echo $row['longitude_out']; ?>" target="_blank" class="map-link out">
                                        Lihat di Maps
                                    </a>
                                <?php else: ?> -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['clock_in']) && !empty($row['clock_out'])): ?>
                                    <span class="status-badge selesai">Selesai</span>
                                <?php else: ?>
                                    <span class="status-badge bekerja">Bekerja</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($stmt->rowCount() === 0): ?>
                        <tr>
                            <td colspan="7" class="empty-data">Belum ada data kehadiran divisi Anda.</td>
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
        </div>

    </main>
</body>

</html>

<script>
    const form = document.getElementById("attendanceForm");
    const btnClockIn = document.getElementById("btnClockIn");
    const btnClockOut = document.getElementById("btnClockOut");

    if (btnClockIn) {
        btnClockIn?.addEventListener("click", function(e) {
            e.preventDefault();
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    document.getElementById("latitude_in").value = pos.coords.latitude;
                    document.getElementById("longitude_in").value = pos.coords.longitude;
                    document.getElementById("action").value = "clock_in";

                    form.submit();
                }, function(err) {
                    alert("Lokasi gagal: " + err.message);
                    form.submit();

                });
            } else {
                form.submit();
            }
        });
    }

    if (btnClockOut) {
        btnClockOut?.addEventListener("click", function(e) {
            e.preventDefault();
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    document.getElementById("latitude_out").value = pos.coords.latitude;
                    document.getElementById("longitude_out").value = pos.coords.longitude;
                    document.getElementById("action").value = "clock_out";

                    form.submit();
                }, function(err) {
                    alert("Lokasi gagal: " + err.message);
                    form.submit();

                });
            } else {
                form.submit();
            }
        });
    }
</script>
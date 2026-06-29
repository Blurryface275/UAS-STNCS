<?php
// Include the database, auth and models
require_once 'auth.php';
require_once 'Database.php';
require_once 'models/User.php';
require_once 'models/Kehadiran.php';
require_once 'models/Task.php';
require_once 'models/Verification.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Koneksi database gagal. Pastikan XAMPP nyala dan .env dikonfigurasi dengan benar.");
}

$user = new User($db);
$kehadiran = new Kehadiran($db);
$task = new Task($db);
$verification = new Verification($db);

$userPowerLevel = $_SESSION['power_level'] ?? 1;
$currentUserId = $_SESSION['user_id'] ?? null;

$filterPowerLevel = ($userPowerLevel < 5) ? $userPowerLevel : null;
$filterUserId = ($userPowerLevel < 5) ? $currentUserId : null;

$stmtUsers = $user->read();
$totalUsers = $stmtUsers->rowCount();

$stmtKehadiran = $kehadiran->read();
$totalKehadiran = $stmtKehadiran->rowCount();

$totalTask = $task->count($filterPowerLevel, $filterUserId);

$totalPending = $verification->count($filterPowerLevel, 'Pending');

$recentUsersQuery = "SELECT u.nama, u.email, u.divisi, u.status, t.nama as tipe_user 
                     FROM users u 
                     LEFT JOIN tipe_users t ON u.tipe_users_id = t.id 
                     ORDER BY u.id DESC LIMIT 5";
$recentUsersStmt = $db->prepare($recentUsersQuery);
$recentUsersStmt->execute();

$recentTasksQuery = "SELECT t.aktivitas, t.tanggal, v.status as verification_status 
                     FROM tasks t 
                     LEFT JOIN verifications v ON t.id = v.tasks_idtasks 
                     WHERE t.assignee_id = :user_id 
                     ORDER BY t.id DESC LIMIT 5";
$recentTasksStmt = $db->prepare($recentTasksQuery);
$recentTasksStmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
$recentTasksStmt->execute();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sistem Presensi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-fingerprint fa-2x" style="color: var(--primary-light);"></i>
            <h2>Presensi<span style="color: white; font-weight: 300;">Pro</span></h2>
        </div>
        <ul class="nav-links">
            <li><a href="index.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin'): ?>
                <li><a href="users/index.php"><i class="fa-solid fa-users"></i> Data Karyawan</a></li>
            <?php endif; ?>
            <li><a href="kehadiran/index.php"><i class="fa-solid fa-clock-rotate-left"></i> Kehadiran</a></li>
            <li><a href="tasks/index.php"><i class="fa-solid fa-list-check"></i> Tasks / Aktivitas</a></li>
            <?php if (isset($_SESSION['power_level']) && $_SESSION['power_level'] > 1): ?>
                <li><a href="verification/index.php"><i class="fa-solid fa-clipboard-check"></i> Verifikasi</a></li>
            <?php endif; ?>
            <li style="margin-top: auto; padding-top: 20px;"><a href="logout.php" style="color: #ef4444;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="welcome-msg">
                <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>! 👋</h1>
                <p>Berikut adalah ringkasan data presensi hari ini.</p>
            </div>
            <div class="user-profile">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name'] ?? 'Admin User'); ?>&background=4F46E5&color=fff" alt="Admin Profile">
                <div>
                    <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin User'); ?></span>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">Role: <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'User'); ?></div>
                </div>
                <i class="fa-solid fa-chevron-down" style="font-size: 0.8rem; color: var(--text-muted);"></i>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Karyawan</h3>
                    <div class="value"><?php echo $totalUsers; ?></div>
                </div>
                <div class="stat-icon icon-blue">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Kehadiran</h3>
                    <div class="value"><?php echo $totalKehadiran; ?></div>
                </div>
                <div class="stat-icon icon-green">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Task</h3>
                    <div class="value"><?php echo $totalTask; ?></div>
                </div>
                <div class="stat-icon icon-purple">
                    <i class="fa-solid fa-tasks"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Menunggu Verifikasi</h3>
                    <div class="value"><?php echo $totalPending; ?></div>
                </div>
                <div class="stat-icon icon-orange">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>
        </div>

        <div class="dashboard-sections">
            <?php if ($userPowerLevel > 1): ?>
                <div class="section-card">
                    <div class="section-header">
                        <h2>Karyawan Terbaru</h2>
                        <a href="users/index.php" class="view-all">Lihat Semua</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Divisi</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $recentUsersStmt->fetch(PDO::FETCH_ASSOC)) : ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 500;"><?php echo htmlspecialchars($row['nama']); ?></div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['divisi']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tipe_user']); ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'Aktif') : ?>
                                                <span class="status-badge status-aktif">Aktif</span>
                                            <?php else : ?>
                                                 <span class="status-badge status-nonaktif">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="section-card">
                    <div class="section-header">
                        <h2>Tugas Terbaru Anda</h2>
                        <a href="tasks/index.php" class="view-all">Lihat Tugas Saya</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Aktivitas</th>
                                    <th>Tanggal</th>
                                    <th>Status Verifikasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $recentTasksStmt->fetch(PDO::FETCH_ASSOC)) : ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?php echo htmlspecialchars($row['aktivitas']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tanggal']); ?></td>
                                        <td>
                                            <?php if ($row['verification_status']): ?>
                                                <span class="status-badge" style="background:#e5e7eb; color:#374151;">
                                                    <?php echo htmlspecialchars($row['verification_status']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge" style="background:#fee2e2; color:#991b1b;">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php if ($recentTasksStmt->rowCount() === 0): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 20px;">Anda belum mendapatkan penugasan task.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>

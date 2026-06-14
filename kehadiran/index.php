<?php
require_once '../auth.php';
require_once '../Database.php';
require_once '../models/Kehadiran.php';

$database = new Database();
$db = $database->getConnection();

$kehadiran = new Kehadiran($db);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
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
            <li style="margin-top: auto; padding-top: 20px;"><a href="../logout.php" style="color: #ef4444;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="welcome-msg">
                <h1>Log Kehadiran</h1>
                <p>Melihat dan mencatat data check-in/out karyawan.</p>
            </div>
        </header>

        <div class="section-card">
            <div class="section-header">
                <h2>Data Kehadiran Terbaru</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID USER</th>
                            <th>NAMA USER</th>
                            <th>TANGGAL</th>
                            <th>CLOCK IN</th>
                            <th>CLOCK OUT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['users_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['tanggal']); ?></td>
                                <td><?php echo htmlspecialchars($row['clock_in']); ?></td>
                                <td><?php echo htmlspecialchars($row['clock_out']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1) : ?>
                <div class="pagination">
                    <?php if ($page > 1) : ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="page-link"><i class="fa-solid fa-chevron-left"></i> Prev</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages) : ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="page-link">Next <i class="fa-solid fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>

<?php
require_once '../auth.php';
require_once '../Database.php';
require_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 5;
$from_record_num = ($records_per_page * $page) - $records_per_page;

$stmtUsers = $user->readPaging($from_record_num, $records_per_page);
$total_rows = $user->count();
$total_pages = ceil($total_rows / $records_per_page);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Karyawan | Sistem Presensi</title>
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
            <li><a href="index.php" class="active"><i class="fa-solid fa-users"></i> Data Karyawan</a></li>
            <li><a href="../kehadiran/index.php"><i class="fa-solid fa-clock-rotate-left"></i> Kehadiran</a></li>
            <li><a href="../tasks/index.php"><i class="fa-solid fa-list-check"></i> Tasks / Aktivitas</a></li>
            <li><a href="../verification/index.php"><i class="fa-solid fa-clipboard-check"></i> Verifikasi</a></li>
            <li style="margin-top: auto; padding-top: 20px;"><a href="../logout.php" style="color: #ef4444;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="welcome-msg">
                <h1>Data Karyawan</h1>
                <p>Manajemen data user dan karyawan.</p>
            </div>
        </header>

        <div class="section-card">
            <div class="section-header">
                <h2>Seluruh Karyawan</h2>
                <button style="padding: 8px 16px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">
                    <i class="fa-solid fa-plus"></i> Tambah Divisi / Karyawan
                </button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>NAMA & EMAIL</th>
                            <th>DIVISI</th>
                            <th>STATUS</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmtUsers->fetch(PDO::FETCH_ASSOC)) : ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($row['nama']); ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($row['divisi']); ?></td>
                                <td>
                                    <?php if ($row['status'] == 'Aktif') : ?>
                                        <span class="status-badge status-aktif">Aktif</span>
                                    <?php else : ?>
                                        <span class="status-badge status-nonaktif">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="#" style="color: var(--primary); margin-right: 10px;"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <a href="#" style="color: #ef4444;"><i class="fa-solid fa-trash"></i></a>
                                </td>
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

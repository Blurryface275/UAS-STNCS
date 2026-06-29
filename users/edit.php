<?php
require_once '../auth.php';
requireRole(['Admin']);
require_once '../Database.php';
require_once '../models/User.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->id = $_GET['id'];

if (!$user->readOne()) {
    header("Location: index.php");
    exit();
}

$stmtTipe = $db->query("SELECT id, nama FROM tipe_users ORDER BY id ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user->nama = $_POST['nama'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password']; // If empty, model handles it to not change
    $user->divisi = $_POST['divisi'];
    $user->status = $_POST['status'];
    $user->tipe_users_id = $_POST['tipe_users_id'];

    if ($user->update()) {
        header("Location: index.php?msg=updated");
        exit();
    } else {
        $error = "Terjadi kesalahan saat mengupdate data.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Karyawan | Sistem Presensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <main class="main-content" style="margin-left: 0; padding: 40px; display: flex; justify-content: center;">
        <div class="section-card" style="max-width: 600px; width: 100%;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <h2 style="margin: 0;">Edit Data Karyawan</h2>
                <a href="index.php" style="color: var(--text-muted); text-decoration: none;"><i class="fa-solid fa-xmark fa-lg"></i></a>
            </div>

            <?php if (isset($error)): ?>
                <div style="margin-bottom: 15px; padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 8px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 0.9rem; margin-bottom: 5px; color: var(--text-dark);">Nama Lengkap</label>
                    <input type="text" name="nama" value="<?php echo htmlspecialchars($user->nama); ?>" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 0.9rem; margin-bottom: 5px; color: var(--text-dark);">Email Karyawan</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user->email); ?>" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 0.9rem; margin-bottom: 5px; color: var(--text-dark);">Ganti Password (Opsional)</label>
                    <input type="text" name="password" placeholder="Kosongkan jika tidak ingin mengubah password" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 0.9rem; margin-bottom: 5px; color: var(--text-dark);">Status Karyawan</label>
                    <select name="status" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: white;">
                        <option value="Aktif" <?php echo $user->status == 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="Nonaktif" <?php echo $user->status == 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 0.9rem; margin-bottom: 5px; color: var(--text-dark);">Divisi</label>
                    <select name="divisi" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: white;">
                        <?php 
                        $divs = ['IT', 'HR', 'Finance', 'Marketing', 'Operasional'];
                        foreach ($divs as $d) {
                            $sel = $user->divisi == $d ? 'selected' : '';
                            echo "<option value='$d' $sel>$d</option>";
                        }
                        ?>
                    </select>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 0.9rem; margin-bottom: 5px; color: var(--text-dark);">Jabatan / Peran (Tipe User)</label>
                    <select name="tipe_users_id" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: white;">
                        <?php while ($tRow = $stmtTipe->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $tRow['id']; ?>" <?php echo $user->tipe_users_id == $tRow['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($tRow['nama']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" style="width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </main>
</body>
</html>

<?php
require_once 'Database.php';
require_once 'models/User.php';

session_start();

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error_message = '';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error_message = 'Email dan password harus diisi.';
    } else {
        $userData = $user->login($email, $password);

        if ($userData) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_name'] = $userData['nama'];
            $_SESSION['user_email'] = $userData['email'];
            $_SESSION['user_role_id'] = $userData['tipe_users_id'];
            $_SESSION['user_role'] = $userData['role_name'] ?? 'User';

            header('Location: index.php');
            exit();
        } else {
            $error_message = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistem Presensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h1>Login Sistem Presensi</h1>
        <p>Masukkan email dan password untuk masuk ke dashboard.</p>

        <?php if ($error_message) : ?>
            <div class="login-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="email@domain.com" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>

            <button type="submit"><i class="fa-solid fa-right-to-bracket"></i> Login</button>
        </form>
    </div>
</body>
</html>
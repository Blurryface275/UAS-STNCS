<?php
require_once '../auth.php';
requireRole(['Admin']);
require_once '../Database.php';
require_once '../models/User.php';

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $user->id = $_GET['id'];
    
    // Cegah Admin menghapus dirinya sendiri
    if ($user->id == $_SESSION['user_id']) {
        header("Location: index.php?msg=error");
        exit();
    }

    if ($user->delete()) {
        header("Location: index.php?msg=deleted");
    } else {
        header("Location: index.php?msg=error");
    }
} else {
    header("Location: index.php");
}
?>

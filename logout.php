<?php
session_start();
// Destroy session when implemented
session_destroy();
// Redirect to login or index
header("Location: index.php");
exit();
?>

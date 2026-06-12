<?php
session_start();

// Cek sudah login
if (isset($_SESSION['id_user'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
        header('Location: modules/dashboard/index.php');
    } else {
        header('Location: modules/transaksi/index.php');
    }
    exit;
}

// Redirect ke login
header('Location: modules/auth/login.php');
exit;
?>
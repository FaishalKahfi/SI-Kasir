<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header('Location: /uts/si-kasir/modules/auth/login.php');
    exit;
}

// Helper: cek role admin
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
}

// Helper: khusus admin only
function requireAdmin()
{
    if (!isAdmin()) {
        die('<div class="alert alert-danger">Akses ditolak! Halaman ini khusus Admin.</div>');
    }
}

// Helper: cek role kasir
function isKasir()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Kasir';
}

// Helper: khusus kasir only
function requireKasir()
{
    if (!isKasir()) {
        die('<div class="alert alert-danger">Akses ditolak! Halaman ini khusus Kasir.</div>');
    }
}
?>
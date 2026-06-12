<?php

$baseUrl = '/uts/si-kasir';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SI-KASIR — Sistem Informasi Kasir Terintegrasi Toko Maju Jaya">
    <title><?= htmlspecialchars($pageTitle ?? 'SI-KASIR') ?> — SI-KASIR</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">SI<span>-KASIR</span></div>

            <?php if (isKasir()): ?>
                <a href="<?= $baseUrl ?>/modules/transaksi/index.php"
                    class="<?= strpos($_SERVER['PHP_SELF'], 'transaksi') !== false ? 'active' : '' ?>">
                    <i class="fas fa-cash-register"></i> Transaksi
                </a>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
                <a href="<?= $baseUrl ?>/modules/dashboard/index.php"
                    class="<?= strpos($_SERVER['PHP_SELF'], 'dashboard') !== false ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="<?= $baseUrl ?>/modules/produk/index.php"
                    class="<?= strpos($_SERVER['PHP_SELF'], 'produk') !== false ? 'active' : '' ?>">
                    <i class="fas fa-boxes-stacked"></i> Produk
                </a>
                <a href="<?= $baseUrl ?>/modules/laporan/penjualan.php"
                    class="<?= strpos($_SERVER['PHP_SELF'], 'penjualan') !== false ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> Laporan Penjualan
                </a>
                <a href="<?= $baseUrl ?>/modules/laporan/riwayat_stok.php"
                    class="<?= strpos($_SERVER['PHP_SELF'], 'riwayat_stok') !== false ? 'active' : '' ?>">
                    <i class="fas fa-exchange-alt"></i> Riwayat Stok
                </a>
                <a href="<?= $baseUrl ?>/modules/auth/register.php"
                    class="<?= strpos($_SERVER['PHP_SELF'], 'register') !== false ? 'active' : '' ?>">
                    <i class="fas fa-users-gear"></i> Manajemen User
                </a>
            <?php endif; ?>

            <div class="user-info">
                <i class="fas fa-user-circle" style="font-size: 1.5rem; margin-bottom: 8px;"></i>
                <strong style="margin-top: 0; margin-bottom: 2px;"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></strong>
                <small style="display: block; margin-bottom: 16px;"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></small>
                <a href="<?= $baseUrl ?>/modules/auth/profil.php"
                    class="btn btn-outline btn-sm <?= strpos($_SERVER['PHP_SELF'], 'profil') !== false ? 'active' : '' ?>"
                    style="width:100%;justify-content:center;margin-bottom:8px;">
                    <i class="fas fa-user-edit"></i> Edit Profil
                </a>
                <a href="<?= $baseUrl ?>/modules/auth/logout.php" class="btn btn-outline btn-sm"
                    style="width:100%;justify-content:center;">
                    <i class="fas fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
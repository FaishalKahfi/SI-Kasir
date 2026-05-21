<?php

require_once __DIR__ . '/../../includes/auth_helper.php';
requireKasir();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Nota Penjualan';

if (!isset($_SESSION['last_nota'])) {
    header('Location: index.php');
    exit;
}

$nota = $_SESSION['last_nota'];

// Ambil detail
$stmt = $pdo->prepare("
    SELECT d.*, p.nama_produk
    FROM t_penjualan_detail d
    JOIN m_produk p ON d.id_produk = p.id_produk
    WHERE d.id_penjualan = ?
");
$stmt->execute([$nota['id_penjualan']]);
$details = $stmt->fetchAll();

// Ambil header
$hdr = $pdo->prepare("SELECT t.*, u.username FROM t_penjualan t JOIN m_user u ON t.id_user = u.id_user WHERE t.id_penjualan = ?");
$hdr->execute([$nota['id_penjualan']]);
$header = $hdr->fetch();

// Clear nota dari session agar tidak bisa di-refresh
unset($_SESSION['last_nota']);

require_once __DIR__ . '/../../includes/header.php';
?>

<h2 style="margin-bottom:24px;font-weight:800;"><i class="fas fa-receipt"></i> Nota Penjualan</h2>

<div class="card" style="max-width:480px;margin:0 auto;">
    <div class="nota">
        <h2>TOKO MAJU JAYA</h2>
        <p style="text-align:center;font-size:0.75rem;color:#666;">Jl. Maju Jaya No. 1 — Telp: (021) 123-4567</p>
        <div class="nota-line"></div>

        <table style="width:100%;">
            <tr>
                <td>No. Nota</td>
                <td style="text-align:right;font-weight:bold;"><?= htmlspecialchars($header['nomor_nota']) ?></td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td style="text-align:right;"><?= date('d/m/Y H:i', strtotime($header['tgl_transaksi'])) ?></td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td style="text-align:right;"><?= htmlspecialchars($header['username']) ?></td>
            </tr>
        </table>

        <div class="nota-line"></div>

        <table style="width:100%;">
            <thead>
                <tr>
                    <td><strong>Barang</strong></td>
                    <td style="text-align:center;"><strong>Qty</strong></td>
                    <td style="text-align:right;"><strong>Subtotal</strong></td>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['nama_produk']) ?></td>
                        <td style="text-align:center;"><?= $d['qty'] ?></td>
                        <td style="text-align:right;">Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="nota-line"></div>

        <table style="width:100%;font-size:0.85rem;">
            <tr>
                <td><strong>Total</strong></td>
                <td style="text-align:right;font-weight:bold;font-size:1rem;">Rp
                    <?= number_format($nota['total'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Bayar</td>
                <td style="text-align:right;">Rp <?= number_format($nota['bayar'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td><strong>Kembalian</strong></td>
                <td style="text-align:right;font-weight:bold;color:#00b894;">Rp
                    <?= number_format($nota['kembalian'], 0, ',', '.') ?></td>
            </tr>
        </table>

        <div class="nota-line"></div>
        <p style="text-align:center;font-size:0.7rem;color:#999;">Terima kasih atas kunjungan Anda!<br>Barang yang sudah
            dibeli tidak dapat dikembalikan.</p>
    </div>

    <div style="display:flex;gap:12px;margin-top:20px;justify-content:center;">
        <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i> Cetak</button>
        <a href="index.php" class="btn btn-primary"><i class="fas fa-plus"></i> Transaksi Baru</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?php

require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$pageTitle = 'Riwayat Mutasi Stok';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1)
    $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$totalRows = $pdo->query("SELECT COUNT(*) FROM t_log_stok")->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->query("
    SELECT l.*, p.nama_produk
    FROM t_log_stok l
    INNER JOIN m_produk p ON l.id_produk = p.id_produk
    ORDER BY l.waktu_log DESC
    LIMIT $limit OFFSET $offset
");
$mutasiStok = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<h2 style="margin-bottom:24px;font-weight:800;"><i class="fas fa-exchange-alt"></i> Riwayat Mutasi Stok</h2>

<div class="card">
    <div class="card-header">Riwayat Mutasi Stok Terbaru</div>
    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Produk</th>
                <th>Tipe</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mutasiStok)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;color:var(--text-muted);padding:40px;">Belum ada data mutasi.
                    </td>
                </tr>
            <?php else:
                foreach ($mutasiStok as $m): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($m['waktu_log'])) ?></td>
                        <td><strong><?= htmlspecialchars($m['nama_produk']) ?></strong></td>
                        <td>
                            <?php if ($m['tipe'] === 'Masuk'): ?>
                                <span class="badge badge-success">↑ Masuk</span>
                            <?php else: ?>
                                <span class="badge badge-danger">↓ Keluar</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $m['jumlah'] ?></td>
                        <td style="color:var(--text-muted);font-size:0.85rem;"><?= htmlspecialchars($m['keterangan'] ?? '-') ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <div style="display:flex;gap:8px;justify-content:center;margin-top:20px;margin-bottom:20px;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?> btn-sm"
                style="padding: 8px 12px;"><?= $i ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?php

require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$pageTitle = 'Laporan & Analisis';

$tab = $_GET['tab'] ?? 'harian';
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

//Laporan Penjualan Harian
$laporanHarian = [];
if ($tab === 'harian') {
    $stmt = $pdo->prepare("
        SELECT p.id_penjualan, p.nomor_nota, p.tgl_transaksi, p.total_bayar, u.username AS kasir
        FROM t_penjualan p
        INNER JOIN m_user u ON p.id_user = u.id_user
        WHERE DATE(p.tgl_transaksi) = ?
        ORDER BY p.tgl_transaksi DESC
    ");
    $stmt->execute([$tanggal]);
    $laporanHarian = $stmt->fetchAll();

    $totalHarian = $pdo->prepare("SELECT COALESCE(SUM(total_bayar),0) FROM t_penjualan WHERE DATE(tgl_transaksi) = ?");
    $totalHarian->execute([$tanggal]);
    $totalHarianVal = $totalHarian->fetchColumn();

    $jumlahNota = count($laporanHarian);
}


// Detail Nota
$headerNota = null;
$detailNota = [];
if ($tab === 'detail_nota') {
    $id_penjualan = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    $hdr = $pdo->prepare("SELECT t.*, u.username FROM t_penjualan t JOIN m_user u ON t.id_user = u.id_user WHERE t.id_penjualan = ?");
    $hdr->execute([$id_penjualan]);
    $headerNota = $hdr->fetch();
    
    if ($headerNota) {
        $stmt = $pdo->prepare("
            SELECT d.*, p.nama_produk
            FROM t_penjualan_detail d
            JOIN m_produk p ON d.id_produk = p.id_produk
            WHERE d.id_penjualan = ?
        ");
        $stmt->execute([$id_penjualan]);
        $detailNota = $stmt->fetchAll();
    }
}

// Detail Laporan (Best Seller)
$bestSeller = [];
$totalPages = 1;
$page = 1;
$offset = 0;
if ($tab === 'detail') {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    if ($page < 1)
        $page = 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $totalQuery = $pdo->query("SELECT COUNT(DISTINCT id_produk) FROM t_penjualan_detail");
    $totalRows = $totalQuery->fetchColumn();
    $totalPages = ceil($totalRows / $limit);

    $stmt = $pdo->query("
        SELECT p.nama_produk, SUM(d.qty) AS total_qty, SUM(d.subtotal) AS total_penjualan
        FROM t_penjualan_detail d
        INNER JOIN m_produk p ON d.id_produk = p.id_produk
        INNER JOIN t_penjualan pj ON d.id_penjualan = pj.id_penjualan
        GROUP BY d.id_produk, p.nama_produk
        ORDER BY total_qty DESC
        LIMIT $limit OFFSET $offset
    ");
    $bestSeller = $stmt->fetchAll();
}

// Stats global
$totalPendapatan = $pdo->query("SELECT COALESCE(SUM(total_bayar),0) FROM t_penjualan")->fetchColumn();
$totalTransaksi = $pdo->query("SELECT COUNT(*) FROM t_penjualan")->fetchColumn();
$totalProduk = $pdo->query("SELECT COUNT(*) FROM m_produk")->fetchColumn();

require_once __DIR__ . '/../../includes/header.php';
?>

<h2 style="margin-bottom:24px;font-weight:800;"><i class="fas fa-chart-line"></i> Dashboard Laporan</h2>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="label">Total Pendapatan</div>
        <div class="value success">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Total Transaksi</div>
        <div class="value accent"><?= $totalTransaksi ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Total Produk</div>
        <div class="value warning"><?= $totalProduk ?></div>
    </div>
</div>

<!-- Tabs -->
<div class="card">
    <div style="display:flex;gap:8px;margin-bottom:20px;">
        <a href="?tab=harian" class="btn <?= $tab === 'harian' ? 'btn-primary' : 'btn-outline' ?>"><i
                class="fas fa-calendar-day"></i> Penjualan Harian</a>
        <a href="?tab=detail" class="btn <?= $tab === 'detail' ? 'btn-primary' : 'btn-outline' ?>"><i
                class="fas fa-trophy"></i> Best Seller</a>
    </div>

    <?php if ($tab === 'harian'): ?>
        <!-- LAPORAN HARIAN -->
        <div class="card-header">
            <span>Laporan Penjualan Harian</span>
            <form method="GET" style="display:flex;gap:8px;align-items:center;">
                <input type="hidden" name="tab" value="harian">
                <input type="date" name="tanggal" class="form-control" value="<?= $tanggal ?>" style="width:auto;">
                <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-filter"></i> Filter</button>
            </form>
        </div>

        <div class="stats-grid" style="margin:16px 0;">
            <div class="stat-card">
                <div class="label">Pendapatan Hari Ini</div>
                <div class="value success">Rp <?= number_format($totalHarianVal, 0, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Jumlah Nota</div>
                <div class="value accent"><?= $jumlahNota ?></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>No. Nota</th>
                    <th>Tanggal</th>
                    <th>Kasir</th>
                    <th>Total</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laporanHarian)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:40px;">Belum ada transaksi pada
                            tanggal ini.</td>
                    </tr>
                <?php else:
                    foreach ($laporanHarian as $l): ?>
                        <tr>
                            <td><span class="badge badge-accent"><?= htmlspecialchars($l['nomor_nota']) ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($l['tgl_transaksi'])) ?></td>
                            <td><?= htmlspecialchars($l['kasir']) ?></td>
                            <td><strong>Rp <?= number_format($l['total_bayar'], 0, ',', '.') ?></strong></td>
                            <td>
                                <a href="?tab=detail_nota&id=<?= $l['id_penjualan'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>

    <?php elseif ($tab === 'detail'): ?>
        <!-- BEST SELLER -->
        <div class="card-header">Produk Terlaris (Best Seller)</div>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Produk</th>
                    <th>Total Terjual</th>
                    <th>Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bestSeller)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;color:var(--text-muted);padding:40px;">Belum ada data
                            penjualan.</td>
                    </tr>
                <?php else:
                    foreach ($bestSeller as $i => $b): ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($b['nama_produk']) ?></strong></td>
                            <td><span class="badge badge-accent"><?= $b['total_qty'] ?> pcs</span></td>
                            <td><strong>Rp <?= number_format($b['total_penjualan'], 0, ',', '.') ?></strong></td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div style="display:flex;gap:8px;justify-content:center;margin-top:20px;margin-bottom:20px;">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?tab=detail&page=<?= $p ?>" class="btn <?= $p === $page ? 'btn-primary' : 'btn-outline' ?> btn-sm"
                        style="padding: 8px 12px;"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'detail_nota'): ?>
        <!-- DETAIL NOTA -->
        <div class="card-header">
            <span>Detail Nota</span>
            <a href="?tab=harian&tanggal=<?= isset($headerNota['tgl_transaksi']) ? date('Y-m-d', strtotime($headerNota['tgl_transaksi'])) : date('Y-m-d') ?>" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <?php if (!$headerNota): ?>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                Data nota tidak ditemukan.
            </div>
        <?php else: ?>
            <div class="nota" style="max-width:480px;margin:20px auto;border:1px solid var(--border);padding:20px;border-radius:8px;">
                <h2 style="text-align:center;">TOKO MAJU JAYA</h2>
                <p style="text-align:center;font-size:0.75rem;color:var(--text-muted);">Jl. Maju Jaya No. 1 — Telp: (021) 123-4567</p>
                <div style="border-bottom:1px dashed var(--border);margin:10px 0;"></div>

                <table style="width:100%;border:none;">
                    <tr>
                        <td style="border:none;padding:4px 0;">No. Nota</td>
                        <td style="text-align:right;font-weight:bold;border:none;padding:4px 0;"><?= htmlspecialchars($headerNota['nomor_nota']) ?></td>
                    </tr>
                    <tr>
                        <td style="border:none;padding:4px 0;">Tanggal</td>
                        <td style="text-align:right;border:none;padding:4px 0;"><?= date('d/m/Y H:i', strtotime($headerNota['tgl_transaksi'])) ?></td>
                    </tr>
                    <tr>
                        <td style="border:none;padding:4px 0;">Kasir</td>
                        <td style="text-align:right;border:none;padding:4px 0;"><?= htmlspecialchars($headerNota['username']) ?></td>
                    </tr>
                </table>

                <div style="border-bottom:1px dashed var(--border);margin:10px 0;"></div>

                <table style="width:100%;border:none;">
                    <thead>
                        <tr>
                            <td style="border:none;padding:4px 0;"><strong>Barang</strong></td>
                            <td style="text-align:center;border:none;padding:4px 0;"><strong>Qty</strong></td>
                            <td style="text-align:right;border:none;padding:4px 0;"><strong>Subtotal</strong></td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailNota as $d): ?>
                            <tr>
                                <td style="border:none;padding:4px 0;"><?= htmlspecialchars($d['nama_produk']) ?></td>
                                <td style="text-align:center;border:none;padding:4px 0;"><?= $d['qty'] ?></td>
                                <td style="text-align:right;border:none;padding:4px 0;">Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="border-bottom:1px dashed var(--border);margin:10px 0;"></div>

                <table style="width:100%;font-size:0.85rem;border:none;">
                    <tr>
                        <td style="border:none;padding:4px 0;"><strong>Total</strong></td>
                        <td style="text-align:right;font-weight:bold;font-size:1rem;border:none;padding:4px 0;">Rp <?= number_format($headerNota['total_bayar'], 0, ',', '.') ?></td>
                    </tr>
                </table>
                
                <div style="display:flex;gap:12px;margin-top:20px;justify-content:center;">
                    <button onclick="window.print()" class="btn btn-outline btn-sm"><i class="fas fa-print"></i> Cetak</button>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
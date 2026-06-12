<?php
require_once __DIR__ . '/../../includes/auth_helper.php';
requireAdmin();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Dashboard Admin';

// --- STATS ---
$totalProduk = $pdo->query("SELECT COUNT(*) FROM m_produk")->fetchColumn();
$stokKritis = $pdo->query("SELECT COUNT(*) FROM m_produk WHERE stok < 5")->fetchColumn();
$totalTransaksi = $pdo->query("SELECT COUNT(*) FROM t_penjualan")->fetchColumn();
$totalPendapatan = $pdo->query("SELECT COALESCE(SUM(total_bayar), 0) FROM t_penjualan")->fetchColumn();

// --- CHART DATA (Last 7 Days Sales) ---
$stmt = $pdo->query("
    SELECT DATE(tgl_transaksi) as tgl, SUM(total_bayar) as total
    FROM t_penjualan
    WHERE tgl_transaksi >= DATE(NOW() - INTERVAL 6 DAY)
    GROUP BY DATE(tgl_transaksi)
    ORDER BY DATE(tgl_transaksi) ASC
");
$salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fill missing days with 0
$chartLabels = [];
$chartValues = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d M', strtotime($date));
    
    $found = false;
    foreach ($salesData as $row) {
        if ($row['tgl'] === $date) {
            $chartValues[] = $row['total'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $chartValues[] = 0;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<h2 style="margin-bottom:24px;font-weight:800;"><i class="fas fa-chart-pie"></i> Dashboard Utama</h2>

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
        <div class="value"><?= $totalProduk ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Stok Kritis</div>
        <div class="value danger"><?= $stokKritis ?></div>
    </div>
</div>

<div class="card" style="margin-top: 32px;">
    <div class="card-header">Grafik Penjualan 7 Hari Terakhir</div>
    <div style="position: relative; height:400px; width:100%;">
        <canvas id="salesChart"></canvas>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Pendapatan',
                data: <?= json_encode($chartValues) ?>,
                borderColor: '#2563EB',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 3,
                pointBackgroundColor: '#2563EB',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#2563EB',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                        }
                    }
                }
            }
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

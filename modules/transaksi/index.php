<?php
require_once __DIR__ . '/../../includes/auth_helper.php';
requireKasir();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Transaksi Penjualan';
$message = '';
$msgType = '';

// Init cart
if (!isset($_SESSION['cart']))
    $_SESSION['cart'] = [];

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $msgType = $_SESSION['msgType'];
    unset($_SESSION['message'], $_SESSION['msgType']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- TAMBAH KE KERANJANG ---
    if ($action === 'add_cart') {
        $idProduk = intval($_POST['id_produk'] ?? 0);
        $qty = intval($_POST['qty'] ?? 0);

        if ($idProduk <= 0 || $qty <= 0) {
            $message = "Pilih produk dan masukkan jumlah yang valid!";
            $msgType = 'danger';
        } else {
            $prod = $pdo->prepare("SELECT * FROM m_produk WHERE id_produk = ?");
            $prod->execute([$idProduk]);
            $produk = $prod->fetch();

            if (!$produk) {
                $message = "Produk tidak ditemukan!";
                $msgType = 'danger';
            } else {
                // Cek stok
                $qtyInCart = 0;
                foreach ($_SESSION['cart'] as $item) {
                    if ($item['id_produk'] == $idProduk)
                        $qtyInCart += $item['qty'];
                }
                if ($qty + $qtyInCart > $produk['stok']) {
                    $message = "Stok {$produk['nama_produk']} tidak mencukupi untuk transaksi ini.";
                    $msgType = 'danger';
                } else {
                    // Cek apakah sudah ada di cart, update qty
                    $found = false;
                    foreach ($_SESSION['cart'] as &$item) {
                        if ($item['id_produk'] == $idProduk) {
                            $item['qty'] += $qty;
                            $item['subtotal'] = $item['qty'] * $item['harga'];
                            $found = true;
                            break;
                        }
                    }
                    unset($item);

                    if (!$found) {
                        $_SESSION['cart'][] = [
                            'id_produk' => $produk['id_produk'],
                            'nama_produk' => $produk['nama_produk'],
                            'harga' => $produk['harga_jual'],
                            'qty' => $qty,
                            'subtotal' => $qty * $produk['harga_jual']
                        ];
                    }
                    $message = "{$produk['nama_produk']} x{$qty} ditambahkan ke keranjang!";
                    $msgType = 'success';
                }
            }
        }
    }

    // --- HAPUS DARI KERANJANG ---
    if ($action === 'remove_cart') {
        $idx = intval($_POST['index'] ?? -1);
        if (isset($_SESSION['cart'][$idx])) {
            unset($_SESSION['cart'][$idx]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            $message = "Item dihapus dari keranjang.";
            $msgType = 'success';
        }
    }

    // --- KOSONGKAN KERANJANG ---
    if ($action === 'clear_cart') {
        $_SESSION['cart'] = [];
        $message = "Keranjang dikosongkan.";
        $msgType = 'success';
    }
}

// Hitung total keranjang
$totalKeranjang = array_sum(array_column($_SESSION['cart'], 'subtotal'));

// Ambil daftar produk untuk dropdown
$produkAll = $pdo->query("SELECT id_produk, nama_produk, harga_jual, stok FROM m_produk WHERE stok > 0 ORDER BY nama_produk")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<h2 style="margin-bottom:24px;font-weight:800;"><i class="fas fa-cash-register"></i> Transaksi Penjualan</h2>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>"><i
            class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="transaction-grid">
    <!-- Pilih Barang -->
    <div class="card">
        <div class="card-header"><i class="fas fa-cart-plus"></i> Pilih Barang</div>
        <form method="POST">
            <input type="hidden" name="action" value="add_cart">
            <div class="form-group">
                <label for="id_produk">Produk</label>
                <select name="id_produk" id="id_produk" class="form-control" required>
                    <option value="">— Pilih Barang —</option>
                    <?php foreach ($produkAll as $p): ?>
                        <option value="<?= $p['id_produk'] ?>" data-harga="<?= $p['harga_jual'] ?>"
                            data-stok="<?= $p['stok'] ?>">
                            <?= htmlspecialchars($p['nama_produk']) ?> — Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?>
                            (Stok: <?= $p['stok'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="qty">Jumlah (Qty)</label>
                <input type="number" name="qty" id="qty" class="form-control" min="1" value="1" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i class="fas fa-plus"></i> Tambah ke Keranjang
            </button>
        </form>
    </div>

    <!-- Proses Bayar -->
    <div class="card">
        <div class="card-header"><i class="fas fa-money-bill-wave"></i> Pembayaran</div>
        <div class="stat-card" style="margin-bottom:16px;">
            <div class="label">Total Belanja</div>
            <div class="value accent">Rp <?= number_format($totalKeranjang, 0, ',', '.') ?></div>
        </div>
        <form method="POST" action="simpan.php">
            <input type="hidden" name="action" value="bayar">
            <div class="form-group">
                <label for="uang_bayar">Uang Tunai (Rp)</label>
                <input type="text" name="uang_bayar" id="uang_bayar" class="form-control currency-input"
                    placeholder="Masukkan nominal uang" required <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
            </div>
            <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;"
                <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                <i class="fas fa-check-circle"></i> Selesaikan Transaksi
            </button>
        </form>
        <form method="POST" style="margin-top:8px;">
            <input type="hidden" name="action" value="clear_cart">
            <button type="submit" class="btn btn-outline" style="width:100%;justify-content:center;">
                <i class="fas fa-trash-can"></i> Kosongkan Keranjang
            </button>
        </form>
    </div>
</div>

<!-- Keranjang -->
<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <span><i class="fas fa-shopping-cart"></i> Keranjang Belanja</span>
        <span class="badge badge-accent"><?= count($_SESSION['cart']) ?> item</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Produk</th>
                <th>Harga</th>
                <th>Qty</th>
                <th>Subtotal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($_SESSION['cart'])): ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">Keranjang kosong.
                        Silakan pilih barang.</td>
                </tr>
            <?php else:
                foreach ($_SESSION['cart'] as $i => $item): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($item['nama_produk']) ?></strong></td>
                        <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                        <td><?= $item['qty'] ?></td>
                        <td><strong>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></strong></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="remove_cart">
                                <input type="hidden" name="index" value="<?= $i ?>">
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-times"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
        </tbody>
        <?php if (!empty($_SESSION['cart'])): ?>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:left;font-weight:700;font-size:1.1rem;">TOTAL</td>
                    <td style="font-weight:800;font-size:1.1rem;color:var(--accent);">Rp
                        <?= number_format($totalKeranjang, 0, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
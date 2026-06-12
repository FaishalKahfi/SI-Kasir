<?php

require_once __DIR__ . '/../../includes/auth_helper.php';
requireAdmin();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Master Produk';
$message = '';
$msgType = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $msgType = $_SESSION['msgType'];
    unset($_SESSION['message'], $_SESSION['msgType']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- TAMBAH PRODUK ---
    if ($action === 'tambah') {
        $nama = trim($_POST['nama_produk'] ?? '');
        $harga = $_POST['harga_jual'] ?? '';
        $stok = intval($_POST['stok'] ?? 0);

        if (empty($nama)) {
            $message = "Data produk tidak lengkap, semua kolom wajib diisi!";
            $msgType = 'danger';
        } elseif (strlen($nama) > 100) {
            $message = "Nama produk tidak boleh lebih dari 100 karakter!";
            $msgType = 'danger';
        } elseif (!is_numeric($harga) || $harga < 0) {
            $message = "Harga harus berupa angka positif!";
            $msgType = 'danger';
        } elseif ($stok < 0) {
            $message = "Stok tidak boleh bernilai negatif!";
            $msgType = 'danger';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO m_produk (nama_produk, harga_jual, stok) VALUES (?, ?, ?)");
                $stmt->execute([$nama, $harga, $stok]);
                $newId = $pdo->lastInsertId();

                // Log stok awal
                $stmt2 = $pdo->prepare("INSERT INTO t_log_stok (id_produk, jumlah, tipe, keterangan) VALUES (?, ?, 'Masuk', 'Saldo awal produk')");
                $stmt2->execute([$newId, $stok]);

                $pdo->commit();
                $_SESSION['message'] = "Produk berhasil ditambahkan!";
                $_SESSION['msgType'] = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = "Gagal: " . $e->getMessage();
                $_SESSION['msgType'] = 'danger';
            }
            header('Location: index.php');
            exit;
        }
    }

    // --- EDIT PRODUK ---
    if ($action === 'edit') {
        $id = intval($_POST['id_produk']);
        $nama = trim($_POST['nama_produk'] ?? '');
        $harga = $_POST['harga_jual'] ?? '';
        $stokBaru = intval($_POST['stok'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? 'Stock opname manual');

        if (empty($nama) || !is_numeric($harga) || $harga < 0) {
            $_SESSION['message'] = "Data produk tidak lengkap, semua kolom wajib diisi!";
            $_SESSION['msgType'] = 'danger';
            header('Location: index.php?edit=' . $id);
            exit;
        } elseif (strlen($nama) > 100) {
            $_SESSION['message'] = "Nama produk tidak boleh lebih dari 100 karakter!";
            $_SESSION['msgType'] = 'danger';
            header('Location: index.php?edit=' . $id);
            exit;
        } else {
            $pdo->beginTransaction();
            try {
                // Ambil stok lama
                $old = $pdo->prepare("SELECT stok FROM m_produk WHERE id_produk = ?");
                $old->execute([$id]);
                $stokLama = $old->fetchColumn();

                $stmt = $pdo->prepare("UPDATE m_produk SET nama_produk=?, harga_jual=?, stok=? WHERE id_produk=?");
                $stmt->execute([$nama, $harga, $stokBaru, $id]);

                // Catat log stok jika stok berubah
                $diff = $stokBaru - $stokLama;
                if ($diff != 0) {
                    $tipe = $diff > 0 ? 'Masuk' : 'Keluar';
                    $stmt2 = $pdo->prepare("INSERT INTO t_log_stok (id_produk, jumlah, tipe, keterangan) VALUES (?, ?, ?, ?)");
                    $stmt2->execute([$id, abs($diff), $tipe, $keterangan]);
                }

                $pdo->commit();
                $_SESSION['message'] = "Produk berhasil diperbarui!";
                $_SESSION['msgType'] = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = "Gagal: " . $e->getMessage();
                $_SESSION['msgType'] = 'danger';
            }
            header('Location: index.php');
            exit;
        }
    }

    // --- HAPUS PRODUK (Restricted Delete) ---
    if ($action === 'hapus') {
        $id = intval($_POST['id_produk']);
        // Cek apakah produk pernah ada di t_penjualan_detail
        $cek = $pdo->prepare("SELECT COUNT(*) FROM t_penjualan_detail WHERE id_produk = ?");
        $cek->execute([$id]);
        if ($cek->fetchColumn() > 0) {
            $_SESSION['message'] = "Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi!";
            $_SESSION['msgType'] = 'danger';
        } else {
            // Hapus log stok dulu, lalu produk
            $pdo->beginTransaction();
            try {
                $pdo->prepare("DELETE FROM t_log_stok WHERE id_produk = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM m_produk WHERE id_produk = ?")->execute([$id]);
                $pdo->commit();
                $_SESSION['message'] = "Produk berhasil dihapus!";
                $_SESSION['msgType'] = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = "Gagal: " . $e->getMessage();
                $_SESSION['msgType'] = 'danger';
            }
        }
        header('Location: index.php');
        exit;
    }
}

// QUERY DATA
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? '';

$sql = "SELECT * FROM m_produk WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND nama_produk LIKE ?";
    $params[] = "%$search%";
}
if ($filter === 'kritis') {
    $sql .= " AND stok < 5";
}
$sql .= " ORDER BY id_produk DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produkList = $stmt->fetchAll();

// Stats
$totalProduk = $pdo->query("SELECT COUNT(*) FROM m_produk")->fetchColumn();
$stokKritis = $pdo->query("SELECT COUNT(*) FROM m_produk WHERE stok < 5")->fetchColumn();

// Untuk edit modal - ambil data produk jika ada parameter edit
$editProduk = null;
if (isset($_GET['edit'])) {
    $ed = $pdo->prepare("SELECT * FROM m_produk WHERE id_produk = ?");
    $ed->execute([$_GET['edit']]);
    $editProduk = $ed->fetch();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<h2 style="margin-bottom:24px;font-weight:800;"><i class="fas fa-boxes-stacked"></i> Master Produk</h2>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>"><i
            class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="label">Total Produk</div>
        <div class="value accent"><?= $totalProduk ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Stok Kritis (&lt;5)</div>
        <div class="value danger"><?= $stokKritis ?></div>
    </div>
</div>

<!-- Toolbar -->
<div class="card">
    <div class="toolbar">
        <form method="GET" style="display:flex;gap:12px;flex:1;flex-wrap:wrap;">
            <input type="text" name="search" class="form-control" placeholder="🔍 Cari produk..."
                value="<?= htmlspecialchars($search) ?>">
            <select name="filter" class="form-control" onchange="this.form.submit()" style="width:180px;">
                <option value="">Semua Produk</option>
                <option value="kritis" <?= $filter === 'kritis' ? 'selected' : '' ?>>⚠ Stok Kritis</option>
            </select>
            <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i> Cari</button>
        </form>
        <button onclick="document.getElementById('modalTambah').classList.add('show')" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Produk
        </button>
    </div>

    <!-- Table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Produk</th>
                <th>Harga Jual</th>
                <th>Stok</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($produkList)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">Tidak ada data produk.
                    </td>
                </tr>
            <?php else:
                foreach ($produkList as $p): ?>
                    <tr>
                        <td><?= $p['id_produk'] ?></td>
                        <td><strong><?= htmlspecialchars($p['nama_produk']) ?></strong></td>
                        <td>Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?></td>
                        <td><?= $p['stok'] ?></td>
                        <td>
                            <?php if ($p['stok'] < 5): ?>
                                <span class="badge badge-danger">Kritis</span>
                            <?php elseif ($p['stok'] < 20): ?>
                                <span class="badge badge-warning">Rendah</span>
                            <?php else: ?>
                                <span class="badge badge-success">Aman</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?edit=<?= $p['id_produk'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-pen"></i></a>
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="confirmDelete(<?= $p['id_produk'] ?>, '<?= htmlspecialchars(addslashes($p['nama_produk'])) ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal">
        <h3><i class="fas fa-plus-circle"></i> Tambah Produk Baru</h3>
        <form method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="form-group">
                <label for="nama_produk">Nama Produk</label>
                <input type="text" name="nama_produk" id="nama_produk" class="form-control" maxlength="100" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="harga_jual">Harga Jual (Rp)</label>
                    <input type="text" name="harga_jual" id="harga_jual" class="form-control currency-input" required>
                </div>
                <div class="form-group">
                    <label for="stok">Stok Awal</label>
                    <input type="number" name="stok" id="stok" class="form-control" min="0" required>
                </div>
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="document.getElementById('modalTambah').classList.remove('show')"
                    class="btn btn-outline">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php if ($editProduk): ?>
    <!-- Modal Edit -->
    <div class="modal-overlay show" id="modalEdit">
        <div class="modal">
            <h3><i class="fas fa-pen-to-square"></i> Edit Produk</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_produk" value="<?= $editProduk['id_produk'] ?>">
                <div class="form-group">
                    <label>Nama Produk</label>
                    <input type="text" name="nama_produk" class="form-control"
                        value="<?= htmlspecialchars($editProduk['nama_produk']) ?>" maxlength="100" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Harga Jual (Rp)</label>
                        <input type="text" name="harga_jual" class="form-control currency-input"
                            value="<?= number_format($editProduk['harga_jual'], 0, ',', '.') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Stok</label>
                        <input type="number" name="stok" class="form-control" value="<?= $editProduk['stok'] ?>" min="0"
                            required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Keterangan Perubahan Stok</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Stock opname manual"
                        value="Stock opname manual">
                </div>
                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:16px;">
                    <a href="?" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Hapus -->
<div class="modal-overlay" id="modalHapus">
    <div class="modal" style="width:400px;text-align:center;">
        <i class="fas fa-exclamation-triangle" style="font-size:3rem;color:var(--danger);margin-bottom:16px;"></i>
        <h3 style="border:none;padding:0;margin-bottom:8px;">Yakin hapus produk ini?</h3>
        <p style="color:var(--text-muted);margin-bottom:24px;">Anda akan menghapus <strong id="del_nama_produk"
                style="color:var(--text-primary);"></strong>. Tindakan ini tidak dapat dibatalkan.</p>
        <form method="POST">
            <input type="hidden" name="action" value="hapus">
            <input type="hidden" name="id_produk" id="del_id_produk" value="">
            <div style="display:flex;gap:12px;justify-content:center;">
                <button type="button" onclick="document.getElementById('modalHapus').classList.remove('show')"
                    class="btn btn-outline">Batal</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
    function confirmDelete(id, nama) {
        document.getElementById('del_id_produk').value = id;
        document.getElementById('del_nama_produk').innerText = nama;
        document.getElementById('modalHapus').classList.add('show');
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
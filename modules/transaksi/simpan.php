<?php

require_once __DIR__ . '/../../includes/auth_helper.php';
requireKasir();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bayar') {
    $uangBayar = floatval($_POST['uang_bayar'] ?? 0);
    $cart = $_SESSION['cart'] ?? [];

    if (empty($cart)) {
        $_SESSION['message'] = "Keranjang kosong!";
        $_SESSION['msgType'] = 'danger';
        header('Location: index.php');
        exit;
    }

    $totalBelanja = array_sum(array_column($cart, 'subtotal'));

    if ($uangBayar < $totalBelanja) {
        $_SESSION['message'] = "Uang bayar kurang dari total tagihan.";
        $_SESSION['msgType'] = 'danger';
        header('Location: index.php');
        exit;
    }

    // Generate nomor nota
    $nomorNota = 'PJN' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Cek duplicate nota
    $cekNota = $pdo->prepare("SELECT COUNT(*) FROM t_penjualan WHERE nomor_nota = ?");
    $cekNota->execute([$nomorNota]);
    if ($cekNota->fetchColumn() > 0) {
        $_SESSION['message'] = "Transaksi sudah diproses sebelumnya (Duplicate Nota).";
        $_SESSION['msgType'] = 'danger';
        header('Location: index.php');
        exit;
    }

    $pdo->beginTransaction();
    try {
        // Cek stok terakhir
        foreach ($cart as $item) {
            $stk = $pdo->prepare("SELECT stok FROM m_produk WHERE id_produk = ? FOR UPDATE");
            $stk->execute([$item['id_produk']]);
            $stokAktual = $stk->fetchColumn();
            if ($item['qty'] > $stokAktual) {
                throw new Exception("Stok {$item['nama_produk']} tidak mencukupi untuk transaksi ini.");
            }
        }

        // Insert header t_penjualan
        $stmt = $pdo->prepare("INSERT INTO t_penjualan (nomor_nota, tgl_transaksi, total_bayar, id_user) VALUES (?, NOW(), ?, ?)");
        $stmt->execute([$nomorNota, $totalBelanja, $_SESSION['id_user']]);
        $idPenjualan = $pdo->lastInsertId();

        // Loop detail
        foreach ($cart as $item) {
            // Insert detail
            $det = $pdo->prepare("INSERT INTO t_penjualan_detail (id_penjualan, id_produk, qty, subtotal) VALUES (?, ?, ?, ?)");
            $det->execute([$idPenjualan, $item['id_produk'], $item['qty'], $item['subtotal']]);

            // Update stok
            $upd = $pdo->prepare("UPDATE m_produk SET stok = stok - ? WHERE id_produk = ?");
            $upd->execute([$item['qty'], $item['id_produk']]);

            // Log stok keluar
            $log = $pdo->prepare("INSERT INTO t_log_stok (id_produk, jumlah, tipe, keterangan) VALUES (?, ?, 'Keluar', ?)");
            $log->execute([$item['id_produk'], $item['qty'], "Penjualan Nota #$nomorNota"]);
        }

        $pdo->commit();
        $_SESSION['cart'] = [];
        $_SESSION['last_nota'] = [
            'nomor_nota' => $nomorNota,
            'id_penjualan' => $idPenjualan,
            'total' => $totalBelanja,
            'bayar' => $uangBayar,
            'kembalian' => $uangBayar - $totalBelanja
        ];
        header('Location: nota.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['msgType'] = 'danger';
        header('Location: index.php');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}

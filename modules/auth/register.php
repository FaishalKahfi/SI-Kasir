<?php

require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$pageTitle = 'Manajemen User';
$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'Kasir';

        if (empty($username) || empty($password)) {
            $message = "Username dan Password wajib diisi!";
            $msgType = 'danger';
        } else {
            // Cek unique
            $cek = $pdo->prepare("SELECT COUNT(*) FROM m_user WHERE username = ?");
            $cek->execute([$username]);
            if ($cek->fetchColumn() > 0) {
                $message = "Username sudah digunakan, silakan pilih yang lain.";
                $msgType = 'danger';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO m_user (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$username, $hash, $role]);
                $message = "User $username berhasil didaftarkan!";
                $msgType = 'success';
            }
        }
    }

    if ($action === 'hapus') {
        $id = intval($_POST['id_user']);
        // Cek apakah user terikat transaksi
        $cek = $pdo->prepare("SELECT COUNT(*) FROM t_penjualan WHERE id_user = ?");
        $cek->execute([$id]);
        if ($cek->fetchColumn() > 0) {
            $message = "User tidak dapat dihapus karena masih terikat data transaksi!";
            $msgType = 'danger';
        } else {
            $pdo->prepare("DELETE FROM m_user WHERE id_user = ?")->execute([$id]);
            $message = "User berhasil dihapus!";
            $msgType = 'success';
        }
    }
}

$users = $pdo->query("SELECT id_user, username, role FROM m_user ORDER BY id_user")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<h2 style="margin-bottom:24px;font-weight:800;"><i class="fas fa-users-gear"></i> Manajemen User</h2>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>"><i
            class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <!-- Form Register -->
    <div class="card">
        <div class="card-header"><i class="fas fa-user-plus"></i> Tambah User Baru</div>
        <form method="POST">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control">
                    <option value="Kasir">Kasir</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i class="fas fa-save"></i> Daftarkan
            </button>
        </form>
    </div>

    <!-- Daftar User -->
    <div class="card">
        <div class="card-header"><i class="fas fa-list"></i> Daftar User</div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id_user'] ?></td>
                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                        <td><span
                                class="badge <?= $u['role'] === 'Admin' ? 'badge-accent' : 'badge-success' ?>"><?= $u['role'] ?></span>
                        </td>
                        <td>
                            <?php if ($u['id_user'] != $_SESSION['id_user']): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus user ini?')">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            <?php else: ?>
                                <span class="badge badge-warning">Anda</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
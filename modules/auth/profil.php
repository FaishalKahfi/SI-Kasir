<?php

require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Edit Profil';
$message = '';
$msgType = '';

$id_user = $_SESSION['id_user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameBaru = trim($_POST['username'] ?? '');
    $passwordLama = $_POST['password_lama'] ?? '';
    $passwordBaru = $_POST['password_baru'] ?? '';

    if (empty($usernameBaru)) {
        $message = "Username tidak boleh kosong!";
        $msgType = 'danger';
    } else {
        // Cek unique username (jika ganti username)
        if ($usernameBaru !== $_SESSION['username']) {
            $cek = $pdo->prepare("SELECT COUNT(*) FROM m_user WHERE username = ? AND id_user != ?");
            $cek->execute([$usernameBaru, $id_user]);
            if ($cek->fetchColumn() > 0) {
                $message = "Username sudah digunakan, silakan pilih yang lain.";
                $msgType = 'danger';
            }
        }

        if (empty($message)) {
            // Cek apakah mau ganti password
            if (!empty($passwordLama) || !empty($passwordBaru)) {
                // Ambil password lama dari DB
                $stmt = $pdo->prepare("SELECT password FROM m_user WHERE id_user = ?");
                $stmt->execute([$id_user]);
                $userDb = $stmt->fetch();

                if (!password_verify($passwordLama, $userDb['password'])) {
                    $message = "Password lama salah!";
                    $msgType = 'danger';
                } elseif (empty($passwordBaru)) {
                    $message = "Password baru tidak boleh kosong!";
                    $msgType = 'danger';
                } else {
                    $hashBaru = password_hash($passwordBaru, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE m_user SET username = ?, password = ? WHERE id_user = ?");
                    $upd->execute([$usernameBaru, $hashBaru, $id_user]);
                    $_SESSION['username'] = $usernameBaru;
                    $message = "Profil dan password berhasil diperbarui!";
                    $msgType = 'success';
                }
            } else {
                // Update username saja
                $upd = $pdo->prepare("UPDATE m_user SET username = ? WHERE id_user = ?");
                $upd->execute([$usernameBaru, $id_user]);
                $_SESSION['username'] = $usernameBaru;
                $message = "Profil berhasil diperbarui!";
                $msgType = 'success';
            }
        }
    }
}

// Ambil data user saat ini
$stmt = $pdo->prepare("SELECT username, role FROM m_user WHERE id_user = ?");
$stmt->execute([$id_user]);
$currentUser = $stmt->fetch();

require_once __DIR__ . '/../../includes/header.php';
?>

<h2 style="margin-bottom:24px;font-weight:800;"><i class="fas fa-user-edit"></i> Edit Profil</h2>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>"><i
            class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>">&nbsp;</i>
        <?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="card" style="max-width: 600px;">
    <div class="card-header"><i class="fas fa-id-card"></i> Informasi Akun</div>
    <form method="POST">
        <div class="form-group">
            <label>Role</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($currentUser['role']) ?>" disabled>
        </div>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control"
                value="<?= htmlspecialchars($currentUser['username']) ?>" required>
        </div>
        <hr style="margin:24px 0;border:0;border-top:1px solid var(--border-color);">
        <h4 style="margin-bottom:16px;">Ubah Password <span
                style="font-size:0.85rem;color:var(--text-muted);font-weight:normal;">(Opsional, biarkan kosong jika
                tidak ingin mengubah password)</span></h4>
        <div class="form-group">
            <label>Password Lama</label>
            <input type="password" name="password_lama" class="form-control" placeholder="Masukkan password saat ini">
        </div>
        <div class="form-group">
            <label>Password Baru</label>
            <input type="password" name="password_baru" class="form-control" placeholder="Masukkan password baru">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:12px;">
            <i class="fas fa-save"></i> Simpan Perubahan
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
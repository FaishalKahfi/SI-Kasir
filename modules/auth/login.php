<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$error = '';

// Bruteforce Prevention
if (!isset($_SESSION['login_attempts']))
    $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['lockout_time']))
    $_SESSION['lockout_time'] = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cek lockout
    if ($_SESSION['login_attempts'] >= 5 && time() - $_SESSION['lockout_time'] < 300) {
        $remaining = 300 - (time() - $_SESSION['lockout_time']);
        $error = "Terlalu banyak percobaan, tunggu " . ceil($remaining / 60) . " menit.";
    } else {
        // Reset jika lockout sudah lewat
        if ($_SESSION['login_attempts'] >= 5 && time() - $_SESSION['lockout_time'] >= 300) {
            $_SESSION['login_attempts'] = 0;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validasi empty field
        if (empty($username) || empty($password)) {
            $error = "Username dan Password wajib diisi!";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM m_user WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Reset attempts
                $_SESSION['login_attempts'] = 0;

                // Set session
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'Admin') {
                    header('Location: ../produk/index.php');
                } else {
                    header('Location: ../transaksi/index.php');
                }
                exit;
            } else {
                $_SESSION['login_attempts']++;
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['lockout_time'] = time();
                    $error = "Terlalu banyak percobaan, tunggu 5 menit.";
                } else {
                    $error = "Username atau Password salah!";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login SI-KASIR Toko Maju Jaya">
    <title>Login — SI-KASIR</title>
    <link rel="stylesheet" href="/uts/si-kasir/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="login-page">
        <div class="login-box">
            <div class="brand">SI-KASIR</div>
            <h1>Selamat Datang</h1>
            <p class="subtitle">Toko Swalayan Maju Jaya — Sistem Kasir Terintegrasi</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" id="username" class="form-control"
                        placeholder="Masukkan username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        autofocus>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" id="password" class="form-control"
                        placeholder="Masukkan password">
                </div>
                <button type="submit" class="btn btn-primary"
                    style="width:100%;justify-content:center;padding:14px;font-size:1rem;margin-top:8px;">
                    <i class="fas fa-right-to-bracket"></i> Login
                </button>
            </form>

            <p style="text-align:center;margin-top:24px;font-size:0.75rem;color:var(--text-muted);">
                &copy; 2026 SI-KASIR &bull; Faishal Kahfi
            </p>
        </div>
    </div>
</body>

</html>
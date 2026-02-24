<?php
require 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Jika sudah login, redirect sesuai role
if (is_logged_in()) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin')   { header('Location: admin.php');   exit; }
    if ($role === 'dokter')  { header('Location: dokter.php');  exit; }
    if ($role === 'perawat') { header('Location: perawat.php'); exit; }
    if ($role === 'apoteker'){ header('Location: apoteker.php'); exit; }
    header('Location: login.php');
    exit;
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $err = 'Username dan password harus diisi.';
    } else {
        $stmt = $db->prepare("SELECT id, username, password_hash, role, nama_lengkap FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
                // Redirect sesuai role
                if ($row['role'] === 'admin')   { header('Location: admin.php');   exit; }
                if ($row['role'] === 'dokter')  { header('Location: dokter.php');  exit; }
                if ($row['role'] === 'perawat') { header('Location: perawat.php'); exit; }
                if ($row['role'] === 'apoteker'){ header('Location: apoteker.php'); exit; }
                header('Location: login.php');
                exit;
            }
        }
        $err = 'Login gagal: username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Login - Klinik</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .portal { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 360px; width: 100%; }
        .portal h1 { margin: 0 0 0.5rem 0; font-size: 1.5rem; color: #333; }
        .portal p.sub { margin: 0 0 1.5rem 0; color: #666; font-size: 0.9rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.35rem; font-weight: 600; color: #444; }
        .form-group input { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; }
        .form-group input:focus { outline: none; border-color: #4a90d9; }
        .err { background: #fee; color: #c00; padding: 8px 10px; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem; }
        .btn { width: 100%; padding: 10px; background: #4a90d9; color: #fff; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #357abd; }
        .links { margin-top: 1.5rem; font-size: 0.85rem; color: #666; }
        .links a { color: #4a90d9; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="portal">
        <h1>Portal Login Klinik</h1>
        <p class="sub">Masuk dengan akun dokter, perawat, atau apoteker.</p>

        <?php if (!empty($err)): ?>
            <div class="err"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn">Login</button>
        </form>

        <div class="links">
            Belum punya akun? <a href="register.php">Daftar</a><br>
            Atau login lewat <a href="login.php">halaman login</a>.
        </div>
    </div>
</body>
</html>

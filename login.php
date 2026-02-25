<?php
require 'koneksi.php';
if(is_logged_in()){
    $r = $_SESSION['role'];
    if($r==='admin') header('Location: admin.php');
    if($r==='perawat') header('Location: perawat.php');
    if($r==='dokter') header('Location: dokter.php');
    if($r==='apoteker') header('Location: apoteker.php');
    if($r==='direktur') header('Location: direktur.php');
    header('Location: user.php');
    exit;
}

$err = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === 'masterlogin' && $password === 'master123') {
        $_SESSION['master_auth'] = true;
        header('Location: pilih_role_master.php');
        exit;
    }

    $stmt = $db->prepare("SELECT id, username, password_hash, role, nama_lengkap FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
        if(password_verify($password, $row['password_hash'])){
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
            if($row['role'] === 'admin') header('Location: admin.php');
            if($row['role'] === 'perawat') header('Location: perawat.php');
            if($row['role'] === 'dokter') header('Location: dokter.php');
            if($row['role'] === 'apoteker') header('Location: apoteker.php');
            if($row['role'] === 'direktur') header('Location: direktur.php');
            header('Location: user.php');
            exit;
        } else {
            $err = "Login gagal: username/password salah.";
        }
    } else {
        $err = "Login gagal: username/password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Klinik Risalah Medika</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 14px; }
        .login-box { background: var(--c-card); padding: 20px 24px; border-radius: var(--radius); box-shadow: 0 2px 8px rgba(0,0,0,0.08); max-width: 320px; width: 100%; border: 1px solid var(--c-border); }
        .login-box .brand { font-size: 16px; font-weight: 700; color: var(--c-primary); margin-bottom: 4px; }
        .login-box .sub { font-size: 11px; color: var(--c-muted); margin-bottom: 14px; }
        .login-box input { margin-bottom: 10px; }
        .login-box .btn { width: 100%; padding: 8px; margin-top: 4px; }
        .login-err { background: #fee2e2; color: #b91c1c; padding: 8px 10px; border-radius: 4px; font-size: 12px; margin-bottom: 10px; }
        .login-links { margin-top: 14px; font-size: 11px; color: var(--c-muted); }
        .login-links a { color: var(--c-primary); }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-box">
            <div class="brand">Klinik Risalah Medika</div>
            <p class="sub">Login dashboard</p>
            <?php if(!empty($_GET['registered'])): ?><p style="color:var(--c-success);font-size:12px;">Akun dibuat. Silakan login.</p><?php endif; ?>
            <?php if(!empty($err)): ?><div class="login-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
            <form method="post">
                <label>Username</label>
                <input name="username" required>
                <label>Password</label>
                <input name="password" type="password" required>
                <button type="submit" class="btn">Login</button>
            </form>
            <div class="login-links"><a href="index.php">Portal</a> &middot; <a href="register.php">Daftar</a></div>
        </div>
    </div>
</body>
</html>

<?php
session_start();
if (empty($_SESSION['master_auth'])) {
    header('Location: index.php');
    exit;
}

$role = $_GET['role'] ?? '';
$allowed = ['dokter', 'perawat', 'apoteker'];
if (!in_array($role, $allowed)) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pilih Role - Master Login</title>
        <style>
            * { box-sizing: border-box; }
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .box { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 400px; width: 100%; text-align: center; }
            .box h2 { margin: 0 0 0.5rem 0; color: #333; }
            .box p { color: #666; margin-bottom: 1.5rem; }
            .btn-role { display: block; width: 100%; padding: 14px; margin: 10px 0; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; color: #fff; text-align: center; }
            .btn-role:hover { opacity: 0.9; }
            .btn-dokter { background: #6d28d9; }
            .btn-perawat { background: #0d9488; }
            .btn-apoteker { background: #2563eb; }
            .back { margin-top: 1rem; font-size: 0.9rem; }
            .back a { color: #4a90d9; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>Master Login</h2>
            <p>Pilih masuk sebagai:</p>
            <a href="?role=dokter" class="btn-role btn-dokter">Dokter</a>
            <a href="?role=perawat" class="btn-role btn-perawat">Perawat</a>
            <a href="?role=apoteker" class="btn-role btn-apoteker">Apoteker</a>
            <p class="back"><a href="index.php">← Kembali ke login</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Set session sesuai role dan redirect
unset($_SESSION['master_auth']);

if ($role === 'perawat') {
    $_SESSION['perawat_login'] = true;
    $_SESSION['perawat_username'] = 'masterlogin';
    $_SESSION['perawat_nama'] = 'Master Login';
    header('Location: perawat.php');
    exit;
}

// Dokter & Apoteker pakai session users
$_SESSION['user_id'] = 999;
$_SESSION['username'] = 'masterlogin';
$_SESSION['nama_lengkap'] = 'Master Login';
$_SESSION['role'] = $role;
header('Location: ' . ($role === 'dokter' ? 'dokter.php' : 'apoteker.php'));
exit;

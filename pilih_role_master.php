<?php
session_start();

$is_switch = isset($_GET['switch']) && $_GET['switch'] === '1';
$is_master_session = ($is_switch && (
    (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === 999) ||
    (isset($_SESSION['perawat_username']) && $_SESSION['perawat_username'] === 'masterlogin')
));

if ($is_master_session) {
    unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'], $_SESSION['nama_lengkap']);
    unset($_SESSION['perawat_login'], $_SESSION['perawat_username'], $_SESSION['perawat_nama']);
    $_SESSION['master_auth'] = true;
    header('Location: pilih_role_master.php');
    exit;
}

if (empty($_SESSION['master_auth'])) {
    header('Location: index.php');
    exit;
}

$role = $_GET['role'] ?? '';
$allowed = ['dokter', 'perawat', 'apoteker', 'user'];
if (!in_array($role, $allowed)) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pilih Role - Klinik Risalah Medika</title>
        <link rel="stylesheet" href="style.css">
        <style>
            .role-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 14px; }
            .role-box { background: var(--c-card); padding: 20px 24px; border-radius: var(--radius); box-shadow: 0 2px 8px rgba(0,0,0,0.08); max-width: 320px; width: 100%; border: 1px solid var(--c-border); text-align: center; }
            .role-box .brand { font-size: 14px; font-weight: 700; color: var(--c-primary); margin-bottom: 12px; }
            .role-box .btn-role { display: block; width: 100%; padding: 10px 12px; margin: 6px 0; border-radius: 4px; font-size: 12px; font-weight: 600; text-decoration: none; color: #fff; text-align: center; }
            .role-box .btn-role:hover { opacity: 0.95; }
            .btn-dokter { background: #6d28d9; }
            .btn-perawat { background: #0d9488; }
            .btn-apoteker { background: #2563eb; }
            .btn-user { background: #64748b; }
            .role-box .back { margin-top: 14px; font-size: 11px; }
            .role-box .back a { color: var(--c-primary); }
        </style>
    </head>
    <body>
        <div class="role-page">
            <div class="role-box">
                <div class="brand">Klinik Risalah Medika</div>
                <p class="muted">Pilih masuk sebagai:</p>
                <a href="?role=dokter" class="btn-role btn-dokter">Dokter</a>
                <a href="?role=perawat" class="btn-role btn-perawat">Perawat</a>
                <a href="?role=apoteker" class="btn-role btn-apoteker">Apoteker</a>
                <a href="?role=user" class="btn-role btn-user">User (Pendaftaran)</a>
                <p class="back"><a href="index.php">← Kembali ke login</a></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

unset($_SESSION['master_auth']);

if ($role === 'user') {
    header('Location: user.php');
    exit;
}

if ($role === 'perawat') {
    $_SESSION['perawat_login'] = true;
    $_SESSION['perawat_username'] = 'masterlogin';
    $_SESSION['perawat_nama'] = 'Master Login';
    header('Location: perawat.php');
    exit;
}

$_SESSION['user_id'] = 999;
$_SESSION['username'] = 'masterlogin';
$_SESSION['nama_lengkap'] = 'Master Login';
$_SESSION['role'] = $role;
header('Location: ' . ($role === 'dokter' ? 'dokter.php' : 'apoteker.php'));
exit;

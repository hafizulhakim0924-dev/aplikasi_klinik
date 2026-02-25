<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Klinik Risalah Medika</title>
<link rel="stylesheet" href="style.css">
<style>
body{padding:0;}
textarea{min-height:60px;}
</style>
</head>
<body>
<?php
if (function_exists('is_logged_in') && is_logged_in() && function_exists('current_user')) {
    $u = current_user();
    $is_master = !empty($u['is_master']);
    ?>
    <div class="nav-bar">
        <span class="brand">Klinik Risalah Medika</span>
        <span style="opacity:0.8;">|</span>
        <span><?= htmlspecialchars($u['nama_lengkap']) ?> <span style="opacity:0.9;">(<?= htmlspecialchars($u['role']) ?>)</span></span>
        <?php if ($u['role']==='admin'): ?><a href="admin.php">Admin</a><?php endif; ?>
        <?php if ($u['role']==='perawat'): ?><a href="perawat.php">Perawat</a><?php endif; ?>
        <?php if ($u['role']==='dokter'): ?><a href="dokter.php">Dokter</a><?php endif; ?>
        <?php if ($u['role']==='apoteker'): ?><a href="apoteker.php">Apoteker</a><?php endif; ?>
        <a href="user.php">Pendaftaran</a>
        <?php if ($is_master): ?>
            <a href="pilih_role_master.php?switch=1" class="btn-switch">Switch</a>
        <?php else: ?>
            <a href="logout.php" class="btn-logout">Logout</a>
        <?php endif; ?>
    </div>
    <?php
}
?>
<div class="app-wrap">

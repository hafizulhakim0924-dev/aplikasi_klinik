<?php
require_once 'koneksi.php';
$user = current_user();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Klinik Risalah Medika</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="nav-bar">
    <span class="brand">Klinik Risalah Medika</span>
    <?php if($user): ?>
        <span style="opacity:0.9;"><?= htmlspecialchars($user['nama_lengkap'] ?? $user['username']) ?> (<?= $user['role'] ?>)</span>
        <?php if(!empty($user['is_master'])): ?>
            <a href="pilih_role_master.php?switch=1" class="btn-switch">Switch</a>
        <?php else: ?>
            <a href="logout.php" class="btn-logout">Logout</a>
        <?php endif; ?>
        <?php if($user['role']==='admin'): ?><a href="admin.php">Admin</a><?php endif; ?>
        <?php if($user['role']==='perawat'): ?><a href="perawat.php">Perawat</a><?php endif; ?>
        <?php if($user['role']==='dokter'): ?><a href="dokter.php">Dokter</a><?php endif; ?>
        <?php if($user['role']==='apoteker'): ?><a href="apoteker.php">Apoteker</a><?php endif; ?>
        <a href="user.php">Pendaftaran</a>
    <?php else: ?>
        <a href="login.php">Login</a>
    <?php endif; ?>
</div>
<div class="app-wrap">

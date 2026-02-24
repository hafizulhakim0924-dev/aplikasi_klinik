<?php
require_once 'koneksi.php';
$user = current_user();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Klinik</title>
<style>
body{font-family:Arial, Helvetica, sans-serif; padding:20px;}
nav{margin-bottom:15px;}
a{margin-right:10px;}
.panel{border:1px solid #ddd;padding:15px;border-radius:6px;}
.two-col{display:flex;gap:20px;}
.left{flex:1;}
.right{flex:1;max-width:480px;}
table{border-collapse:collapse;width:100%}
table td, table th{border:1px solid #eee;padding:6px}
</style>
</head>
<body>
<nav>
    <?php if($user): ?>
        Halo, <?= htmlspecialchars($user['nama_lengkap'] ?? $user['username']) ?> (<?= $user['role'] ?>) |
        <a href="logout.php">Logout</a>
        <?php if($user['role']==='admin'): ?><a href="admin.php">Admin</a><?php endif; ?>
        <?php if($user['role']==='perawat'): ?><a href="perawat.php">Perawat</a><?php endif; ?>
        <?php if($user['role']==='dokter'): ?><a href="dokter.php">Dokter</a><?php endif; ?>
        <?php if($user['role']==='apoteker'): ?><a href="apoteker.php">Apoteker</a><?php endif; ?>
        <a href="user.php">Pendaftaran</a>
    <?php else: ?>
        <a href="login.php">Login</a>
    <?php endif; ?>
</nav>

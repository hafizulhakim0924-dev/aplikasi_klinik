<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Pendaftaran Klinik</title>
<style>
body{font-family:Arial;padding:20px;}
.panel{border:1px solid #ddd;padding:15px;border-radius:6px;}
.two-col{display:flex;gap:20px;}
.left{flex:1;}
.right{flex:1;}
textarea{width:100%;height:90px;}
select,input,button{padding:6px;margin-top:4px;}
.nav-bar{background:#f0f4f8;padding:10px 14px;margin:-20px -20px 15px -20px;border-bottom:1px solid #ddd;display:flex;align-items:center;flex-wrap:wrap;gap:10px;}
.nav-bar a{margin-right:8px;color:#2563eb;text-decoration:none;}
.nav-bar a:hover{text-decoration:underline;}
.nav-bar .btn-logout,.nav-bar .btn-switch{padding:6px 12px;border-radius:4px;font-size:13px;text-decoration:none;display:inline-block;}
.nav-bar .btn-logout{background:#dc2626;color:#fff;}
.nav-bar .btn-switch{background:#7c3aed;color:#fff;}
</style>
</head>
<body>
<?php
if (function_exists('is_logged_in') && is_logged_in() && function_exists('current_user')) {
    $u = current_user();
    $is_master = !empty($u['is_master']);
    ?>
    <div class="nav-bar">
        <span><b>Halo, <?= htmlspecialchars($u['nama_lengkap']) ?></span> <span style="color:#64748b;">(<?= htmlspecialchars($u['role']) ?>)</span></span>
        <?php if ($u['role']==='admin'): ?><a href="admin.php">Admin</a><?php endif; ?>
        <?php if ($u['role']==='perawat'): ?><a href="perawat.php">Perawat</a><?php endif; ?>
        <?php if ($u['role']==='dokter'): ?><a href="dokter.php">Dokter</a><?php endif; ?>
        <?php if ($u['role']==='apoteker'): ?><a href="apoteker.php">Apoteker</a><?php endif; ?>
        <a href="user.php">Pendaftaran</a>
        <?php if ($is_master): ?>
            <a href="pilih_role_master.php?switch=1" class="btn-switch">Switch (Dokter/Perawat/Apoteker/User)</a>
        <?php else: ?>
            <a href="logout.php" class="btn-logout">Logout</a>
        <?php endif; ?>
    </div>
    <?php
}
?>

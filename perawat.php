<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// ========================== LOGIN SYSTEM ==========================
$login_file = "dataloginperawat.json";
if(!file_exists($login_file)){
    file_put_contents($login_file, json_encode([
        ["username" => "perawat1", "password" => "123456", "nama" => "Ustadzah Yovi, Amd., Kes"],
        ["username" => "perawat2", "password" => "123456", "nama" => "Ustadz A"],
        ["username" => "perawat3", "password" => "123456", "nama" => "ustadz B"]
    ], JSON_PRETTY_PRINT));
}

// Proses Login
if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    $users = json_decode(file_get_contents($login_file), true);
    foreach($users as $user){
        if($user['username'] === $username && $user['password'] === $password){
            $_SESSION['perawat_login'] = true;
            $_SESSION['perawat_username'] = $user['username'];
            $_SESSION['perawat_nama'] = $user['nama'];
            header("Location: perawat.php");
            exit;
        }
    }
    $login_error = "Username atau password salah!";
}

// Proses Logout
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: perawat.php");
    exit;
}

// Cek Login
if(!isset($_SESSION['perawat_login'])){
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login Perawat - Klinik Risalah Medika</title>
        <link rel="stylesheet" href="style.css">
        <style>
            .login-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 14px; }
            .login-box { background: var(--c-card); padding: 20px 24px; border-radius: var(--radius); box-shadow: 0 2px 8px rgba(0,0,0,0.08); max-width: 300px; width: 100%; border: 1px solid var(--c-border); }
            .login-box .brand { font-size: 14px; font-weight: 700; color: var(--c-primary); margin-bottom: 4px; }
            .login-box .sub { font-size: 11px; color: var(--c-muted); margin-bottom: 12px; }
            .login-box input { margin-bottom: 8px; }
            .login-box .btn { width: 100%; padding: 8px; margin-top: 4px; background: var(--c-primary); }
            .login-err { background: #fee2e2; color: #b91c1c; padding: 6px 10px; border-radius: 4px; font-size: 11px; margin-bottom: 8px; }
            .info-box { background: #f0fdfa; color: #0f766e; padding: 8px 10px; border-radius: 4px; margin-top: 12px; font-size: 11px; }
        </style>
    </head>
    <body>
        <div class="login-page">
            <div class="login-box">
                <div class="brand">Klinik Risalah Medika</div>
                <p class="sub">Login Perawat</p>
                <?php if(isset($login_error)): ?><div class="login-err"><?= $login_error ?></div><?php endif; ?>
                <form method="post">
                    <input type="text" name="username" placeholder="Username" required autofocus>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" name="login" class="btn">Login</button>
                </form>
                <div class="info-box">perawat1 / 123456 &middot; masterlogin / master123</div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ========================== SETELAH LOGIN =========================
require 'koneksi.php';
include 'header_user.php';

// ========================== HELPERS ===============================
function h($s){ return htmlspecialchars($s); }
function safe_display($v){ return ($v===''||$v===null) ? '-' : nl2br(h($v)); }

// ========================== AJAX HANDLERS =========================
if(isset($_GET['ajax'])){

    // ------- AJAX: LIST ANTRIAN ------
    if($_GET['ajax'] == "antrian"){
        $list = $db->query("
            SELECT p.*, a.nama AS nama_anak
            FROM pasien p
            LEFT JOIN anak a ON p.anak_id = a.id_anak
            WHERE p.status='menunggu'
            ORDER BY p.nomor_antrian ASC
        ");

        if($list->num_rows == 0){
            echo '<tr><td colspan="3" align="center">Tidak ada antrian</td></tr>';
        } else {
            while($p = $list->fetch_assoc()){
                echo '<tr>
                    <td align="center"><b>'.$p['nomor_antrian'].'</b></td>
                    <td>'.h($p['nama_anak']).'</td>
                    <td><a href="perawat.php?proses='.$p['id'].'">Proses</a></td>
                </tr>';
            }
        }
        exit;
    }

    // ------- AJAX: STATUS PASIEN ------
    if($_GET['ajax'] == "status"){
        $id = (int)$_GET['pasien_id'];
        $st = $db->query("SELECT status FROM pasien WHERE id=$id")->fetch_assoc();
        echo json_encode(["status" => $st['status'] ?? "none"]);
        exit;
    }

    // ------- AJAX: RIWAYAT ------
    if($_GET['ajax'] == "riwayat"){
        $anak_id = (int)$_GET['anak_id'];

        $riwayat = $db->query("
            SELECT * FROM riwayat_kesehatan
            WHERE anak_id=$anak_id
            ORDER BY created_at DESC
        ");

        if($riwayat->num_rows == 0){
            echo '<p>Belum ada riwayat</p>';
            exit;
        }

        while($r = $riwayat->fetch_assoc()){
            echo '<div style="border:1px solid #ccc; padding:10px; margin-bottom:10px; background:#f9f9f9;">
                <small><b>'.date("d M Y H:i", strtotime($r['created_at'])).'</b></small><br>
                <b>Kategori:</b> '.safe_display($r['kategori']).'<br>
                <b>Keluhan:</b> '.safe_display($r['keluhan']).'<br>';
            if($r['catatan_perawat']) echo '<b>Catatan Perawat:</b> '.safe_display($r['catatan_perawat']).'<br>';
            if($r['diagnosa']) echo '<b>Diagnosa:</b> '.safe_display($r['diagnosa']).'<br>';
            echo '</div>';
        }
        exit;
    }

    // ------- AJAX: SUGGEST ANAK (dropdown rekam medik & grafik) ------
    if($_GET['ajax'] == "suggest_anak"){
        $q = trim($_GET['q'] ?? '');
        header('Content-Type: text/html; charset=utf-8');
        if(strlen($q) < 2){
            echo '';
            exit;
        }
        $stmt = $db->prepare("SELECT id_anak, nama FROM anak WHERE nama LIKE ? ORDER BY nama ASC LIMIT 15");
        $like = '%'.$q.'%';
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){
            echo '<div class="perawat-suggest-item" data-id="'.(int)$row['id_anak'].'" data-nama="'.h($row['nama']).'">'.h($row['nama']).'</div>';
        }
        $stmt->close();
        exit;
    }
}

// ========================== KATEGORI JSON =========================
$json_file = "kategoripenyakit.json";
if(!file_exists($json_file)){
    file_put_contents($json_file, json_encode([
        "Demam","Batuk","Pilek","Flu","Asma","Diare","Alergi",
        "Sakit Kepala","Pusing","Mual"
    ], JSON_PRETTY_PRINT));
}
$kategori = json_decode(file_get_contents($json_file), true);

// tambah kategori
if(isset($_POST['tambah_kategori'])){
    $baru = trim($_POST['kategori_baru']);
    if($baru !== ""){
        $kategori[] = $baru;
        $kategori = array_values(array_unique($kategori));
        file_put_contents($json_file, json_encode($kategori, JSON_PRETTY_PRINT));
    }
    header("Location: perawat.php");
    exit;
}

//hapus kategori
if(isset($_GET['hapus_kat'])){
    $hapus = $_GET['hapus_kat'];
    $kategori = array_filter($kategori, fn($x) => $x !== $hapus);
    file_put_contents($json_file, json_encode(array_values($kategori), JSON_PRETTY_PRINT));
    header("Location: perawat.php");
    exit;
}

// ========================== DAFTAR ANTRIAN DARI KLINIK (pasien tanpa HP) =========================
if(isset($_POST['daftar_antrian'])){
    $anak_id = (int)$_POST['anak_id'];
    $keluhan = trim($_POST['keluhan'] ?? '');

    $q = $db->prepare("SELECT nama FROM anak WHERE id_anak = ?");
    $q->bind_param('i', $anak_id);
    $q->execute();
    $result = $q->get_result();
    $row = $result->fetch_assoc();
    $nama = $row['nama'] ?? '---';
    $q->close();

    $res = $db->query("SELECT COALESCE(MAX(nomor_antrian), 0) AS last FROM pasien");
    $last = (int)$res->fetch_assoc()['last'] + 1;
    $waktu_daftar = date('Y-m-d H:i:s');

    $ins = $db->prepare("
        INSERT INTO pasien 
        (anak_id, nama, nomor_antrian, status, tgl_daftar)
        VALUES (?, ?, ?, 'menunggu', ?)
    ");
    $ins->bind_param('isis', $anak_id, $nama, $last, $waktu_daftar);
    $ins->execute();
    $pasien_id = $db->insert_id;
    $ins->close();

    $stmt_r = $db->prepare("
        INSERT INTO riwayat_kesehatan 
        (pasien_id, anak_id, keluhan, nomor_antrian, status_akhir, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'user_saja', NOW(), NOW())
    ");
    $stmt_r->bind_param('iisi', $pasien_id, $anak_id, $keluhan, $last);
    $stmt_r->execute();
    $stmt_r->close();

    header("Location: perawat.php?ok_daftar=1&antrian=".$last);
    exit;
}

// ========================== LIST ANAK (untuk form daftar antrian) =========================
$anak_list = $db->query("SELECT id_anak, nama FROM anak ORDER BY nama ASC");

// ========================== LIST PASIEN ===========================
$list = $db->query("
    SELECT p.*, a.nama AS nama_anak
    FROM pasien p
    LEFT JOIN anak a ON p.anak_id = a.id_anak
    WHERE p.status='menunggu'
    ORDER BY p.nomor_antrian ASC
");

$selected = null;
$riwayat = null;
$modal_grafik_kat = [];
$keluhan_text = '';

if(isset($_GET['proses'])){
    $idp = (int)$_GET['proses'];

    $stmt = $db->prepare("
        SELECT p.*, a.nama AS nama_anak 
        FROM pasien p
        LEFT JOIN anak a ON p.anak_id = a.id_anak
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $idp);
    $stmt->execute();
    $selected = $stmt->get_result()->fetch_assoc();

    if($selected){
        $keluhan = $db->query("
            SELECT keluhan FROM riwayat_kesehatan 
            WHERE pasien_id=".$selected['id']." LIMIT 1
        ")->fetch_assoc();
        $keluhan_text = $keluhan['keluhan'] ?? '';

        // Jika belum ada baris riwayat (data lama sebelum form user disimpan ke riwayat), buat dulu agar data perawat bisa masuk ke dokter
        if ($keluhan === null) {
            $ins = $db->prepare("
                INSERT INTO riwayat_kesehatan 
                (pasien_id, anak_id, keluhan, nomor_antrian, status_akhir, created_at, updated_at)
                VALUES (?, ?, '', ?, 'user_saja', NOW(), NOW())
            ");
            $ins->bind_param("iii", $selected['id'], $selected['anak_id'], $selected['nomor_antrian']);
            $ins->execute();
            $ins->close();
        }

        $riwayat = $db->query("
            SELECT * FROM riwayat_kesehatan
            WHERE anak_id=".$selected['anak_id']."
            ORDER BY created_at DESC
        ");

        $gq = $db->query("
            SELECT kategori, COUNT(*) AS jumlah 
            FROM riwayat_kesehatan
            WHERE anak_id=".$selected['anak_id']." 
              AND kategori IS NOT NULL AND kategori != ''
            GROUP BY kategori
            ORDER BY jumlah DESC
        ");
        while($row = $gq->fetch_assoc()){
            $modal_grafik_kat[] = $row;
        }
    }
}

// ========================= DATA TAB REKAM MEDIK & GRAFIK (gabungan) =========================
$anak_list_tab = $db->query("SELECT id_anak, nama FROM anak ORDER BY nama ASC");

$rekam_anak_id = (int)($_GET['anak_id'] ?? 0);
$rekam_q = trim($_GET['rekam_q'] ?? '');
if ($rekam_anak_id > 0) {
    $rekam_anak = $db->query("SELECT * FROM anak WHERE id_anak=$rekam_anak_id LIMIT 1")->fetch_assoc();
} elseif ($rekam_q !== '') {
    $stmt_r = $db->prepare("SELECT * FROM anak WHERE nama LIKE ? OR id_anak = ? LIMIT 1");
    $kr = "%$rekam_q%";
    $stmt_r->bind_param("si", $kr, $rekam_q);
    $stmt_r->execute();
    $rekam_anak = $stmt_r->get_result()->fetch_assoc();
    $stmt_r->close();
    if ($rekam_anak) $rekam_anak_id = (int)$rekam_anak['id_anak'];
} else {
    $rekam_anak = null;
}

$rekam_riwayat = null;
$grafik_kategori = [];
if ($rekam_anak) {
    $aid = (int)$rekam_anak['id_anak'];
    $rekam_riwayat = $db->query("SELECT * FROM riwayat_kesehatan WHERE anak_id=$aid ORDER BY created_at DESC");
    $res_kat = $db->query("
        SELECT kategori, COUNT(*) AS jumlah FROM riwayat_kesehatan
        WHERE anak_id=$aid AND kategori IS NOT NULL AND kategori != ''
        GROUP BY kategori ORDER BY jumlah DESC
    ");
    while ($row = $res_kat->fetch_assoc()) {
        $grafik_kategori[] = $row;
    }
}

// Gabungkan keterangan per kategori jadi satu teks (untuk simpan & dokter_tidak_ada)
function build_catatan_kategori_from_post() {
    $catatan_kat = $_POST['catatan_kat'] ?? [];
    if (!is_array($catatan_kat)) return '';
    $parts = [];
    foreach ($catatan_kat as $k => $v) {
        $v = trim($v ?? '');
        if ($v !== '') $parts[] = $k . ': ' . $v;
    }
    return implode("\n", $parts);
}

// ========================= SIMPAN ================================
if(isset($_POST['simpan'])){
    $pasien_id = (int)$_POST['pasien_id'];

    // PERUBAHAN: Gabungkan multiple kategori dengan koma
    $kategori_array = isset($_POST['kategori']) ? $_POST['kategori'] : [];
    $kategori_penyakit = !empty($kategori_array) ? implode(', ', $kategori_array) : null;
    
    $td = trim($_POST['td']);
    $tinggi = ($_POST['tinggi']==''? null : (int)$_POST['tinggi']);
    $berat = ($_POST['berat']==''? null : (float)$_POST['berat']);
    $cat = trim($_POST['catatan_perawat']);
    $catatan_kategori = build_catatan_kategori_from_post();
    $resep_perawat = trim($_POST['resep_perawat']);
    $nama_perawat = $_SESSION['perawat_nama'];
    $suhu_demam = ($_POST['suhu_demam']==''? null : (float)$_POST['suhu_demam']);

    $upd = $db->prepare("
        UPDATE riwayat_kesehatan 
        SET kategori=?, td=?, tinggi_cm=?, berat_kg=?, 
            catatan_perawat=?, catatan_kategori=?, resep_perawat=?, suhu_demam=?, 
            status_akhir='perawat_selesai', alur_langsung_apoteker=0,
            updated_at=NOW(), nama_perawat=?
        WHERE pasien_id=? LIMIT 1
    ");

    $upd->bind_param(
        "sssssssssi",
        $kategori_penyakit,
        $td,
        $tinggi,
        $berat,
        $cat,
        $catatan_kategori,
        $resep_perawat,
        $suhu_demam,
        $nama_perawat,
        $pasien_id
    );

    $upd->execute();

    $db->query("UPDATE pasien SET status='proses_dokter' WHERE id=$pasien_id");

    if (!empty($_POST['buat_surat'])) {
        header("Location: generate_surat_perawat.php?pasien_id=".$pasien_id."&print=1");
        exit;
    }
    header("Location: perawat.php?ok=1");
    exit;
}

// ========================= DOKTER TIDAK ADA ======================
if(isset($_POST['dokter_tidak_ada'])){
    $pasien_id = (int)$_POST['pasien_id'];

    // PERUBAHAN: Gabungkan multiple kategori dengan koma
    $kategori_array = isset($_POST['kategori']) ? $_POST['kategori'] : [];
    $kategori_penyakit = !empty($kategori_array) ? implode(', ', $kategori_array) : null;
    
    $td = trim($_POST['td']);
    $tinggi = ($_POST['tinggi']==''? null : (int)$_POST['tinggi']);
    $berat = ($_POST['berat']==''? null : (float)$_POST['berat']);
    $cat = trim($_POST['catatan_perawat']);
    $catatan_kategori = build_catatan_kategori_from_post();
    $resep_perawat = trim($_POST['resep_perawat']);
    $nama_perawat = $_SESSION['perawat_nama'];
    $suhu_demam = ($_POST['suhu_demam']==''? null : (float)$_POST['suhu_demam']);

    $upd = $db->prepare("
        UPDATE riwayat_kesehatan 
        SET kategori=?, td=?, tinggi_cm=?, berat_kg=?, catatan_perawat=?, catatan_kategori=?,
            resep_perawat=?, suhu_demam=?, status_akhir='resep_dari_perawat', alur_langsung_apoteker=1,
            updated_at=NOW(), nama_perawat=?
        WHERE pasien_id=? LIMIT 1
    ");
    $upd->bind_param("ssssssssi",
        $kategori_penyakit,$td,$tinggi,$berat,$cat,$catatan_kategori,$resep_perawat,
        $suhu_demam,$nama_perawat,$pasien_id
    );
    $upd->execute();

    $db->query("UPDATE pasien SET status='resep_dari_perawat' WHERE id=$pasien_id");

    header("Location: perawat.php?ok_perawat=1");
    exit;
}
?>

<link rel="stylesheet" href="style.css">
<style>
.perawat-nav { background: var(--c-primary); color: #fff; padding: 6px 14px; margin: 0 -14px 10px -14px; display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
.perawat-nav .brand { font-weight: 700; font-size: 13px; }
.perawat-nav .user { font-size: 12px; opacity: 0.95; }
.perawat-nav a { color: #fff; text-decoration: none; font-size: 11px; padding: 4px 10px; border-radius: 4px; }
.perawat-nav .btn-logout { background: var(--c-danger); }
.perawat-nav .btn-switch-perawat { background: #7c3aed; }
.box { background: var(--c-card); border: 1px solid var(--c-border); border-radius: var(--radius); padding: 10px 12px; margin-bottom: 10px; box-shadow: var(--shadow); }
.box h3 { margin: 0 0 8px 0; font-size: 12px; font-weight: 600; color: var(--c-primary); border-bottom: 1px solid var(--c-border); padding-bottom: 4px; }
.two-col { display: flex; gap: 12px; }
.left-col { width: 28%; min-width: 200px; }
.right-col { flex: 1; }
button, .btn { padding: 6px 12px; font-size: 12px; }
.btn-danger { background: #dc2626; color: #fff; }
.btn-ke-dokter { background: var(--c-primary); color: #fff; }
.btn-ke-apoteker { background: #2563eb; color: #fff; }
.pilihan-alur { background: #f8fafc; border: 1px solid var(--c-border); border-radius: var(--radius); padding: 10px; margin-top: 8px; }
.label-alur { margin: 0 0 2px 0; font-size: 12px; }
.desc-alur { margin: 0 0 8px 0; font-size: 11px; color: var(--c-muted); }
.wrap-btn-alur { display: flex; flex-wrap: wrap; gap: 8px; }
#fieldSuhu { display: none; background: #fffbeb; padding: 8px; margin: 8px 0; border-left: 3px solid #f59e0b; font-size: 12px; }
#fieldKeteranganKategori { display: none; background: #f0fdfa; padding: 8px; margin: 8px 0; border-left: 3px solid var(--c-primary); }
.perawat-tab { padding: 5px 12px; margin-right: 4px; cursor: pointer; font-size: 12px; border: 1px solid var(--c-border); background: #fff; border-radius: 4px; }
.perawat-tab.active { background: var(--c-primary); color: #fff; border-color: var(--c-primary); }
.perawat-tabContent { display: none; }
.perawat-tabContent.active { display: block; }
.grafik-bar { height: 20px; background: var(--c-primary); border-radius: 4px; }
.grafik-bar-wrap { background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-bottom: 4px; }
.record-box { border: 1px solid var(--c-border); padding: 8px 10px; margin-bottom: 6px; border-radius: 4px; background: #f8fafc; cursor: pointer; }
.record-detail { display: none; background: #fff; border: 1px solid var(--c-border); padding: 10px; border-radius: 4px; margin-top: 6px; font-size: 12px; }
.perawat-modal-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,0.55); display: flex; align-items: center; justify-content: center; padding: 16px; z-index: 200; }
.perawat-modal { background: #ffffff; width: 100%; max-width: 720px; max-height: 90vh; overflow-y: auto; border-radius: 10px; box-shadow: 0 24px 48px rgba(15,23,42,0.35); padding: 20px 24px 24px; }
.perawat-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.perawat-modal-title { font-size: 18px; font-weight: 600; }
.perawat-modal-close { border: none; background: transparent; font-size: 20px; cursor: pointer; line-height: 1; padding: 4px 8px; border-radius: 999px; }
.perawat-modal-close:hover { background: #e2e8f0; }
.perawat-modal-tabs { display:flex; gap:8px; }
.perawat-modal-tab { padding:6px 12px; font-size:12px; border-radius:999px; border:1px solid var(--c-border); background:#f8fafc; cursor:pointer; }
.perawat-modal-tab.active { background:var(--c-primary); color:#fff; border-color:var(--c-primary); }
.perawat-suggest-dropdown { position: absolute; left: 0; top: 100%; width: 100%; max-width: 280px; max-height: 220px; overflow-y: auto; background: #fff; border: 1px solid var(--c-border); border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 100; margin-top: 2px; }
.perawat-suggest-item { padding: 8px 10px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
.perawat-suggest-item:hover { background: #f0fdfa; }
.perawat-suggest-item:last-child { border-bottom: none; }
.kategori-checkbox-container { border: 1px solid var(--c-border); padding: 8px; background: #fafafa; border-radius: 4px; display: flex; flex-direction: column; flex-wrap: wrap; max-height: 90px; gap: 2px 14px; align-content: flex-start; }
.kategori-checkbox-item { padding: 2px 0; white-space: nowrap; }
.kategori-checkbox-item input { width: auto; margin-right: 6px; }
.selected-categories { padding: 6px; margin: 4px 0; border-radius: 4px; min-height: 18px; font-size: 11px; background: #f0fdfa; }
.selected-categories .badge { display: inline-block; background: var(--c-primary); color: #fff; padding: 2px 6px; margin: 1px; border-radius: 10px; font-size: 11px; }
textarea { min-height: 50px; }
</style>

<div class="perawat-nav">
    <span class="brand">Klinik Risalah Medika</span>
    <span style="opacity:0.7;">|</span>
    <span class="user"><?= h($_SESSION['perawat_nama']) ?> (<?= h($_SESSION['perawat_username']) ?>)</span>
    <?php if (!empty($_SESSION['perawat_username']) && $_SESSION['perawat_username'] === 'masterlogin'): ?>
        <a href="pilih_role_master.php?switch=1" class="btn-switch-perawat">Switch</a>
    <?php else: ?>
        <a href="?logout" class="btn-logout" onclick="return confirm('Yakin logout?')">Logout</a>
    <?php endif; ?>
</div>

<div class="app-wrap">
<h1 class="page-title">Panel Perawat</h1>

<?php if(isset($_GET['ok'])): ?>
<div class="alert alert-success">✓ Data berhasil dikirim ke dokter</div>
<?php endif; ?>

<?php if(isset($_GET['ok_perawat'])): ?>
<div class="alert alert-info">✓ Resep berhasil dikirim ke apotek</div>
<?php endif; ?>

<?php if(isset($_GET['ok_daftar'])): ?>
<div class="alert alert-success">✓ Pasien berhasil didaftarkan. No. Antrian: <strong><?= (int)($_GET['antrian'] ?? '') ?></strong></div>
<?php endif; ?>

<?php
$tab_rekam_active = (isset($_GET['tab']) && $_GET['tab'] === 'rekam');
?>
<button type="button" class="perawat-tab <?= $tab_rekam_active ? '' : 'active' ?>" data-tab="tabPemeriksaan">Antrian & Pemeriksaan</button>
<button type="button" class="perawat-tab <?= $tab_rekam_active ? 'active' : '' ?>" data-tab="tabRekamMedik">Rekam Medik & Grafik</button>

<div id="tabPemeriksaan" class="perawat-tabContent <?= $tab_rekam_active ? '' : 'active' ?>">
<div class="two-col">
    <!-- KOLOM KIRI -->
    <div class="left-col">
        <!-- Input Antrian dari Klinik (pasien tanpa HP) -->
        <div class="box box-daftar">
            <h3>📝 Input Antrian dari Klinik</h3>
            <p class="small-muted">Untuk pasien yang tidak daftar lewat HP (perawat bantu daftarkan)</p>
            <form method="post">
                <label><b>Pilih Nama Anak:</b></label>
                <select name="anak_id" required style="width:100%; padding:8px; margin:5px 0; box-sizing:border-box;">
                    <option value="">-- pilih anak --</option>
                    <?php while($a = $anak_list->fetch_assoc()): ?>
                        <option value="<?= (int)$a['id_anak'] ?>"><?= h($a['nama']) ?></option>
                    <?php endwhile; ?>
                </select>
                <label><b>Keluhan:</b></label>
                <textarea name="keluhan" placeholder="Keluhan pasien (boleh kosong)" rows="2" style="width:100%; padding:8px; margin:5px 0; box-sizing:border-box;"></textarea>
                <button type="submit" name="daftar_antrian" class="btn" style="margin-top:8px;">Daftarkan & Ambil Antrian</button>
            </form>
        </div>

        <!-- Kategori Penyakit -->
        <div class="box">
            <h3>🏥 Kategori Penyakit</h3>
            <form method="post" style="margin-bottom:10px;">
                <input type="text" name="kategori_baru" placeholder="Kategori baru" required>
                <button name="tambah_kategori">Tambah</button>
            </form>

            <table>
                <tr><th>Kategori</th><th>Aksi</th></tr>
                <?php foreach($kategori as $k): ?>
                    <tr>
                        <td><?=h($k)?></td>
                        <td><a href="?hapus_kat=<?=urlencode($k)?>" style="color:red;">Hapus</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- KOLOM KANAN -->
    <div class="right-col">
        <div class="box">
            <h3>📋 Antrian Pasien</h3>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="antrianBox">
                    <?php
                    if($list->num_rows==0){
                        echo '<tr><td colspan="3" align="center">Tidak ada antrian</td></tr>';
                    }else{
                        while($p=$list->fetch_assoc()){
                            echo '<tr>
                                <td align="center"><b>'.$p['nomor_antrian'].'</b></td>
                                <td>'.h($p['nama_anak']).'</td>
                                <td><a href="perawat.php?proses='.$p['id'].'" class="btn">Proses</a></td>
                            </tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php if($selected): ?>
<!-- ================= MODAL PEMERIKSAAN DETAIL ================= -->
<div class="perawat-modal-backdrop" id="modalPemeriksaan">
    <div class="perawat-modal">
        <div class="perawat-modal-header">
            <div class="perawat-modal-title">
                Pemeriksaan Pasien — <?=h($selected['nama_anak'])?> (No. Antrian: <?=$selected['nomor_antrian']?>)
            </div>
            <a href="perawat.php" class="perawat-modal-close" aria-label="Tutup">&times;</a>
        </div>

        <!-- Keluhan utama -->
        <div style="background:#fff3cd; padding:10px; margin:10px 0 16px; border-left:4px solid #ffc107;">
            <b>Keluhan Saat Daftar:</b><br>
            <?=safe_display($keluhan_text)?>
        </div>

        <div class="perawat-modal-tabs" style="margin-bottom:10px; border-bottom:1px solid #e2e8f0;">
            <button type="button" class="perawat-modal-tab active" data-tab="modalForm">Form Pemeriksaan</button>
            <button type="button" class="perawat-modal-tab" data-tab="modalRiwayat">Riwayat</button>
            <button type="button" class="perawat-modal-tab" data-tab="modalGrafik">Grafik</button>
        </div>

        <div id="modalForm" class="perawat-modal-tabContent active">
            <!-- Form Pemeriksaan Lengkap -->
            <form method="post">
                <input type="hidden" name="pasien_id" value="<?=$selected['id']?>">

                <h4 style="margin-top:4px;">🩺 Data Pemeriksaan</h4>

                <label><b>Kategori Penyakit (bisa lebih dari satu):</b></label>
                <div class="selected-categories" id="selectedCategoriesDisplay">
                    <small style="color:#666;">Belum ada kategori dipilih</small>
                </div>
                <div class="kategori-checkbox-container">
                    <?php foreach($kategori as $k): ?>
                        <div class="kategori-checkbox-item">
                            <input type="checkbox" 
                                   name="kategori[]" 
                                   value="<?=h($k)?>" 
                                   id="kat_<?=h($k)?>"
                                   class="kategori-checkbox">
                            <label for="kat_<?=h($k)?>"><?=h($k)?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="fieldKeteranganKategori">
                    <p style="margin:0 0 6px 0;"><b>📝 Keterangan per kategori (isi jika perlu):</b></p>
                    <?php foreach($kategori as $k): 
                        $kid = str_replace(' ', '_', $k);
                    ?>
                    <div class="wrap-ket-kat" id="wrap_ket_<?= h($kid) ?>" style="display:none; margin-bottom:6px;">
                        <label style="font-size:12px;">Keterangan untuk <strong><?= h($k) ?></strong>:</label>
                        <textarea name="catatan_kat[<?= h($k) ?>]" rows="2" placeholder="Contoh: suhu 38°C, menggigil" style="width:100%; margin-top:2px;"></textarea>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div id="fieldSuhu">
                    <label><b>🌡️ Suhu Tubuh (°C):</b></label>
                    <input type="number" step="0.1" name="suhu_demam" placeholder="Contoh: 38.5">
                    <small>Isi suhu tubuh pasien</small>
                </div>

                <div class="two-col" style="margin-top:8px; gap:12px;">
                    <div class="left-col" style="width:50%;">
                        <label><b>Tekanan Darah:</b></label>
                        <input type="text" name="td" placeholder="Contoh: 120/80">

                        <label><b>Tinggi Badan (cm):</b></label>
                        <input type="number" name="tinggi" placeholder="Contoh: 165">

                        <label><b>Berat Badan (kg):</b></label>
                        <input type="number" step="0.1" name="berat" placeholder="Contoh: 55.5">
                    </div>
                    <div class="right-col">
                        <label><b>Catatan Perawat:</b></label>
                        <textarea name="catatan_perawat" placeholder="Catatan pemeriksaan..."></textarea>

                        <label><b>Resep Perawat:</b></label>
                        <textarea name="resep_perawat" placeholder="Obat dan dosis..."></textarea>
                    </div>
                </div>

                <label style="margin-top:8px; display:block;">
                    <input type="checkbox" name="buat_surat" id="buat_surat" value="1"> Buat Surat Izin Sakit
                </label>

                <label><b>Nama Perawat:</b></label>
                <input type="text" value="<?= h($_SESSION['perawat_nama']) ?>" readonly style="background: #e9ecef; cursor: not-allowed;">

                <div class="pilihan-alur" style="margin-top:10px;">
                    <p class="label-alur"><b>Pilih alur pasien:</b></p>
                    <p class="desc-alur">Jika dokter ada, pilih <strong>Lanjutkan ke Dokter</strong>. Jika dokter tidak ada, pilih <strong>Langsung ke Apoteker</strong>.</p>
                    <div class="wrap-btn-alur">
                        <button type="submit" name="simpan" class="btn btn-ke-dokter">✓ Lanjutkan ke Dokter</button>
                        <button type="submit" name="dokter_tidak_ada" class="btn btn-ke-apoteker">Langsung ke Apoteker (dokter tidak ada)</button>
                    </div>
                </div>
            </form>

            <p style="margin-top:12px;">
                <a href="generate_surat_perawat.php?pasien_id=<?= (int)$selected['id'] ?>" target="_blank" class="btn" style="background:#0d9488;">
                    📄 Generate Surat Izin (PDF) – Perawat
                </a>
                <small style="color:#666;"> Buka surat lalu cetak / simpan sebagai PDF.</small>
            </p>
        </div>

        <div id="modalRiwayat" class="perawat-modal-tabContent" style="display:none;">
            <h4>📖 Riwayat Kesehatan Anak</h4>
            <div id="riwayatContainer" style="margin-top:6px;">
                <?php
                if($riwayat && $riwayat->num_rows > 0){
                    while($r=$riwayat->fetch_assoc()){
                        echo '<div style="border:1px solid #ccc; padding:10px; margin-bottom:10px; background:#f9f9f9;">
                            <small><b>'.date("d M Y H:i", strtotime($r['created_at'])).'</b></small><br>
                            <b>Kategori:</b> '.safe_display($r['kategori']).'<br>
                            <b>Keluhan:</b> '.safe_display($r['keluhan']).'<br>';
                        if($r['catatan_perawat']) echo '<b>Catatan Perawat:</b> '.safe_display($r['catatan_perawat']).'<br>';
                        if($r['diagnosa']) echo '<b>Diagnosa:</b> '.safe_display($r['diagnosa']).'<br>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Belum ada riwayat</p>';
                }
                ?>
            </div>
        </div>

        <div id="modalGrafik" class="perawat-modal-tabContent" style="display:none;">
            <h4>📊 Grafik Batang Riwayat Kategori</h4>
            <?php
            $modal_max_j = 0;
            foreach ($modal_grafik_kat as $gk) { if ($gk['jumlah'] > $modal_max_j) $modal_max_j = (int)$gk['jumlah']; }
            ?>
            <?php if (empty($modal_grafik_kat)): ?>
                <p class="muted">Belum ada data kategori untuk grafik.</p>
            <?php else: ?>
                <table style="width:100%; max-width:500px; margin-top:8px;">
                    <tr><th>Kategori</th><th>Jumlah</th><th>Grafik</th></tr>
                    <?php foreach ($modal_grafik_kat as $gk):
                        $pct = $modal_max_j > 0 ? round(($gk['jumlah'] / $modal_max_j) * 100) : 0;
                    ?>
                    <tr>
                        <td><?= h($gk['kategori']) ?></td>
                        <td><?= $gk['jumlah'] ?>x</td>
                        <td style="width:180px;">
                            <div class="grafik-bar-wrap" style="height:18px;">
                                <div class="grafik-bar" style="width:<?= $pct ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

</div><!-- #tabPemeriksaan -->
    
<!-- ================= TAB REKAM MEDIK & GRAFIK (gabungan) ================= -->
<div id="tabRekamMedik" class="perawat-tabContent <?= $tab_rekam_active ? 'active' : '' ?>">
    <div class="box">
        <h3>🔎 Rekam Medik & Grafik</h3>
        <form method="get" id="formRekamGrafik" style="margin-bottom:12px;">
            <input type="hidden" name="tab" value="rekam">
            <input type="hidden" name="anak_id" id="rekam_anak_id" value="<?= $rekam_anak_id ?>">
            <?php if (!empty($selected['id'])): ?><input type="hidden" name="proses" value="<?= (int)$selected['id'] ?>"><?php endif; ?>
            <div class="perawat-search-wrap" style="position:relative; display:inline-block;">
                <input type="text" id="rekam_search_input" placeholder="Cari nama anak..." value="<?= $rekam_anak ? h($rekam_anak['nama']) : '' ?>" autocomplete="off" style="padding:6px 10px; width:280px;">
                <div id="rekam_suggest_dropdown" class="perawat-suggest-dropdown" style="display:none;"></div>
            </div>
        </form>
        <?php if (!$rekam_anak): ?>
            <p class="muted">Ketik nama anak di atas dan pilih dari dropdown untuk melihat rekam medik dan grafik.</p>
        <?php else: ?>
            <p><b>Nama:</b> <?= h($rekam_anak['nama']) ?> | <b>ID:</b> <?= h($rekam_anak['id_anak']) ?></p>

            <h4 style="margin-top:16px;">📌 Rekam Medik</h4>
            <?php if (!$rekam_riwayat || $rekam_riwayat->num_rows === 0): ?>
                <p class="muted">Belum ada riwayat medis.</p>
            <?php else: ?>
                <?php $cnt = 0; while ($r = $rekam_riwayat->fetch_assoc()): $cnt++; ?>
                <div class="record-box" onclick="var d=document.getElementById('rekamDetail<?= $cnt ?>'); d.style.display=d.style.display==='block'?'none':'block';">
                    <b>📌 <?= date('d M Y H:i', strtotime($r['created_at'])) ?></b> — Kategori: <?= h($r['kategori'] ?? '-') ?>
                </div>
                <div id="rekamDetail<?= $cnt ?>" class="record-detail">
                    <b>Keluhan:</b> <?= nl2br(h($r['keluhan'] ?? '-')) ?><br>
                    <b>Catatan Perawat:</b> <?= nl2br(h($r['catatan_perawat'] ?? '-')) ?><br>
                    <?php if (!empty($r['catatan_kategori'])): ?><b>Keterangan Kategori:</b> <?= nl2br(h($r['catatan_kategori'])) ?><br><?php endif; ?>
                    <b>Diagnosa:</b> <?= nl2br(h($r['diagnosa'] ?? '-')) ?><br>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <h4 style="margin-top:20px;">📊 Grafik Kategori Kunjungan</h4>
            <?php
            $max_j = 0;
            foreach ($grafik_kategori as $gk) { if ($gk['jumlah'] > $max_j) $max_j = (int)$gk['jumlah']; }
            ?>
            <?php if (empty($grafik_kategori)): ?>
                <p class="muted">Belum ada data kategori.</p>
            <?php else: ?>
                <table style="width:100%; max-width:500px;">
                    <tr><th>Kategori</th><th>Jumlah</th><th>Grafik</th></tr>
                    <?php foreach ($grafik_kategori as $gk):
                        $pct = $max_j > 0 ? round(($gk['jumlah'] / $max_j) * 100) : 0;
                    ?>
                    <tr>
                        <td><?= h($gk['kategori']) ?></td>
                        <td><?= $gk['jumlah'] ?>x</td>
                        <td style="width:180px;">
                            <div class="grafik-bar-wrap" style="height:18px;">
                                <div class="grafik-bar" style="width:<?= $pct ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.perawat-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
        var tabId = this.getAttribute('data-tab');
        document.querySelectorAll('.perawat-tab').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.perawat-tabContent').forEach(function(c){ c.classList.remove('active'); });
        this.classList.add('active');
        var el = document.getElementById(tabId);
        if(el) el.classList.add('active');
    });
});

// Tab di dalam modal pemeriksaan (Form vs Riwayat & Grafik)
(function(){
    var tabs = document.querySelectorAll('.perawat-modal-tab');
    if(!tabs.length) return;
    tabs.forEach(function(btn){
        btn.addEventListener('click', function(){
            var tabId = this.getAttribute('data-tab');
            tabs.forEach(function(b){ b.classList.remove('active'); });
            document.querySelectorAll('.perawat-modal-tabContent').forEach(function(c){
                c.style.display = 'none';
                c.classList.remove('active');
            });
            this.classList.add('active');
            var target = document.getElementById(tabId);
            if(target){
                target.style.display = 'block';
                target.classList.add('active');
            }
        });
    });
})();

// Rekam Medik & Grafik: dropdown saran nama (submit form dengan tab=rekam, tidak pindah tab)
(function(){
    var input = document.getElementById('rekam_search_input');
    var dropdown = document.getElementById('rekam_suggest_dropdown');
    var form = document.getElementById('formRekamGrafik');
    var hiddenId = document.getElementById('rekam_anak_id');
    if(!input || !dropdown || !form) return;
    var timer;
    input.addEventListener('input', function(){
        var q = (this.value || '').trim();
        clearTimeout(timer);
        dropdown.style.display = 'none';
        if(q.length < 2){ hiddenId.value = ''; return; }
        timer = setTimeout(function(){
            fetch('perawat.php?ajax=suggest_anak&q=' + encodeURIComponent(q))
                .then(function(r){ return r.text(); })
                .then(function(html){
                    dropdown.innerHTML = html;
                    dropdown.style.display = html ? 'block' : 'none';
                    var items = dropdown.querySelectorAll('.perawat-suggest-item');
                    items.forEach(function(item){
                        item.addEventListener('click', function(e){
                            e.preventDefault();
                            var id = this.getAttribute('data-id');
                            var nama = this.getAttribute('data-nama');
                            if(id){ hiddenId.value = id; input.value = nama; }
                            dropdown.style.display = 'none';
                            form.submit();
                        });
                    });
                });
        }, 200);
    });
    input.addEventListener('focus', function(){
        if(dropdown.innerHTML && (input.value || '').trim().length >= 2) dropdown.style.display = 'block';
    });
    document.addEventListener('click', function(e){
        if(!input.contains(e.target) && !dropdown.contains(e.target)) dropdown.style.display = 'none';
    });
})();
</script>

<script>
// ===================================================
// MULTIPLE KATEGORI CHECKBOX HANDLER
// ===================================================
const checkboxes = document.querySelectorAll('.kategori-checkbox');
const displayArea = document.getElementById('selectedCategoriesDisplay');
const fieldSuhu = document.getElementById('fieldSuhu');
const fieldKeterangan = document.getElementById('fieldKeteranganKategori');

function updateSelectedDisplay() {
    const selected = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);
    
    if(selected.length === 0) {
        displayArea.innerHTML = '<small style="color:#666;">Belum ada kategori dipilih</small>';
        if(fieldSuhu) fieldSuhu.style.display = 'none';
        if(fieldKeterangan) fieldKeterangan.style.display = 'none';
    } else {
        displayArea.innerHTML = selected
            .map(cat => `<span class="badge">${cat}</span>`)
            .join('');
        if(fieldKeterangan) fieldKeterangan.style.display = 'block';
        selected.forEach(function(cat){
            var wrap = document.getElementById('wrap_ket_' + cat.replace(/\s/g, '_'));
            if(wrap) wrap.style.display = 'block';
        });
        checkboxes.forEach(function(cb){
            if(!cb.checked){
                var wrap = document.getElementById('wrap_ket_' + cb.value.replace(/\s/g, '_'));
                if(wrap) wrap.style.display = 'none';
            }
        });
        if(selected.includes('Demam')) {
            if(fieldSuhu) fieldSuhu.style.display = 'block';
        } else {
            if(fieldSuhu) fieldSuhu.style.display = 'none';
        }
    }
}

// Attach event listener ke semua checkbox
checkboxes.forEach(cb => {
    cb.addEventListener('change', updateSelectedDisplay);
});

// ===================================================
// AJAX POLLING - OPTIMIZED (3 detik interval)
// ===================================================
let pollingInterval;
let isPolling = false;

function startPolling() {
    if(isPolling) return;
    isPolling = true;

    pollingInterval = setInterval(() => {
        // 1. Polling antrian
        fetch("perawat.php?ajax=antrian")
            .then(r => r.text())
            .then(html => {
                const antrianBox = document.getElementById("antrianBox");
                if(antrianBox) antrianBox.innerHTML = html;
            })
            .catch(err => console.log('Error polling antrian:', err));

        <?php if($selected): ?>
        // 2. Polling riwayat
        fetch("perawat.php?ajax=riwayat&anak_id=<?=$selected['anak_id']?>")
            .then(r => r.text())
            .then(html => {
                const riwayatContainer = document.getElementById("riwayatContainer");
                if(riwayatContainer) riwayatContainer.innerHTML = html;
            })
            .catch(err => console.log('Error polling riwayat:', err));

        // 3. Polling status pasien
        fetch("perawat.php?ajax=status&pasien_id=<?=$selected['id']?>")
            .then(r => r.json())
            .then(data => {
                if(data.status !== "menunggu" && data.status !== "proses_perawat"){
                    stopPolling();
                    alert("⚕ Status pasien berubah. Halaman akan dimuat ulang.");
                    location.href = "perawat.php";
                }
            })
            .catch(err => console.log('Error polling status:', err));
        <?php endif; ?>

    }, 3000); // 3 detik
}

function stopPolling() {
    if(pollingInterval) {
        clearInterval(pollingInterval);
        isPolling = false;
    }
}

// Start polling saat halaman load
startPolling();

// Stop polling saat user pindah tab
document.
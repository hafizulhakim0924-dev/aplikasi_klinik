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
    }
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
    $resep_perawat = trim($_POST['resep_perawat']);
    $nama_perawat = $_SESSION['perawat_nama'];
    $suhu_demam = ($_POST['suhu_demam']==''? null : (float)$_POST['suhu_demam']);

    $upd = $db->prepare("
        UPDATE riwayat_kesehatan 
        SET kategori=?, td=?, tinggi_cm=?, berat_kg=?, 
            catatan_perawat=?, resep_perawat=?, suhu_demam=?, 
            status_akhir='perawat_selesai', 
            updated_at=NOW(), nama_perawat=?
        WHERE pasien_id=? LIMIT 1
    ");

    $upd->bind_param(
        "ssssssssi",
        $kategori_penyakit,
        $td,
        $tinggi,
        $berat,
        $cat,
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
    $resep_perawat = trim($_POST['resep_perawat']);
    $nama_perawat = $_SESSION['perawat_nama'];
    $suhu_demam = ($_POST['suhu_demam']==''? null : (float)$_POST['suhu_demam']);

    $upd = $db->prepare("
        UPDATE riwayat_kesehatan 
        SET kategori=?, td=?, tinggi_cm=?, berat_kg=?, catatan_perawat=?, 
            resep_perawat=?, suhu_demam=?, status_akhir='resep_dari_perawat', 
            updated_at=NOW(), nama_perawat=?
        WHERE pasien_id=? LIMIT 1
    ");
    $upd->bind_param("sssssssi",
        $kategori_penyakit,$td,$tinggi,$berat,$cat,$resep_perawat,
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
.kategori-checkbox-container { border: 1px solid var(--c-border); padding: 8px; max-height: 140px; overflow-y: auto; background: #fafafa; border-radius: 4px; }
.kategori-checkbox-item { padding: 3px 0; }
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

        <!-- Antrian Pasien -->
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
            <?php if(!$selected): ?>
                <p align="center" style="padding:40px 0; color:#666;">
                    👈 Pilih pasien dari antrian untuk memulai pemeriksaan
                </p>
            <?php else: ?>
                <h3>Pemeriksaan: <?=h($selected['nama_anak'])?></h3>
                <p><b>Nomor Antrian:</b> <?=$selected['nomor_antrian']?></p>
                <hr>

                <!-- Keluhan -->
                <div style="background:#fff3cd; padding:10px; margin:10px 0; border-left:4px solid #ffc107;">
                    <b>Keluhan:</b><br>
                    <?=safe_display($keluhan_text)?>
                </div>

                <!-- Form Pemeriksaan -->
                <form method="post">
                    <input type="hidden" name="pasien_id" value="<?=$selected['id']?>">

                    <label><b>Kategori Penyakit (Pilih satu atau lebih):</b></label>
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

                    <!-- Field Suhu (muncul jika pilih Demam) -->
                    <div id="fieldSuhu">
                        <label><b>🌡️ Suhu Tubuh (°C):</b></label>
                        <input type="number" step="0.1" name="suhu_demam" placeholder="Contoh: 38.5">
                        <small>Isi suhu tubuh pasien</small>
                    </div>

                    <label><b>Tekanan Darah:</b></label>
                    <input type="text" name="td" placeholder="Contoh: 120/80">

                    <label><b>Tinggi Badan (cm):</b></label>
                    <input type="number" name="tinggi" placeholder="Contoh: 165">

                    <label><b>Berat Badan (kg):</b></label>
                    <input type="number" step="0.1" name="berat" placeholder="Contoh: 55.5">

                    <label><b>Catatan Perawat:</b></label>
                    <textarea name="catatan_perawat" placeholder="Catatan pemeriksaan..."></textarea>

                    <label><b>Resep Perawat:</b></label>
                    <textarea name="resep_perawat" placeholder="Obat dan dosis..."></textarea>

                    <label>
                        <input type="checkbox" name="buat_surat" id="buat_surat" value="1"> Buat Surat Izin Sakit
                    </label>

                    <!-- Nama Perawat (Otomatis Terisi & Readonly) -->
                    <label><b>Nama Perawat:</b></label>
                    <input type="text" value="<?= h($_SESSION['perawat_nama']) ?>" readonly style="background: #e9ecef; cursor: not-allowed;">

                    <br><br>
                    <div class="pilihan-alur">
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

                <hr style="margin:20px 0;">

                <!-- Riwayat -->
                <h3>📖 Riwayat Kesehatan</h3>
                <div id="riwayatContainer" style="max-height:400px; overflow-y:auto;">
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
            <?php endif; ?>
        </div>
    </div>
</div>
</div><!-- .app-wrap -->

<script>
// ===================================================
// MULTIPLE KATEGORI CHECKBOX HANDLER
// ===================================================
const checkboxes = document.querySelectorAll('.kategori-checkbox');
const displayArea = document.getElementById('selectedCategoriesDisplay');
const fieldSuhu = document.getElementById('fieldSuhu');

function updateSelectedDisplay() {
    const selected = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);
    
    if(selected.length === 0) {
        displayArea.innerHTML = '<small style="color:#666;">Belum ada kategori dipilih</small>';
        fieldSuhu.style.display = 'none';
    } else {
        displayArea.innerHTML = selected
            .map(cat => `<span class="badge">${cat}</span>`)
            .join('');
        
        // Show field suhu jika ada yang pilih "Demam"
        if(selected.includes('Demam')) {
            fieldSuhu.style.display = 'block';
        } else {
            fieldSuhu.style.display = 'none';
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
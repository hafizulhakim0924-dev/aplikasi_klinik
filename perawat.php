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
    <html>
    <head>
        <title>Login Perawat</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                width: 350px;
            }
            .login-box h2 {
                margin: 0 0 30px 0;
                text-align: center;
                color: #333;
            }
            .login-box input {
                width: 100%;
                padding: 12px;
                margin: 10px 0;
                border: 1px solid #ddd;
                border-radius: 5px;
                box-sizing: border-box;
                font-size: 14px;
            }
            .login-box button {
                width: 100%;
                padding: 12px;
                background: #4CAF50;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                font-weight: bold;
                margin-top: 10px;
            }
            .login-box button:hover {
                background: #45a049;
            }
            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 15px;
                text-align: center;
            }
            .info-box {
                background: #d1ecf1;
                color: #0c5460;
                padding: 10px;
                border-radius: 5px;
                margin-top: 15px;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🏥 Login Perawat</h2>
            
            <?php if(isset($login_error)): ?>
                <div class="error"><?= $login_error ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="text" name="username" placeholder="Username" required autofocus>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
            </form>
            
            <div class="info-box">
                <b>Demo Account:</b><br>
                Username: perawat1 | Password: 123456<br>
                Username: perawat2 | Password: 123456
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

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
table { border-collapse: collapse; width: 100%; background: white; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #4CAF50; color: white; }
input[type="text"], input[type="number"], select, textarea {
    width: 100%; padding: 8px; margin: 5px 0; box-sizing: border-box; border: 1px solid #ddd;
}
textarea { min-height: 60px; }
button, .btn { 
    padding: 10px 15px; margin: 5px 2px; cursor: pointer; border: none;
    background: #4CAF50; color: white; text-decoration: none; display: inline-block;
}
button:hover, .btn:hover { background: #45a049; }
.btn-danger { background: #f44336; }
.btn-danger:hover { background: #da190b; }
.btn-logout { background: #ff9800; padding: 8px 15px; font-size: 14px; }
.btn-logout:hover { background: #e68900; }
.alert { padding: 10px; margin: 10px 0; border-radius: 5px; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.two-col { display: flex; gap: 20px; }
.left-col { width: 30%; }
.right-col { width: 70%; }
.box { background: white; padding: 15px; margin-bottom: 15px; border: 1px solid #ddd; }
.box h3 { margin-top: 0; color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 5px; }
.user-info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
#fieldSuhu { display: none; background: #fff3cd; padding: 10px; margin: 10px 0; border-left: 4px solid #ffc107; }

/* STYLE UNTUK CHECKBOX KATEGORI */
.kategori-checkbox-container {
    border: 1px solid #ddd;
    padding: 10px;
    margin: 5px 0;
    max-height: 200px;
    overflow-y: auto;
    background: #fafafa;
    border-radius: 4px;
}
.kategori-checkbox-item {
    display: block;
    padding: 5px;
    margin: 3px 0;
}
.kategori-checkbox-item:hover {
    background: #e8f5e9;
}
.kategori-checkbox-item input[type="checkbox"] {
    width: auto;
    margin-right: 8px;
}
.kategori-checkbox-item label {
    cursor: pointer;
    display: inline;
}
.selected-categories {
    background: #e3f2fd;
    padding: 8px;
    margin: 5px 0;
    border-radius: 4px;
    min-height: 20px;
    font-size: 13px;
}
.selected-categories .badge {
    display: inline-block;
    background: #2196F3;
    color: white;
    padding: 3px 8px;
    margin: 2px;
    border-radius: 12px;
    font-size: 12px;
}
</style>

<div class="user-info">
    <div>
        <b>👤 Login sebagai:</b> <?= h($_SESSION['perawat_nama']) ?> 
        <small>(<?= h($_SESSION['perawat_username']) ?>)</small>
    </div>
    <a href="?logout" class="btn btn-logout" onclick="return confirm('Yakin ingin logout?')">Logout</a>
</div>

<h2>👨‍⚕️ Panel Perawat</h2>

<?php if(isset($_GET['ok'])): ?>
<div class="alert alert-success">✓ Data berhasil dikirim ke dokter</div>
<?php endif; ?>

<?php if(isset($_GET['ok_perawat'])): ?>
<div class="alert alert-info">✓ Resep berhasil dikirim ke apotek</div>
<?php endif; ?>

<div class="two-col">
    <!-- KOLOM KIRI -->
    <div class="left-col">
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
                        <input type="checkbox" id="buat_surat"> Buat Surat Izin Sakit
                    </label>

                    <!-- Nama Perawat (Otomatis Terisi & Readonly) -->
                    <label><b>Nama Perawat:</b></label>
                    <input type="text" value="<?= h($_SESSION['perawat_nama']) ?>" readonly style="background: #e9ecef; cursor: not-allowed;">

                    <br><br>
                    <button type="submit" name="simpan">✓ Simpan & Kirim ke Dokter</button>
                    <button type="submit" name="dokter_tidak_ada" class="btn-danger">Dokter Tidak Ada - Kirim ke Apotek</button>
                </form>

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
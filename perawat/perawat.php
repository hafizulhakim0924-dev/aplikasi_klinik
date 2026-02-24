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

// Cek Login - Jika belum login, include halaman login
if(!isset($_SESSION['perawat_login'])){
    include 'perawat_login.php';
    exit;
}

// ========================== SETELAH LOGIN =========================
require 'koneksi.php';

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

    $kategori_penyakit = $_POST['kategori'] !== '' ? $_POST['kategori'] : null;
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

    $kategori_penyakit = $_POST['kategori'] !== '' ? $_POST['kategori'] : null;
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

// Include tampilan HTML
include 'perawat_view.php';
?>
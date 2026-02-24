<?php
require 'koneksi.php';
date_default_timezone_set("Asia/Jakarta");

$pasien_id = (int)$_GET['pasien_id'];

$data = $db->query("
    SELECT r.*, 
           a.nama AS nama_anak, 
           a.kelas,
           p.status
    FROM riwayat_kesehatan r
    LEFT JOIN pasien p ON p.id = r.pasien_id
    LEFT JOIN anak a ON a.id_anak = p.anak_id
    WHERE r.pasien_id=$pasien_id
    LIMIT 1
")->fetch_assoc();

// --- AMAN JIKA DATA KOSONG ---
function safe($v){
    return ($v=="" || $v==null) ? "-" : nl2br(htmlspecialchars($v));
}

// --- INI PENTING: NAMA PEMBUAT SURAT ---
$petugas = "-";

if($data['nama_dokter'] != "" && $data['nama_dokter'] != null){
    $petugas = "Dokter: " . $data['nama_dokter'];
} else {
    // dokter kosong → perawat pembuat surat
    $petugas = "Perawat: " . safe($data['nama_perawat']);
}

?>
<!DOCTYPE html>
<html>
<head>
<title>Surat Izin Sakit</title>
<style>
body { font-family:Arial; padding:30px; line-height:1.5; }
.box { border:1px solid #000; padding:20px; }
b { font-size:14px; }
</style>
</head>
<body>

<h2 align="center">KLINIK RISALAH MEDIKA</h2>
<h3 align="center">SURAT IZIN SAKIT</h3>
<hr><br>

<div class="box">

<b>Nama Anak:</b><br>
<?= safe($data['nama_anak']) ?><br><br>

<b>Kelas:</b><br>
<?= safe($data['kelas']) ?><br><br>

<b>Diperiksa oleh:</b><br>
<?= $petugas ?><br><br>

<b>Diagnosa / Kondisi:</b><br>
<?= safe($data['diagnosa']) ?><br><br>

<b>Catatan Dokter/Perawat:</b><br>
<?= safe($data['catatan_dokter'] ?: $data['catatan_perawat']) ?><br><br>

<b>Resep Obat:</b><br>
<?= safe($data['resep_dokter'] ?: $data['resep_perawat']) ?><br><br>

<b>Saran Istirahat:</b><br>
1–2 hari atau sesuai kondisi siswa.<br><br>

Demikian surat ini dibuat untuk digunakan sebagaimana mestinya.
<br><br>

Jakarta, <?= date("d-m-Y") ?><br><br><br>

<b><?= $petugas ?></b>

</div>

</body>
</html>

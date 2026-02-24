<?php
require 'koneksi.php';
include 'header_user.php';
date_default_timezone_set('Asia/Jakarta');

function h($s){ return htmlspecialchars($s); }

$keyword = $_GET['q'] ?? '';
$anak = null;
$riwayat = [];
$found = false;

/* Cari siswa berdasarkan nama atau ID */
if(!empty($keyword)){
    $stmt = $db->prepare("
        SELECT * FROM anak 
        WHERE nama LIKE ? OR id_anak = ?
        LIMIT 1
    ");
    $k = "%$keyword%";
    $stmt->bind_param("si", $k, $keyword);
    $stmt->execute();
    $anak = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if($anak){
        $found = true;

        // Ambil seluruh riwayat kesehatan
        $riwayat = $db->query("
            SELECT *
            FROM riwayat_kesehatan
            WHERE anak_id = {$anak['id_anak']}
            ORDER BY created_at DESC
        ");
    }
}
?>

<style>
.record-box {
    border:1px solid #ccc;
    padding:12px;
    border-radius:6px;
    margin-bottom:10px;
    background:#f8f8f8;
    cursor:pointer;
}
.record-box:hover {
    background:#eee;
}
.record-detail {
    display:none;
    background:white;
    border:1px solid #ccc;
    padding:12px;
    border-radius:6px;
    margin-top:8px;
}
</style>

<h2>🔎 Cek Rekam Medik Siswa</h2>

<form method="get" style="margin-bottom:20px;">
    <input type="text" name="q" placeholder="Cari nama siswa atau ID..." 
           value="<?= h($keyword) ?>" 
           style="padding:8px; width:280px;">
    <button style="padding:8px 12px;">Cari</button>
</form>

<hr>

<?php if(!$keyword): ?>

<p>Masukkan nama siswa atau ID untuk melihat rekam medis.</p>

<?php elseif($keyword && !$found): ?>

<p style="color:red;">❌ Siswa tidak ditemukan.</p>

<?php else: ?>

<!-- ===================== DATA SISWA ===================== -->
<h3>👦 Data Siswa</h3>
<div style="border:1px solid #ccc; background:#f9f9f9; padding:12px; border-radius:6px; margin-bottom:20px;">
    <b>Nama:</b> <?= h($anak['nama']) ?><br>
    <b>ID Anak:</b> <?= h($anak['id_anak']) ?><br>
</div>

<!-- ===================== RIWAYAT MEDIS (TANGGAL DULU) ===================== -->
<h3>📅 Riwayat Berdasarkan Tanggal</h3>

<?php if($riwayat->num_rows == 0): ?>
    <p>Belum ada riwayat medis untuk siswa ini.</p>

<?php else: ?>

<?php $counter = 0; ?>
<?php while($r = $riwayat->fetch_assoc()): $counter++; ?>

<div class="record-box" onclick="toggleDetail('detail<?= $counter ?>')">
    <b>📌 Tanggal:</b> <?= h($r['created_at']) ?><br>
    <span style="font-size:13px;color:#444;">
        Klik untuk lihat detail pemeriksaan
    </span>
</div>

<!-- DETAIL REKAM MEDIS -->
<div id="detail<?= $counter ?>" class="record-detail">

    <h4>🩺 Pemeriksaan Lengkap</h4>

    <b>Status Akhir:</b> <?= h($r['status_akhir']) ?><br><br>

    <!-- PERAWAT -->
    <h4 style="color:#1e40af;">✔ Data Perawat</h4>
    <b>Kategori:</b> <?= h($r['kategori']) ?><br>
    <b>Keluhan:</b> <?= nl2br(h($r['keluhan'])) ?><br><br>

    <b>Tekanan Darah:</b> <?= h($r['td']) ?><br>
    <b>Tinggi:</b> <?= h($r['tinggi_cm']) ?> cm<br>
    <b>Berat:</b> <?= h($r['berat_kg']) ?> kg<br>

    <?php if(!empty($r['suhu_demam'])): ?>
        <b>Suhu:</b> <?= h($r['suhu_demam']) ?> °C<br>
    <?php endif; ?>

    <?php if(!empty($r['catatan_perawat'])): ?>
        <br><b>Catatan Perawat:</b><br>
        <?= nl2br(h($r['catatan_perawat'])) ?><br>
    <?php endif; ?>

    <?php if(!empty($r['resep_perawat'])): ?>
        <br><b>Resep Perawat:</b><br>
        <?= nl2br(h($r['resep_perawat'])) ?><br>
    <?php endif; ?>

    <hr>

    <!-- DOKTER -->
    <h4 style="color:#6d28d9;">👨‍⚕️ Pemeriksaan Dokter</h4>

    <b>Nama Dokter:</b> <?= h($r['nama_dokter']) ?><br>
    <b>Kategori Final:</b> <?= h($r['kategori']) ?><br><br>

    <b>Diagnosa:</b><br>
    <?= nl2br(h($r['diagnosa'])) ?><br><br>

    <?php if(!empty($r['catatan_dokter'])): ?>
        <b>Catatan Dokter:</b><br>
        <?= nl2br(h($r['catatan_dokter'])) ?><br><br>
    <?php endif; ?>

    <?php if(!empty($r['resep_dokter'])): ?>
        <b>Resep Dokter:</b><br>
        <?= nl2br(h($r['resep_dokter'])) ?><br><br>
    <?php endif; ?>

    <?php if(!empty($r['tindakan'])): ?>
        <b>Tindakan:</b><br>
        <?= nl2br(h($r['tindakan'])) ?><br><br>
    <?php endif; ?>

    <!-- Status Tambahan -->
    <h4 style="color:#166534;">📋 Status Tambahan</h4>
    <b>Menyusui:</b> <?= h($r['status_menyusui']) ?><br>
    <b>Alergi:</b> <?= h($r['status_alergi']) ?><br>
    <b>Hamil:</b> <?= h($r['status_hamil']) ?><br><br>

    <?php if(!empty($r['catatan_kategori'])): ?>
        <b>Catatan Kategori:</b><br>
        <?= nl2br(h($r['catatan_kategori'])) ?><br>
    <?php endif; ?>

</div>

<?php endwhile; ?>

<?php endif; ?>

<?php endif; ?>

<script>
function toggleDetail(id){
    let box = document.getElementById(id);
    box.style.display = (box.style.display === "block") ? "none" : "block";
}
</script>

<?php include 'footer_user.php'; ?>

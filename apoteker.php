<?php
require 'koneksi.php';
include 'header_user.php';
date_default_timezone_set('Asia/Jakarta');

function h($s){ return htmlspecialchars($s); }

/* LIST PASIEN */
$list = $db->query("
    SELECT p.*, a.nama AS nama_anak
    FROM pasien p
    LEFT JOIN anak a ON p.anak_id = a.id_anak
    WHERE p.status IN ('proses_obat','resep_dari_perawat')
    ORDER BY p.nomor_antrian ASC
");

$selected = null;
$riwayat = null;

/* AMBIL DATA PASIEN + SEMUA RIWAYAT KESEHATAN */
if(isset($_GET['ambil'])){
    $pid = (int)$_GET['ambil'];

    $selected = $db->query("
        SELECT p.*, a.nama AS nama_anak
        FROM pasien p
        LEFT JOIN anak a ON p.anak_id = a.id_anak
        WHERE p.id=$pid
        LIMIT 1
    ")->fetch_assoc();

    // Semua data perawat + dokter
    $riwayat = $db->query("
        SELECT *
        FROM riwayat_kesehatan
        WHERE pasien_id=$pid
        LIMIT 1
    ")->fetch_assoc();
}

/* ================================
   APOTEKER MENYELESAIKAN OBAT
   ================================ */
if(isset($_POST['selesai_obat'])){
    $pid = (int)$_POST['pasien_id'];

    foreach ($_POST['obat_id'] as $index => $oid) {

        $obat_id = (int)$oid;
        $jumlah  = (int)$_POST['jumlah'][$index];

        if ($jumlah <= 0) continue;

        // cek stok
        $cek = $db->query("SELECT stok FROM obat WHERE id=$obat_id")->fetch_assoc();
        if ($cek['stok'] < $jumlah) continue;

        // kurangi stok
        $db->query("UPDATE obat SET stok = stok - $jumlah WHERE id=$obat_id");

        // catat transaksi
        $stmt = $db->prepare("
            INSERT INTO transaksi_obat (obat_id, pasien_id, jenis, jumlah, tanggal, waktu)
            VALUES (?, ?, 'keluar', ?, CURDATE(), NOW())
        ");
        $stmt->bind_param("iii", $obat_id, $pid, $jumlah);
        $stmt->execute();
        $stmt->close();
    }

    // ubah status menjadi selesai
    $db->query("UPDATE pasien SET status='selesai' WHERE id=$pid");

    header("Location: apoteker.php?done=1");
    exit;
}
?>

<h2>Panel Apoteker</h2>

<?php if(isset($_GET['done'])): ?>
<p style="color:green;">✔ Obat berhasil diserahkan & stok sudah diperbarui.</p>
<?php endif; ?>

<div class="two-col">

<!-- ================= LIST PASIEN ================= -->
<div class="left panel">
    <h3>Daftar Pasien</h3>

    <table border="1" cellpadding="6" style="width:100%;">
    <tr>
        <th>Antrian</th>
        <th>Nama Anak</th>
        <th>Aksi</th>
    </tr>

    <?php while($p=$list->fetch_assoc()): ?>
    <tr>
        <td><?= $p['nomor_antrian'] ?></td>
        <td><?= h($p['nama_anak']) ?></td>
        <td><a href="apoteker.php?ambil=<?= $p['id'] ?>">Ambil Obat</a></td>
    </tr>
    <?php endwhile; ?>
    </table>
</div>

<!-- ================= DETAIL PASIEN ================= -->
<div class="right panel">

<?php if(!$selected): ?>
    <p>Pilih pasien di kiri.</p>
<?php else: ?>

<h3>Penyerahan Obat untuk: <?= h($selected['nama_anak']) ?></h3>
<b>Antrian:</b> <?= h($selected['nomor_antrian']) ?><br>
<hr>

<!-- ================= PANEL INFORMASI FROM PERAWAT & DOKTER ================= -->
<div style="border:1px solid #ccc; padding:12px; background:#f9f9f9; border-radius:6px; margin-bottom:20px;">

    <h3>🩺 Informasi Medis Pasien</h3>

    <!-- ===== PERAWAT ===== -->
    <h4>✔ Data Perawat</h4>

    <b>Kategori:</b> <?= h($riwayat['kategori']) ?><br>
    <b>Keluhan:</b> <?= nl2br(h($riwayat['keluhan'])) ?><br><br>

    <b>Tekanan Darah:</b> <?= h($riwayat['td']) ?><br>
    <b>Tinggi:</b> <?= h($riwayat['tinggi_cm']) ?> cm<br>
    <b>Berat:</b> <?= h($riwayat['berat_kg']) ?> kg<br>

    <?php if(!empty($riwayat['suhu_demam'])): ?>
        <b>Suhu:</b> <?= h($riwayat['suhu_demam']) ?> °C<br>
    <?php endif; ?>

    <?php if(!empty($riwayat['catatan_perawat'])): ?>
        <br><b>Catatan Perawat:</b><br>
        <?= nl2br(h($riwayat['catatan_perawat'])) ?><br>
    <?php endif; ?>

    <?php if(!empty($riwayat['resep_perawat'])): ?>
        <br><b>Resep Perawat:</b><br>
        <?= nl2br(h($riwayat['resep_perawat'])) ?><br>
    <?php endif; ?>

    <hr>

    <!-- ===== DOKTER ===== -->
    <h4>✔ Data Dokter</h4>

    <b>Nama Dokter:</b> <?= h($riwayat['nama_dokter']) ?><br>
    <b>Kategori Final:</b> <?= h($riwayat['kategori']) ?><br>

    <?php if(!empty($riwayat['catatan_kategori'])): ?>
        <b>Catatan Kategori:</b><br>
        <?= nl2br(h($riwayat['catatan_kategori'])) ?><br><br>
    <?php endif; ?>

    <b>Diagnosa:</b><br>
    <?= nl2br(h($riwayat['diagnosa'])) ?><br><br>

    <?php if(!empty($riwayat['catatan_dokter'])): ?>
        <b>Catatan Dokter:</b><br>
        <?= nl2br(h($riwayat['catatan_dokter'])) ?><br><br>
    <?php endif; ?>

    <?php if(!empty($riwayat['resep_dokter'])): ?>
        <b>Resep Dokter:</b><br>
        <?= nl2br(h($riwayat['resep_dokter'])) ?><br><br>
    <?php endif; ?>

    <?php if(!empty($riwayat['tindakan'])): ?>
        <b>Tindakan:</b><br>
        <?= nl2br(h($riwayat['tindakan'])) ?><br><br>
    <?php endif; ?>

    <!-- Status Checklist -->
    <h4>✔ Status Tambahan Pasien</h4>

    <b>Status Menyusui:</b> <?= h($riwayat['status_menyusui']) ?><br>
    <b>Status Alergi:</b> <?= h($riwayat['status_alergi']) ?><br>
    <b>Status Hamil:</b> <?= h($riwayat['status_hamil']) ?><br>

</div>

<!-- ================= FORM OBAT ================= -->
<form method="post">
<input type="hidden" name="pasien_id" value="<?= $selected['id'] ?>">

<h4>Pilih Obat untuk Dikeluarkan</h4>

<?php
$ob = $db->query("SELECT * FROM obat ORDER BY nama_obat ASC");
while($o = $ob->fetch_assoc()):
?>
<div style="border:1px solid #ddd; padding:8px; margin-bottom:6px;">
    <b><?= $o['nama_obat'] ?></b> — Stok: <?= $o['stok'] ?> <?= $o['satuan'] ?><br>

    <input type="hidden" name="obat_id[]" value="<?= $o['id'] ?>">
    <input type="number" name="jumlah[]" placeholder="Jumlah diambil" min="0" style="width:120px;">
</div>
<?php endwhile; ?>

<button name="selesai_obat" style="margin-top:15px;">
    ✔ Obat Diserahkan ke Pasien
</button>

</form>

<?php endif; ?>

</div>
</div>

<?php include 'footer_user.php'; ?>

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

/* ================================
   DATA UNTUK TAB STOCK & RANKING
   ================================ */
$obat_list = $db->query("SELECT * FROM obat ORDER BY nama_obat ASC");

$ranking = $db->query("
    SELECT a.id_anak, a.nama AS nama_anak,
           SUM(t.jumlah) AS total_jumlah,
           COUNT(DISTINCT t.pasien_id) AS frekuensi
    FROM transaksi_obat t
    JOIN pasien p ON p.id = t.pasien_id
    JOIN anak a ON a.id_anak = p.anak_id
    WHERE t.jenis = 'keluar'
    GROUP BY a.id_anak, a.nama
    ORDER BY total_jumlah DESC
    LIMIT 50
");
$max_total = 0;
$rank_rows = [];
while ($r = $ranking->fetch_assoc()) {
    $rank_rows[] = $r;
    if ($r['total_jumlah'] > $max_total) $max_total = (int)$r['total_jumlah'];
}
?>

<link rel="stylesheet" href="style.css">
<style>
.apotek-tab { padding: 5px 12px; margin-right: 4px; cursor: pointer; font-size: 12px; border: 1px solid var(--c-border); background: #fff; border-radius: 4px; }
.apotek-tab.active { background: var(--c-primary); color: #fff; border-color: var(--c-primary); }
.apotek-tabContent { display: none; margin-top: 10px; }
.apotek-tabContent.active { display: block; }
.rank-bar { height: 20px; background: var(--c-primary); border-radius: 4px; min-width: 2%; }
.rank-bar-wrap { background: #e2e8f0; border-radius: 4px; overflow: hidden; }
</style>
<h1 class="page-title">Panel Apoteker</h1>
<?php if(isset($_GET['done'])): ?><div class="alert alert-success">✔ Obat berhasil diserahkan & stok diperbarui.</div><?php endif; ?>

<button type="button" class="apotek-tab active" data-tab="tabPasien">Daftar Pasien</button>
<button type="button" class="apotek-tab" data-tab="tabStock">Stock Obat</button>
<button type="button" class="apotek-tab" data-tab="tabRanking">Ranking Penggunaan Obat</button>

<div id="tabPasien" class="apotek-tabContent active">
<div class="two-col">
<div class="left panel">
    <h3>Daftar Pasien</h3>
    <table style="width:100%;">
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
<div class="panel-info" style="margin-bottom:14px;">

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
</div>

<!-- ================= TAB STOCK OBAT ================= -->
<div id="tabStock" class="apotek-tabContent">
    <div class="panel">
        <h3>Stock Obat</h3>
        <table style="width:100%;">
            <tr>
                <th>No</th>
                <th>Nama Obat</th>
                <th>Stok</th>
                <th>Satuan</th>
            </tr>
            <?php
            $obat_list->data_seek(0);
            $no = 1;
            while ($o = $obat_list->fetch_assoc()):
                $stok = (int)$o['stok'];
                $row_class = $stok <= 0 ? ' style="background:#fee2e2;"' : ($stok <= 5 ? ' style="background:#fef3c7;"' : '');
            ?>
            <tr<?= $row_class ?>>
                <td><?= $no++ ?></td>
                <td><?= h($o['nama_obat']) ?></td>
                <td><strong><?= $stok ?></strong></td>
                <td><?= h($o['satuan'] ?? '-') ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php if ($obat_list->num_rows == 0): ?>
            <p class="muted">Belum ada data obat.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ================= TAB RANKING PENGGUNAAN OBAT ================= -->
<div id="tabRanking" class="apotek-tabContent">
    <div class="panel">
        <h3>Ranking Penggunaan Obat (per Pasien/Anak)</h3>
        <p class="muted">Berdasarkan total jumlah item obat yang keluar. Siapa yang paling sering menggunakan obat.</p>
        <?php if (empty($rank_rows)): ?>
            <p class="muted">Belum ada data transaksi keluar.</p>
        <?php else: ?>
        <table style="width:100%; margin-top:10px;">
            <tr>
                <th style="width:50px;">Rank</th>
                <th>Nama Pasien / Anak</th>
                <th style="width:100px;">Total Jumlah</th>
                <th style="width:90px;">Frekuensi</th>
                <th style="width:200px;">Grafik</th>
            </tr>
            <?php foreach ($rank_rows as $i => $row):
                $pct = $max_total > 0 ? round(($row['total_jumlah'] / $max_total) * 100) : 0;
            ?>
            <tr>
                <td><strong>#<?= $i + 1 ?></strong></td>
                <td><?= h($row['nama_anak']) ?></td>
                <td><?= number_format($row['total_jumlah']) ?></td>
                <td><?= $row['frekuensi'] ?>x kunjungan</td>
                <td>
                    <div class="rank-bar-wrap" style="height:22px;">
                        <div class="rank-bar" style="width:<?= $pct ?>%;"></div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.apotek-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var tabId = this.getAttribute('data-tab');
        document.querySelectorAll('.apotek-tab').forEach(function(b) { b.classList.remove('active'); });
        document.querySelectorAll('.apotek-tabContent').forEach(function(c) { c.classList.remove('active'); });
        this.classList.add('active');
        var el = document.getElementById(tabId);
        if (el) el.classList.add('active');
    });
});
</script>

<?php include 'footer_user.php'; ?>

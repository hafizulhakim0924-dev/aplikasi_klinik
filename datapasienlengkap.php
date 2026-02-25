<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =============================
   KONFIG DATABASE
============================= */
date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$user = "xreiins1_clinic";
$pass = "Hakim123!";
$dbname = "xreiins1_clinic";

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("<b>Koneksi gagal:</b> " . mysqli_connect_error());
}

/* =============================
   SEARCH FEATURE
============================= */
$keyword = "";
$whereClause = "";

if (isset($_GET['nama']) && $_GET['nama'] !== "") {
    $keyword = mysqli_real_escape_string($conn, $_GET['nama']);

    // FILTER RIWAYAT BERDASARKAN TABEL ANAK
    $whereClause = "WHERE a.nama LIKE '%$keyword%'";
}
?>

<link rel="stylesheet" href="style.css">
<style>
.search-box-wrapper { position: relative; width: 220px; }
.search-box { padding: 6px 8px; width: 100%; font-size: 12px; }
.suggestion-list { position: absolute; top: 32px; left: 0; width: 100%; background: var(--c-card); border: 1px solid var(--c-border); border-radius: 4px; max-height: 180px; overflow-y: auto; display: none; z-index: 999; font-size: 12px; }
.suggestion-item { padding: 6px 8px; cursor: pointer; }
.suggestion-item:hover { background: #f1f5f9; }
.status-badge { padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 11px; }
.status-user { background: #fef3c7; color: #b45309; }
.status-perawat { background: #dbeafe; color: #1e40af; }
.status-dokter { background: #ede9fe; color: #6d28d9; }
.status-apotek { background: #dcfce7; color: #166534; }
</style>
<h1 class="page-title">Data Riwayat Pasien – Klinik Risalah Medika</h1>

<!-- SEARCH BOX + DROPDOWN AUTOSUGGEST -->
<form method="GET">
    <div class="search-box-wrapper">
        <input type="text" id="searchInput" name="nama"
               class="search-box" placeholder="Cari nama anak..."
               autocomplete="off" value="<?= htmlspecialchars($keyword) ?>">

        <div id="suggestions" class="suggestion-list"></div>
    </div>

    <button type="submit">Cari</button>

    <?php if($keyword != ""): ?>
        <a href="datapasienlengkap.php">Reset</a>
    <?php endif; ?>
</form>

<?php
/* =============================
   QUERY RIWAYAT + FILTER NAMA
============================= */

$sql = "
    SELECT r.*, a.nama AS nama_anak
    FROM riwayat_kesehatan r
    LEFT JOIN anak a ON r.anak_id = a.id_anak
    $whereClause
    ORDER BY r.id DESC
";

$q = mysqli_query($conn, $sql) or die("<b>SQL ERROR:</b> " . mysqli_error($conn));
?>

<?php if(mysqli_num_rows($q) == 0): ?>
<p><b>Tidak ada data ditemukan.</b></p>
<?php else: ?>

<table>
<tr>
    <th>Tanggal</th>
    <th>Nama Anak</th>
    <th>No Antrian</th>
    <th>Kategori</th>
    <th>Keluhan</th>
    <th>Perawat</th>
    <th>Dokter</th>
    <th>Apoteker</th>
    <th>Status</th>
    <th>Aksi</th>
</tr>

<?php while($r = mysqli_fetch_assoc($q)): ?>
<tr>

    <td><?= $r['created_at'] ?></td>
    <td><?= htmlspecialchars($r['nama_anak']) ?></td>
    <td><?= htmlspecialchars($r['nomor_antrian']) ?></td>

    <td>
        <b><?= htmlspecialchars($r['kategori']) ?></b><br>
        <?php if($r['suhu_demam']): ?>Suhu: <?= $r['suhu_demam'] ?>°C<br><?php endif; ?>
        <?php if($r['catatan_kategori']): ?>
            <i><?= nl2br(htmlspecialchars($r['catatan_kategori'])) ?></i>
        <?php endif; ?>
    </td>

    <td><?= nl2br(htmlspecialchars($r['keluhan'])) ?></td>

    <td>
        <?= htmlspecialchars($r['nama_perawat']) ?><br>
        <?php if($r['td']): ?>TD: <?= $r['td'] ?><br><?php endif; ?>
        <?php if($r['tinggi_cm']): ?>Tinggi: <?= $r['tinggi_cm'] ?> cm<br><?php endif; ?>
        <?php if($r['berat_kg']): ?>Berat: <?= $r['berat_kg'] ?> kg<br><?php endif; ?>
        <?= nl2br(htmlspecialchars($r['catatan_perawat'])) ?>
    </td>

    <td>
        <?= htmlspecialchars($r['nama_dokter']) ?><br>
        <?= nl2br(htmlspecialchars($r['diagnosa'])) ?><br>
        <?= nl2br(htmlspecialchars($r['resep_dokter'])) ?><br>
        <?= nl2br(htmlspecialchars($r['tindakan'])) ?>
    </td>

    <td>
        <?= htmlspecialchars($r['nama_apoteker']) ?><br>
        <?= nl2br(htmlspecialchars($r['obat_apoteker'])) ?>
    </td>

    <td><?= htmlspecialchars($r['status_akhir']) ?></td>

    <td>
        <?php if($r['status_akhir']=="dokter_selesai"): ?>
            <a href="generate_surat_dokter.php?pasien_id=<?= $r['pasien_id'] ?>" target="_blank">Surat (Dokter)</a>
        <?php else: ?>-<?php endif; ?>
    </td>

</tr>
<?php endwhile; ?>

</table>
<?php endif; ?>

<script>
// AJAX AUTOSUGGEST UNTUK FIELD 'nama' DARI TABEL 'anak'
document.getElementById("searchInput").addEventListener("keyup", function(){
    let keyword = this.value;
    let box = document.getElementById("suggestions");

    if(keyword.length < 1){
        box.style.display = "none";
        return;
    }

    let xhr = new XMLHttpRequest();
    xhr.open("GET", "search_suggestion.php?key=" + keyword, true);
    xhr.onload = function(){
        box.innerHTML = this.responseText;
        box.style.display = "block";
    };
    xhr.send();
});

function pilihNama(nama){
    document.getElementById("searchInput").value = nama;
    document.getElementById("suggestions").style.display = "none";
}
</script>

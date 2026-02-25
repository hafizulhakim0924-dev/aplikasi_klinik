<?php
require 'koneksi.php';
include 'header_user.php'; // tanpa login

function h($s){ return htmlspecialchars($s); }

// -------------------------
// HELPERS
// -------------------------
function count_pasien_range($db,$start,$end){
    $q = $db->prepare("SELECT COUNT(*) AS j FROM pasien WHERE DATE(tgl_daftar) BETWEEN ? AND ?");
    $q->bind_param("ss",$start,$end);
    $q->execute();
    return $q->get_result()->fetch_assoc()['j'];
}

function kategori_stats($db,$start,$end){
    $q = $db->prepare("
        SELECT kategori, COUNT(*) AS jumlah 
        FROM riwayat_kesehatan
        WHERE kategori IS NOT NULL AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY kategori
        ORDER BY jumlah DESC
    ");
    $q->bind_param("ss",$start,$end);
    $q->execute();
    return $q->get_result();
}

function kategori_per_tanggal($db,$start,$end){
    $q = $db->prepare("
        SELECT DATE(created_at) AS tgl, kategori, COUNT(*) AS jumlah 
        FROM riwayat_kesehatan
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at), kategori
        ORDER BY DATE(created_at), jumlah DESC
    ");
    $q->bind_param("ss",$start,$end);
    $q->execute();
    return $q->get_result();
}

// -------------------------
// WAKTU PERIODE
// -------------------------
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('-6 days'));
$month_start = date('Y-m-01');
$year_start = date('Y-01-01'); // cadangan

// Custom
$custom_start = $_GET['start'] ?? null;
$custom_end   = $_GET['end']   ?? null;

// Jika salah satu kosong, jangan tampilkan
if($custom_start && !$custom_end) $custom_start = $custom_end = null;
if($custom_end && !$custom_start) $custom_start = $custom_end = null;

?>
<link rel="stylesheet" href="style.css">
<h1 class="page-title">Admin – Klinik Risalah Medika</h1>
<button onclick="openTab('tabGlobal')" class="tabBtn">GLOBAL</button>
<button onclick="openTab('tabWeek')" class="tabBtn">PEKAN INI</button>
<button onclick="openTab('tabMonth')" class="tabBtn">BULAN INI</button>
<button onclick="openTab('tabMonthToToday')" class="tabBtn">TGL 1 → HARI INI</button>
<button onclick="openTab('tabCustom')" class="tabBtn">CUSTOM RANGE</button>
<style>
.tabContent{ display:none; margin-top:10px; }
</style>

<!-- ========================================================= -->
<!-- ===================== TAB 1 — GLOBAL ===================== -->
<!-- ========================================================= -->
<div id="tabGlobal" class="tabContent" style="display:block;">
    <h3>GLOBAL - Semua Waktu</h3>

    <?php
        $global_start = "1970-01-01";
        $global_end = $today;
        $global_pasien = count_pasien_range($db,$global_start,$global_end);
        $global_kat = kategori_stats($db,$global_start,$global_end);
    ?>

    <p>Total pasien sepanjang waktu: <b><?= $global_pasien ?></b></p>

    <h4>Ranking Kategori Penyakit (Global)</h4>
    <?php if($global_kat->num_rows==0): ?>
        <p>Belum ada data kategori.</p>
    <?php else: ?>
        <table>
            <tr><th>Rank</th><th>Kategori</th><th>Jumlah</th></tr>
            <?php $rank=1; while($r=$global_kat->fetch_assoc()): ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td><?= h($r['kategori']) ?></td>
                    <td><?= h($r['jumlah']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>

<!-- ========================================================= -->
<!-- =================== TAB 2 — PEKAN INI ==================== -->
<!-- ========================================================= -->
<div id="tabWeek" class="tabContent">
    <h3>Pekan Ini (<?= $week_start ?> → <?= $today ?>)</h3>

    <?php
        $week_pasien = count_pasien_range($db,$week_start,$today);
        $week_kat = kategori_stats($db,$week_start,$today);
    ?>

    <p>Total pasien pekan ini: <b><?= $week_pasien ?></b></p>

    <h4>Ranking Kategori (Pekan Ini)</h4>
    <?php if($week_kat->num_rows==0): ?>
        <p>Tidak ada data.</p>
    <?php else: ?>
        <table>
            <tr><th>Rank</th><th>Kategori</th><th>Jumlah</th></tr>
            <?php $rank=1; while($r=$week_kat->fetch_assoc()): ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td><?= h($r['kategori']) ?></td>
                    <td><?= h($r['jumlah']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>

<!-- ========================================================= -->
<!-- =================== TAB 3 — BULAN INI ==================== -->
<!-- ========================================================= -->
<div id="tabMonth" class="tabContent">
    <h3>Bulan Ini (<?= $month_start ?> → <?= $today ?>)</h3>

    <?php
        $month_pasien = count_pasien_range($db,$month_start,$today);
        $month_kat = kategori_stats($db,$month_start,$today);
    ?>

    <p>Total pasien bulan ini: <b><?= $month_pasien ?></b></p>

    <h4>Ranking Kategori (Bulan Ini)</h4>
    <?php if($month_kat->num_rows==0): ?>
        <p>Tidak ada data.</p>
    <?php else: ?>
        <table>
            <tr><th>Rank</th><th>Kategori</th><th>Jumlah</th></tr>
            <?php $rank=1; while($r=$month_kat->fetch_assoc()): ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td><?= h($r['kategori']) ?></td>
                    <td><?= h($r['jumlah']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>

<!-- ========================================================= -->
<!-- ======= TAB 4 — DARI TGL 1 BULAN INI → HARI INI ========= -->
<!-- ========================================================= -->
<div id="tabMonthToToday" class="tabContent">
    <h3>Tanggal 1 sampai Hari Ini (<?= $month_start ?> → <?= $today ?>)</h3>

    <?php
        $mto_pasien = count_pasien_range($db,$month_start,$today);
        $mto_kat = kategori_stats($db,$month_start,$today);
    ?>

    <p>Total pasien rentang ini: <b><?= $mto_pasien ?></b></p>

    <h4>Ranking Kategori</h4>
    <?php if($mto_kat->num_rows==0): ?>
        <p>Tidak ada data.</p>
    <?php else: ?>
        <table>
            <tr><th>Rank</th><th>Kategori</th><th>Jumlah</th></tr>
            <?php $rank=1; while($r=$mto_kat->fetch_assoc()): ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td><?= h($r['kategori']) ?></td>
                    <td><?= h($r['jumlah']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>

<!-- ========================================================= -->
<!-- ================ TAB 5 — CUSTOM RANGE ==================== -->
<!-- ========================================================= -->
<div id="tabCustom" class="tabContent">
    <h3>Custom Range</h3>

    <form method="get">
        <label>Start: <input type="date" name="start" value="<?= h($custom_start) ?>"></label>
        <label>End: <input type="date" name="end" value="<?= h($custom_end) ?>"></label>
        <button>Proses</button>
    </form>

    <?php if($custom_start && $custom_end): ?>

        <h4>Hasil dari <?= h($custom_start) ?> → <?= h($custom_end) ?></h4>

        <?php
            $custom_pasien = count_pasien_range($db,$custom_start,$custom_end);
            $custom_kat = kategori_stats($db,$custom_start,$custom_end);
            $custom_per_tgl = kategori_per_tanggal($db,$custom_start,$custom_end);
        ?>

        <p>Total pasien: <b><?= $custom_pasien ?></b></p>

        <h4>Ranking kategori</h4>
        <?php if($custom_kat->num_rows==0): ?>
            <p>Tidak ada kategori pada rentang ini.</p>
        <?php else: ?>
            <table>
                <tr><th>Rank</th><th>Kategori</th><th>Jumlah</th></tr>
                <?php $rank=1; while($r=$custom_kat->fetch_assoc()): ?>
                    <tr>
                        <td><?= $rank++ ?></td>
                        <td><?= h($r['kategori']) ?></td>
                        <td><?= h($r['jumlah']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php endif; ?>

        <h4>Detail per Tanggal & Kategori</h4>
        <?php if($custom_per_tgl->num_rows==0): ?>
            <p>Tidak ada data.</p>
        <?php else: ?>
            <table>
                <tr><th>Tanggal</th><th>Kategori</th><th>Jumlah</th></tr>
                <?php while($r=$custom_per_tgl->fetch_assoc()): ?>
                    <tr>
                        <td><?= h($r['tgl']) ?></td>
                        <td><?= h($r['kategori']) ?></td>
                        <td><?= h($r['jumlah']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
function openTab(id){
    document.querySelectorAll('.tabContent').forEach(e=>e.style.display='none');
    document.getElementById(id).style.display='block';
}
</script>

<?php include 'footer_user.php'; ?>

<?php
require 'koneksi.php';
include 'header_user.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}

$u = current_user();
if (!$u || ($u['role'] !== 'direktur' && empty($u['is_master']))) {
    header('Location: login.php');
    exit;
}

// ================== FILTER TANGGAL ==================
$period = $_GET['period'] ?? 'month';
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to'] ?? '';

$today = date('Y-m-d');
$d_sql = '';
$d_sql_pasien = '';

if ($period === 'custom' && $date_from !== '' && $date_to !== '') {
    $d_from = $db->real_escape_string($date_from);
    $d_to   = $db->real_escape_string($date_to);
    $d_sql = " AND DATE(r.created_at) BETWEEN '$d_from' AND '$d_to' ";
    $d_sql_pasien = " AND DATE(p.tgl_daftar) BETWEEN '$d_from' AND '$d_to' ";
} elseif ($period === 'today') {
    $d_sql = " AND DATE(r.created_at) = '$today' ";
    $d_sql_pasien = " AND DATE(p.tgl_daftar) = '$today' ";
} elseif ($period === 'week') {
    $start_week = date('Y-m-d', strtotime('monday this week'));
    $d_sql = " AND DATE(r.created_at) BETWEEN '$start_week' AND '$today' ";
    $d_sql_pasien = " AND DATE(p.tgl_daftar) BETWEEN '$start_week' AND '$today' ";
} else {
    // month (default)
    $start_month = date('Y-m-01');
    $d_sql = " AND DATE(r.created_at) BETWEEN '$start_month' AND '$today' ";
    $d_sql_pasien = " AND DATE(p.tgl_daftar) BETWEEN '$start_month' AND '$today' ";
}

// Helper: agregasi kategori per baris, pisah berdasarkan koma
function agregasi_kategori_by_comma($res) {
    $counts = [];
    while ($r = $res->fetch_assoc()) {
        $raw = $r['kategori'] ?? '';
        if ($raw === '') continue;
        $parts = array_map('trim', explode(',', $raw));
        foreach ($parts as $k) {
            if ($k !== '') {
                $counts[$k] = ($counts[$k] ?? 0) + 1;
            }
        }
    }
    arsort($counts);
    $out = [];
    $i = 0;
    foreach ($counts as $k => $j) {
        $out[] = ['kategori' => $k, 'jumlah' => $j];
        if (++$i >= 15) break;
    }
    return $out;
}

// ================== RINGKASAN ANGKA ==================
$total_pasien = 0;
$total_pasien_diobati = 0;
$total_pasien_dokter = 0;
$total_flow_perawat_apoteker = 0;
$total_flow_perawat_dokter_apoteker = 0;

$row = $db->query("SELECT COUNT(*) AS c FROM pasien p WHERE 1=1 $d_sql_pasien")->fetch_assoc();
$total_pasien = (int)($row['c'] ?? 0);

$row = $db->query("
    SELECT COUNT(DISTINCT r.pasien_id) AS c
    FROM riwayat_kesehatan r
    WHERE r.status_akhir <> 'user_saja' $d_sql
")->fetch_assoc();
$total_pasien_diobati = (int)($row['c'] ?? 0);

$row = $db->query("
    SELECT COUNT(DISTINCT r.pasien_id) AS c
    FROM riwayat_kesehatan r
    WHERE r.status_akhir = 'dokter_selesai' $d_sql
")->fetch_assoc();
$total_pasien_dokter = (int)($row['c'] ?? 0);
$total_flow_perawat_dokter_apoteker = $total_pasien_dokter;

$row = $db->query("
    SELECT COUNT(DISTINCT r.pasien_id) AS c
    FROM riwayat_kesehatan r
    WHERE r.status_akhir = 'resep_dari_perawat' $d_sql
")->fetch_assoc();
$total_flow_perawat_apoteker = (int)($row['c'] ?? 0);

// ================== DATA GRAFIK KATEGORI (pisah per koma) ==================
$kat_all = [];
$kat_perawat = [];
$kat_dokter = [];

$res = $db->query("
    SELECT r.kategori
    FROM riwayat_kesehatan r
    WHERE r.kategori IS NOT NULL AND TRIM(r.kategori) <> '' $d_sql
");
$kat_all = agregasi_kategori_by_comma($res);

$res = $db->query("
    SELECT r.kategori
    FROM riwayat_kesehatan r
    WHERE r.kategori IS NOT NULL AND TRIM(r.kategori) <> ''
      AND r.status_akhir IN ('perawat_selesai','resep_dari_perawat') $d_sql
");
$kat_perawat = agregasi_kategori_by_comma($res);

$res = $db->query("
    SELECT r.kategori
    FROM riwayat_kesehatan r
    WHERE r.kategori IS NOT NULL AND TRIM(r.kategori) <> ''
      AND r.status_akhir = 'dokter_selesai' $d_sql
");
$kat_dokter = agregasi_kategori_by_comma($res);

function max_jumlah($rows) {
    $m = 0;
    foreach ($rows as $r) {
        if ((int)$r['jumlah'] > $m) $m = (int)$r['jumlah'];
    }
    return $m;
}

$max_all = max_jumlah($kat_all);
$max_perawat = max_jumlah($kat_perawat);
$max_dokter = max_jumlah($kat_dokter);

// user_saja count dengan filter tanggal
$user_saja_sql = "SELECT COUNT(DISTINCT r.pasien_id) AS c FROM riwayat_kesehatan r WHERE r.status_akhir='user_saja' $d_sql";
$row_user_saja = $db->query($user_saja_sql)->fetch_assoc();
$count_user_saja = (int)($row_user_saja['c'] ?? 0);
?>

<style>
.dir-summary-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-bottom:18px; }
.dir-card { background:#ffffff; border:1px solid var(--c-border); border-radius:8px; padding:10px 12px; }
.dir-card-title { font-size:12px; color:var(--c-muted); margin-bottom:4px; }
.dir-card-value { font-size:20px; font-weight:700; }
.dir-layout { display:grid; grid-template-columns: 2fr 1fr; gap:14px; align-items:flex-start; }
.dir-panel { background:#ffffff; border:1px solid var(--c-border); border-radius:8px; padding:12px 14px; }
.dir-panel h3 { margin-top:0; margin-bottom:8px; font-size:14px; }
.dir-table { width:100%; border-collapse:collapse; font-size:12px; }
.dir-table th, .dir-table td { padding:4px 6px; text-align:left; }
.dir-table th { background:#f1f5f9; }
.dir-bar-wrap { background:#e2e8f0; border-radius:4px; overflow:hidden; height:18px; }
.dir-bar { height:18px; background:var(--c-primary); border-radius:4px; }
.dir-filter { background:#f8fafc; border:1px solid var(--c-border); border-radius:8px; padding:12px 14px; margin-bottom:16px; }
.dir-filter form { display:flex; flex-wrap:wrap; align-items:flex-end; gap:10px 14px; }
.dir-filter label { font-size:12px; display:block; margin-bottom:2px; }
.dir-filter select, .dir-filter input[type=date] { padding:6px 8px; font-size:12px; }
.dir-filter .custom-dates { display:flex; gap:6px; align-items:flex-end; }
.dir-filter .custom-dates input { width:140px; }
@media(max-width: 900px){
  .dir-layout { grid-template-columns: 1fr; }
}
</style>

<h1 class="page-title">Panel Direktur RS – Dashboard Klinik</h1>

<div class="dir-filter">
    <form method="get" action="">
        <div>
            <label>Periode</label>
            <select name="period" id="dirPeriod">
                <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Hari ini</option>
                <option value="week"  <?= $period === 'week'  ? 'selected' : '' ?>>Minggu ini</option>
                <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Bulan ini</option>
                <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom</option>
            </select>
        </div>
        <div class="custom-dates" id="dirCustomDates" style="<?= $period !== 'custom' ? 'display:none;' : '' ?>">
            <div>
                <label>Dari</label>
                <input type="date" name="date_from" value="<?= h($date_from) ?>">
            </div>
            <div>
                <label>Sampai</label>
                <input type="date" name="date_to" value="<?= h($date_to) ?>">
            </div>
        </div>
        <div>
            <button type="submit" class="btn">Terapkan</button>
        </div>
    </form>
</div>

<script>
document.getElementById('dirPeriod').addEventListener('change', function(){
    var show = this.value === 'custom';
    document.getElementById('dirCustomDates').style.display = show ? 'flex' : 'none';
});
</script>

<?php
$period_label = 'Bulan ini';
if ($period === 'today') $period_label = 'Hari ini';
elseif ($period === 'week') $period_label = 'Minggu ini';
elseif ($period === 'custom') {
    $period_label = ($date_from && $date_to) ? (date('d/m/Y', strtotime($date_from)) . ' – ' . date('d/m/Y', strtotime($date_to))) : 'Custom (pilih tanggal)';
}
?>
<p class="muted" style="margin-top:-8px; margin-bottom:12px; font-size:12px;">Menampilkan data: <strong><?= h($period_label) ?></strong></p>

<div class="dir-summary-grid">
    <div class="dir-card">
        <div class="dir-card-title">Total pasien terdaftar</div>
        <div class="dir-card-value"><?= number_format($total_pasien) ?></div>
    </div>
    <div class="dir-card">
        <div class="dir-card-title">Total pasien yang sudah diobati</div>
        <div class="dir-card-value"><?= number_format($total_pasien_diobati) ?></div>
    </div>
    <div class="dir-card">
        <div class="dir-card-title">Total pasien diobati dokter</div>
        <div class="dir-card-value"><?= number_format($total_pasien_dokter) ?></div>
    </div>
    <div class="dir-card">
        <div class="dir-card-title">Alur perawat → apoteker</div>
        <div class="dir-card-value"><?= number_format($total_flow_perawat_apoteker) ?></div>
    </div>
    <div class="dir-card">
        <div class="dir-card-title">Alur perawat → dokter → apoteker</div>
        <div class="dir-card-value"><?= number_format($total_flow_perawat_dokter_apoteker) ?></div>
    </div>
</div>

<div class="dir-layout">
    <div class="dir-panel">
        <h3>📊 Grafik Penyakit Paling Sering (Semua Data)</h3>
        <?php if (empty($kat_all)): ?>
            <p class="muted">Belum ada data kategori penyakit.</p>
        <?php else: ?>
            <table class="dir-table">
                <tr><th>Kategori</th><th>Jumlah</th><th>Grafik</th></tr>
                <?php foreach ($kat_all as $row): 
                    $pct = $max_all > 0 ? round(($row['jumlah'] / $max_all) * 100) : 0;
                ?>
                <tr>
                    <td><?= h($row['kategori']) ?></td>
                    <td><?= (int)$row['jumlah'] ?>x</td>
                    <td style="width:200px;">
                        <div class="dir-bar-wrap">
                            <div class="dir-bar" style="width:<?= $pct ?>%;"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="dir-panel">
        <h3>Ringkasan Alur Layanan</h3>
        <table class="dir-table">
            <tr><th>Jenis</th><th>Jumlah</th></tr>
            <tr>
                <td>Pasien hanya user (belum diperiksa)</td>
                <td><?= number_format($count_user_saja) ?></td>
            </tr>
            <tr>
                <td>Hanya perawat (belum ke dokter)</td>
                <td><?= number_format($total_flow_perawat_apoteker) ?></td>
            </tr>
            <tr>
                <td>Melibatkan dokter</td>
                <td><?= number_format($total_pasien_dokter) ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="dir-layout" style="margin-top:16px;">
    <div class="dir-panel">
        <h3>Kategori Paling Sering – Diisi Perawat</h3>
        <p class="muted" style="font-size:11px;">Berdasarkan riwayat dengan status akhir <code>perawat_selesai</code> atau <code>resep_dari_perawat</code>.</p>
        <?php if (empty($kat_perawat)): ?>
            <p class="muted">Belum ada data kategori dari perawat.</p>
        <?php else: ?>
            <table class="dir-table">
                <tr><th>Kategori</th><th>Jumlah</th><th>Grafik</th></tr>
                <?php foreach ($kat_perawat as $row): 
                    $pct = $max_perawat > 0 ? round(($row['jumlah'] / $max_perawat) * 100) : 0;
                ?>
                <tr>
                    <td><?= h($row['kategori']) ?></td>
                    <td><?= (int)$row['jumlah'] ?>x</td>
                    <td style="width:180px;">
                        <div class="dir-bar-wrap">
                            <div class="dir-bar" style="width:<?= $pct ?>%;"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="dir-panel">
        <h3>Kategori Paling Sering – Diisi Dokter</h3>
        <p class="muted" style="font-size:11px;">Berdasarkan riwayat dengan status akhir <code>dokter_selesai</code>.</p>
        <?php if (empty($kat_dokter)): ?>
            <p class="muted">Belum ada data kategori dari dokter.</p>
        <?php else: ?>
            <table class="dir-table">
                <tr><th>Kategori</th><th>Jumlah</th><th>Grafik</th></tr>
                <?php foreach ($kat_dokter as $row): 
                    $pct = $max_dokter > 0 ? round(($row['jumlah'] / $max_dokter) * 100) : 0;
                ?>
                <tr>
                    <td><?= h($row['kategori']) ?></td>
                    <td><?= (int)$row['jumlah'] ?>x</td>
                    <td style="width:180px;">
                        <div class="dir-bar-wrap">
                            <div class="dir-bar" style="width:<?= $pct ?>%;"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer_user.php'; ?>


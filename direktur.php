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

// ================== RINGKASAN ANGKA ==================
$total_pasien = 0;
$total_pasien_diobati = 0;
$total_pasien_dokter = 0;
$total_flow_perawat_apoteker = 0;
$total_flow_perawat_dokter_apoteker = 0;

$row = $db->query("SELECT COUNT(*) AS c FROM pasien")->fetch_assoc();
$total_pasien = (int)($row['c'] ?? 0);

// pasien yang sudah punya tindakan (bukan hanya user_saja)
$row = $db->query("
    SELECT COUNT(DISTINCT pasien_id) AS c
    FROM riwayat_kesehatan
    WHERE status_akhir <> 'user_saja'
")->fetch_assoc();
$total_pasien_diobati = (int)($row['c'] ?? 0);

// pasien yang diobati dokter (alur perawat–dokter–apoteker)
$row = $db->query("
    SELECT COUNT(DISTINCT pasien_id) AS c
    FROM riwayat_kesehatan
    WHERE status_akhir = 'dokter_selesai'
")->fetch_assoc();
$total_pasien_dokter = (int)($row['c'] ?? 0);
$total_flow_perawat_dokter_apoteker = $total_pasien_dokter;

// pasien dengan resep langsung dari perawat (alur perawat–apoteker)
$row = $db->query("
    SELECT COUNT(DISTINCT pasien_id) AS c
    FROM riwayat_kesehatan
    WHERE status_akhir = 'resep_dari_perawat'
")->fetch_assoc();
$total_flow_perawat_apoteker = (int)($row['c'] ?? 0);

// ================== DATA GRAFIK KATEGORI ==================
$kat_all = [];
$kat_perawat = [];
$kat_dokter = [];

$res = $db->query("
    SELECT kategori, COUNT(*) AS jumlah
    FROM riwayat_kesehatan
    WHERE kategori IS NOT NULL AND kategori <> ''
    GROUP BY kategori
    ORDER BY jumlah DESC
    LIMIT 15
");
while ($r = $res->fetch_assoc()) {
    $kat_all[] = $r;
}

$res = $db->query("
    SELECT kategori, COUNT(*) AS jumlah
    FROM riwayat_kesehatan
    WHERE kategori IS NOT NULL AND kategori <> ''
      AND status_akhir IN ('perawat_selesai','resep_dari_perawat')
    GROUP BY kategori
    ORDER BY jumlah DESC
    LIMIT 15
");
while ($r = $res->fetch_assoc()) {
    $kat_perawat[] = $r;
}

$res = $db->query("
    SELECT kategori, COUNT(*) AS jumlah
    FROM riwayat_kesehatan
    WHERE kategori IS NOT NULL AND kategori <> ''
      AND status_akhir = 'dokter_selesai'
    GROUP BY kategori
    ORDER BY jumlah DESC
    LIMIT 15
");
while ($r = $res->fetch_assoc()) {
    $kat_dokter[] = $r;
}

// maksimum untuk skala bar
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
@media(max-width: 900px){
  .dir-layout { grid-template-columns: 1fr; }
}
</style>

<h1 class="page-title">Panel Direktur RS – Dashboard Klinik</h1>

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
                <td>
                    <?php
                    $r = $db->query("SELECT COUNT(DISTINCT pasien_id) AS c FROM riwayat_kesehatan WHERE status_akhir='user_saja'")->fetch_assoc();
                    echo number_format((int)($r['c'] ?? 0));
                    ?>
                </td>
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


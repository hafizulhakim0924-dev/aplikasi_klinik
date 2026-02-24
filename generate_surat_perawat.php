<?php
require 'koneksi.php';
date_default_timezone_set("Asia/Jakarta");

$pasien_id = (int)($_GET['pasien_id'] ?? 0);
if (!$pasien_id) {
    die("Parameter pasien_id diperlukan.");
}

$data = $db->query("
    SELECT r.*, 
           a.nama AS nama_anak, 
           a.kelas,
           p.status, p.nomor_antrian
    FROM riwayat_kesehatan r
    LEFT JOIN pasien p ON p.id = r.pasien_id
    LEFT JOIN anak a ON a.id_anak = p.anak_id
    WHERE r.pasien_id = $pasien_id
    LIMIT 1
")->fetch_assoc();

if (!$data) {
    die("Data pasien tidak ditemukan.");
}

function safe($v) {
    return ($v == "" || $v === null) ? "-" : nl2br(htmlspecialchars($v));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Kesehatan - Perawat</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
            color: #1a1a1a;
            line-height: 1.6;
        }
        .letter-head {
            text-align: center;
            padding: 15px 0;
            border-bottom: 3px solid #0d9488;
            margin-bottom: 25px;
        }
        .letter-head h1 {
            margin: 0;
            font-size: 22px;
            color: #0f766e;
        }
        .letter-head .sub {
            margin: 8px 0 0 0;
            font-size: 16px;
            font-weight: bold;
            color: #0d9488;
        }
        .letter-head .label {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        .content {
            border: 1px solid #0d9488;
            border-radius: 8px;
            padding: 24px;
            background: #f0fdfa;
        }
        .content p { margin: 10px 0; }
        .content .field { margin-bottom: 14px; }
        .content .field strong {
            display: block;
            color: #0f766e;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .sign-block {
            margin-top: 30px;
            text-align: right;
        }
        .sign-block .place {
            margin-bottom: 50px;
            font-size: 13px;
            color: #475569;
        }
        .sign-block .name {
            font-weight: bold;
            color: #0f766e;
            border-top: 1px solid #0d9488;
            padding-top: 8px;
            display: inline-block;
            min-width: 200px;
            text-align: center;
        }
        .badge-perawat {
            display: inline-block;
            background: #0d9488;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            margin-left: 8px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="letter-head">
    <h1>KLINIK RISALAH MEDIKA</h1>
    <p class="sub">SURAT KETERANGAN KESEHATAN</p>
    <p class="label">Diterbitkan oleh Perawat Klinik</p>
</div>

<div class="content">
    <p>Yang bertanda tangan di bawah ini, <strong>perawat klinik</strong>, menerangkan bahwa:</p>

    <div class="field">
        <strong>Nama Anak / Pasien</strong>
        <?= safe($data['nama_anak']) ?>
    </div>
    <div class="field">
        <strong>Kelas</strong>
        <?= safe($data['kelas']) ?>
    </div>
    <div class="field">
        <strong>No. Antrian</strong>
        <?= safe($data['nomor_antrian']) ?>
    </div>
    <div class="field">
        <strong>Keluhan</strong>
        <?= safe($data['keluhan']) ?>
    </div>
    <div class="field">
        <strong>Kategori / Pemeriksaan Awal</strong>
        <?= safe($data['kategori']) ?>
    </div>
    <?php if (!empty($data['suhu_demam'])): ?>
    <div class="field">
        <strong>Suhu Tubuh</strong>
        <?= htmlspecialchars($data['suhu_demam']) ?> °C
    </div>
    <?php endif; ?>
    <?php if (!empty($data['td']) || !empty($data['tinggi_cm']) || !empty($data['berat_kg'])): ?>
    <div class="field">
        <strong>Hasil Pemeriksaan (TD / Tinggi / Berat)</strong>
        <?php
        $parts = [];
        if (!empty($data['td'])) $parts[] = 'TD: ' . htmlspecialchars($data['td']);
        if (!empty($data['tinggi_cm'])) $parts[] = 'Tinggi: ' . $data['tinggi_cm'] . ' cm';
        if (!empty($data['berat_kg'])) $parts[] = 'Berat: ' . $data['berat_kg'] . ' kg';
        echo implode(' &nbsp;|&nbsp; ', $parts);
        ?>
    </div>
    <?php endif; ?>
    <div class="field">
        <strong>Catatan Perawat</strong>
        <?= safe($data['catatan_perawat']) ?>
    </div>
    <div class="field">
        <strong>Resep / Obat yang disarankan (Perawat)</strong>
        <?= safe($data['resep_perawat']) ?>
    </div>

    <p style="margin-top:20px;">Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>

    <div class="sign-block">
        <div class="place">Jakarta, <?= date("d F Y") ?></div>
        <div class="name">
            <?= htmlspecialchars($data['nama_perawat'] ?: 'Perawat Klinik') ?>
            <span class="badge-perawat">Perawat</span>
        </div>
    </div>
</div>

<p class="no-print" style="margin-top:20px; text-align:center; font-size:12px; color:#64748b;">
    Cetak halaman ini (Ctrl+P) lalu pilih "Simpan sebagai PDF" untuk menghasilkan file PDF.
</p>
<script>
window.onload = function() {
    if (window.location.search.indexOf('print=1') !== -1) window.print();
};
</script>
</body>
</html>

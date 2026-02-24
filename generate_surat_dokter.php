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
    <title>Surat Izin Sakit - Dokter</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            max-width: 210mm;
            margin: 0 auto;
            padding: 24px;
            color: #1e1b4b;
            line-height: 1.7;
        }
        .letter-head {
            text-align: center;
            padding: 20px 0;
            border-bottom: 4px double #4f46e5;
            margin-bottom: 28px;
        }
        .letter-head h1 {
            margin: 0;
            font-size: 24px;
            color: #3730a3;
            letter-spacing: 0.5px;
        }
        .letter-head .sub {
            margin: 10px 0 0 0;
            font-size: 18px;
            font-weight: bold;
            color: #4f46e5;
        }
        .letter-head .label {
            font-size: 12px;
            color: #6366f1;
            margin-top: 6px;
            font-style: italic;
        }
        .content {
            border: 2px solid #4f46e5;
            padding: 28px;
            background: #eef2ff;
        }
        .content .field { margin-bottom: 16px; }
        .content .field strong {
            display: block;
            color: #4338ca;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .saran {
            background: #c7d2fe;
            padding: 12px 16px;
            border-radius: 6px;
            margin: 16px 0;
            border-left: 4px solid #4f46e5;
        }
        .sign-block {
            margin-top: 40px;
            text-align: right;
        }
        .sign-block .place {
            margin-bottom: 60px;
            font-size: 14px;
            color: #374151;
        }
        .sign-block .name {
            font-weight: bold;
            color: #3730a3;
            border-top: 2px solid #4f46e5;
            padding-top: 10px;
            display: inline-block;
            min-width: 220px;
            text-align: center;
        }
        .badge-dokter {
            display: inline-block;
            background: #4f46e5;
            color: #fff;
            padding: 4px 14px;
            border-radius: 4px;
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
    <p class="sub">SURAT IZIN SAKIT</p>
    <p class="label">Diterbitkan oleh Dokter</p>
</div>

<div class="content">
    <p>Yang bertanda tangan di bawah ini, <strong>dokter klinik</strong>, menerangkan bahwa:</p>

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
        <strong>Diagnosa</strong>
        <?= safe($data['diagnosa']) ?>
    </div>
    <div class="field">
        <strong>Catatan Medis (Dokter)</strong>
        <?= safe($data['catatan_dokter']) ?>
    </div>
    <div class="field">
        <strong>Resep Obat</strong>
        <?= safe($data['resep_dokter']) ?>
    </div>
    <?php if (!empty($data['tindakan'])): ?>
    <div class="field">
        <strong>Tindakan</strong>
        <?= safe($data['tindakan']) ?>
    </div>
    <?php endif; ?>

    <div class="saran">
        <strong>Saran:</strong> Istirahat 1–2 hari atau sesuai kondisi pasien. Kontrol ulang bila keluhan berlanjut.
    </div>

    <p style="margin-top:24px;">Demikian surat izin sakit ini dibuat dengan sebenar-benarnya untuk dipergunakan sebagaimana mestinya.</p>

    <div class="sign-block">
        <div class="place">Jakarta, <?= date("d F Y") ?></div>
        <div class="name">
            <?= htmlspecialchars($data['nama_dokter'] ?: 'Dokter Klinik') ?>
            <span class="badge-dokter">Dokter</span>
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

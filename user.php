<?php
/* ===============================
   SET TIMEZONE & ERROR
================================ */
date_default_timezone_set('Asia/Jakarta');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'koneksi.php';
include 'header_user.php';

/* ===============================
   SET TIMEZONE MYSQL (OPSIONAL TAPI AMAN)
================================ */
$db->query("SET time_zone = '+07:00'");

/* ===============================
   AMBIL LIST ANAK
================================ */
$anak_q = $db->query("SELECT id_anak, nama FROM anak ORDER BY nama ASC");

// anak yang dipilih
$selected_anak = isset($_GET['anak_id']) ? (int)$_GET['anak_id'] : null;

/* ===============================
   PROSES PENDAFTARAN KLINIK
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['daftar'])) {

    $anak_id = (int)$_POST['anak_id'];
    $keluhan = trim($_POST['keluhan']);

    // ambil nama anak
    $q = $db->prepare("SELECT nama FROM anak WHERE id_anak = ?");
    $q->bind_param('i', $anak_id);
    $q->execute();
    $result = $q->get_result();
    $row = $result->fetch_assoc();
    $nama = $row['nama'] ?? '---';

    // hitung nomor antrian
    $res = $db->query("SELECT COALESCE(MAX(nomor_antrian), 0) AS last FROM pasien");
    $last = $res->fetch_assoc()['last'] + 1;

    // waktu daftar (WIB dari PHP)
    $waktu_daftar = date('Y-m-d H:i:s');

    // insert pasien
    $ins = $db->prepare("
        INSERT INTO pasien 
        (anak_id, nama, nomor_antrian, status, tgl_daftar)
        VALUES (?, ?, ?, 'menunggu', ?)
    ");
    $ins->bind_param('isis', $anak_id, $nama, $last, $waktu_daftar);
    $ins->execute();
    $pasien_id = $db->insert_id;
    $ins->close();

    // simpan keluhan user ke riwayat_kesehatan agar data masuk ke perawat
    $stmt_r = $db->prepare("
        INSERT INTO riwayat_kesehatan 
        (pasien_id, anak_id, keluhan, nomor_antrian, status_akhir, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'user_saja', NOW(), NOW())
    ");
    $stmt_r->bind_param('iisi', $pasien_id, $anak_id, $keluhan, $last);
    $stmt_r->execute();
    $stmt_r->close();

    header("Location: user.php?anak_id=".$anak_id."&antrian=".$last."&ok=1");
    exit;
}

/* ===============================
   TABEL ANTRIAN MENUNGGU
================================ */
$antrian_q = $db->query("
    SELECT nama, nomor_antrian, tgl_daftar 
    FROM pasien 
    WHERE status = 'menunggu'
    ORDER BY nomor_antrian ASC
");
?>

<div class="two-col">

<!-- ==========================
         PANEL KIRI
========================== -->
<div class="left panel">
    <h3>Pendaftaran Kunjungan Klinik</h3>

    <?php if (!empty($_GET['ok'])): ?>
        <p style="color:green">
            <b>Pendaftaran berhasil!</b><br>
            Nomor Antrian Anda:
            <span style="font-size:20px;color:blue">
                <?= htmlspecialchars($_GET['antrian']) ?>
            </span>
        </p>
    <?php endif; ?>

    <form method="get">
        <label>Pilih Nama Anak:</label><br>
        <select name="anak_id" onchange="this.form.submit()" required>
            <option value="">-- pilih anak --</option>
            <?php foreach($anak_q as $a): ?>
                <option value="<?= $a['id_anak'] ?>"
                    <?= $selected_anak == $a['id_anak'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a['nama']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selected_anak): ?>
    <hr>
    <form method="post">
        <input type="hidden" name="anak_id" value="<?= $selected_anak ?>">
        <label>Keluhan:</label><br>
        <textarea name="keluhan" required></textarea><br><br>
        <button name="daftar">Daftar & Ambil Antrian</button>
    </form>
    <?php endif; ?>
</div>

<!-- ==========================
         PANEL KANAN
========================== -->
<div class="right panel">
    <h3>Antrian Menunggu</h3>

    <table border="1" cellpadding="6" width="100%">
        <tr>
            <th>No</th>
            <th>Nama Anak</th>
            <th>No. Antrian</th>
            <th>Waktu Daftar (WIB)</th>
        </tr>

        <?php if ($antrian_q->num_rows > 0): ?>
            <?php foreach ($antrian_q as $row): ?>
            <tr>
                <td><?= $row['nomor_antrian'] ?></td>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><b><?= $row['nomor_antrian'] ?></b></td>
                <td><?= $row['tgl_daftar'] ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align:center">Belum ada antrian menunggu</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

</div>

<?php include 'footer_user.php'; ?>

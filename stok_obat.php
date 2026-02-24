<?php
date_default_timezone_set('Asia/Jakarta');

/* =============================
   KONEKSI DATABASE
============================= */
$servername = "localhost";
$username   = "xreiins1_clinic";
$password   = "Hakim123!";
$dbname     = "xreiins1_clinic";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

/* =============================
   TAMBAH OBAT BARU
============================= */
if (isset($_POST['tambah_obat'])) {
    $nama = $_POST['nama'];
    $satuan = $_POST['satuan'];

    $stmt = $conn->prepare("INSERT INTO obat (nama_obat, stok, satuan) VALUES (?, 0, ?)");
    $stmt->bind_param("ss", $nama, $satuan);
    $stmt->execute();

    header("Location: stok_obat.php");
    exit;
}

/* =============================
   INPUT STOK MASUK
============================= */
if (isset($_POST['tambah_stok'])) {
    $obat_id = $_POST['obat_id'];
    $jumlah = $_POST['jumlah'];
    $tanggal = date("Y-m-d");
    $waktu = date("H:i:s");

    // Tambah stok pada tabel obat
    $conn->query("UPDATE obat SET stok = stok + $jumlah WHERE id = $obat_id");

    // Catat riwayat masuk
    $stmt = $conn->prepare("INSERT INTO riwayat_masuk (obat_id, jumlah, tanggal, waktu) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $obat_id, $jumlah, $tanggal, $waktu);
    $stmt->execute();

    header("Location: stok_obat.php");
    exit;
}

/* =============================
   AMBIL DATA OBAT + RIWAYAT
============================= */

$obat = $conn->query("SELECT * FROM obat ORDER BY id DESC");

$transaksi = $conn->query("
    SELECT t.*, o.nama_obat, o.satuan, p.nama AS nama_pasien
    FROM transaksi_obat t
    JOIN obat o ON o.id = t.obat_id
    LEFT JOIN pasien p ON p.id = t.pasien_id
    ORDER BY t.id DESC
");

$riwayat_masuk = $conn->query("
    SELECT r.*, o.nama_obat, o.satuan
    FROM riwayat_masuk r
    JOIN obat o ON o.id = r.obat_id
    ORDER BY r.id DESC
");

?>
<!DOCTYPE html>
<html>
<head>
<title>Manajemen Stok Obat</title>
<style>
body { font-family: Arial; background:#f2f2f2; padding:20px; }
form, table { background:white; padding:15px; border-radius:8px; margin-bottom:20px; }
input, select { padding:8px; width:100%; margin-bottom:10px; border:1px solid #ccc; border-radius:5px; }
table { width:100%; border-collapse:collapse; }
th, td { padding:8px; border-bottom:1px solid #ddd; }
button { padding:8px 15px; background:#007bff; border:none; color:white; border-radius:5px; cursor:pointer; }
</style>
</head>
<body>

<h2>Tambah Obat Baru</h2>
<form method="POST">
    <input type="text" name="nama" placeholder="Nama Obat" required>

    <select name="satuan" required>
        <option value="butir">Butir</option>
        <option value="kapsul">Kapsul</option>
        <option value="kotak">Kotak</option>
        <option value="tablet">Tablet</option>
        <option value="botol">Botol</option>
    </select>

    <button name="tambah_obat">Tambah</button>
</form>

<!-- ==============================
     FORM TAMBAH STOK MASUK
================================= -->
<h2>Tambah Stok Masuk</h2>
<form method="POST">
    <select name="obat_id" required>
        <option value="">-- Pilih Obat --</option>
        <?php
        $ob = $conn->query("SELECT * FROM obat ORDER BY nama_obat ASC");
        while ($d = $ob->fetch_assoc()):
        ?>
            <option value="<?= $d['id'] ?>"><?= $d['nama_obat'] ?></option>
        <?php endwhile; ?>
    </select>

    <input type="number" name="jumlah" placeholder="Jumlah Masuk" required>

    <button name="tambah_stok">Simpan Stok</button>
</form>

<h2>Stok Obat Tersedia</h2>
<table>
<tr><th>ID</th><th>Nama Obat</th><th>Stok</th><th>Satuan</th></tr>
<?php while ($o = $obat->fetch_assoc()): ?>
<tr>
    <td><?= $o['id'] ?></td>
    <td><?= $o['nama_obat'] ?></td>
    <td><?= $o['stok'] ?></td>
    <td><?= $o['satuan'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<!-- ==============================
     RIWAYAT OBAT MASUK
================================= -->
<h2>Riwayat Obat Masuk</h2>
<table>
<tr>
    <th>Tanggal</th>
    <th>Waktu</th>
    <th>Nama Obat</th>
    <th>Jumlah Masuk</th>
    <th>Satuan</th>
</tr>

<?php while ($rm = $riwayat_masuk->fetch_assoc()): ?>
<tr>
    <td><?= $rm['tanggal'] ?></td>
    <td><?= $rm['waktu'] ?></td>
    <td><?= $rm['nama_obat'] ?></td>
    <td><?= $rm['jumlah'] ?></td>
    <td><?= $rm['satuan'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<h2>Riwayat Obat Keluar</h2>
<table>
<tr>
    <th>Tanggal</th>
    <th>Waktu</th>
    <th>Nama Obat</th>
    <th>Jumlah</th>
    <th>Satuan</th>
    <th>Diberikan Kepada</th>
</tr>

<?php while ($t = $transaksi->fetch_assoc()): ?>
<tr>
    <td><?= $t['tanggal'] ?></td>
    <td><?= $t['waktu'] ?></td>
    <td><?= $t['nama_obat'] ?></td>
    <td><?= $t['jumlah'] ?></td>
    <td><?= $t['satuan'] ?></td>
    <td><?= $t['nama_pasien'] ?: '-' ?></td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>

<?php
require 'koneksi.php';
include 'header_user.php';

// ====== FUNGSI STATUS WARNA ======
function warnaStatus($s){
    switch($s){
        case 'user_saja': return "<span class='status-badge status-user'>User</span>";
        case 'perawat_selesai': return "<span class='status-badge status-perawat'>Perawat</span>";
        case 'dokter_selesai': return "<span class='status-badge status-dokter'>Dokter</span>";
        case 'apoteker_selesai': return "<span class='status-badge status-apotek'>Apoteker</span>";
        default: return "<span>-</span>";
    }
}
?>

<style>
.status-badge { padding:4px 8px; font-weight:bold; border-radius:4px; }
.status-user { background:#fef3c7;color:#b45309; }
.status-perawat { background:#dbeafe;color:#1e40af; }
.status-dokter { background:#ede9fe;color:#6d28d9; }
.status-apotek { background:#dcfce7;color:#166534; }

textarea, input[type=text], input[type=number], select {
    width:100%; padding:6px; font-size:14px;
}
.panel-info {
    border:1px solid #ccc; padding:10px; background:#f9f9f9; margin-bottom:15px;
    border-radius:6px;
}
.tabContent { margin-top:10px; }
.two-col { display:flex; gap:20px; align-items:flex-start; }
.left.panel { width:38%; }
.right.panel { width:62%; }
button.tabBtn { padding:6px 10px; margin-right:6px; cursor:pointer; }
.small-muted { color:#666; font-size:13px; }
.readonly-field { background:#fff; border:1px solid #e1e1e1; padding:6px; border-radius:4px; }

.checklist-group {
    background: #f5f5f5;
    border: 1px solid #ddd;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
}
.checklist-group h5 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 14px;
}
.checklist-item {
    margin-bottom: 8px;
}
.checklist-item label {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    font-size: 14px;
}
.checklist-item input[type="radio"] {
    width: auto;
    margin-right: 8px;
    cursor: pointer;
}
</style>

<?php
// LIST PASIEN
$list = $db->query("
    SELECT p.*, a.nama AS nama_anak
    FROM pasien p
    LEFT JOIN anak a ON p.anak_id = a.id_anak
    WHERE p.status='proses_dokter'
    ORDER BY p.nomor_antrian ASC
");

$selected = null;
$riwayat = null;
$perawat_data = null;
$kategori_count = [];

if(isset($_GET['periksa'])){
    $idp = (int)$_GET['periksa'];

    $stmt = $db->prepare("
        SELECT p.*, a.nama AS nama_anak
        FROM pasien p
        LEFT JOIN anak a ON p.anak_id = a.id_anak
        WHERE p.id=? LIMIT 1
    ");
    $stmt->bind_param("i",$idp);
    $stmt->execute();
    $selected = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if($selected){

        // AMBIL DATA PERAWAT (catatan yang dibuat perawat untuk pasien ini)
        $perawat_data = $db->query("
            SELECT * FROM riwayat_kesehatan
            WHERE pasien_id=".$selected['id']." LIMIT 1
        ")->fetch_assoc();

        // Riwayat lengkap anak
        $riwayat = $db->query("
            SELECT * FROM riwayat_kesehatan
            WHERE anak_id=".$selected['anak_id']."
            ORDER BY created_at DESC
        ");

        // Grafik kategori
        $kategori_res = $db->query("
            SELECT kategori, COUNT(*) AS jumlah
            FROM riwayat_kesehatan
            WHERE anak_id=".$selected['anak_id']." AND kategori IS NOT NULL
            GROUP BY kategori
        ");
        while($k = $kategori_res->fetch_assoc()){
            $kategori_count[$k['kategori']] = $k['jumlah'];
        }
    }
}

// ===================== SIMPAN DOKTER =====================
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['simpan_dokter'])){

    $pasien_id = (int)$_POST['pasien_id'];

    $nama_dokter       = trim($_POST['nama_dokter']);
    $diagnosa          = trim($_POST['diagnosa']);
    $catatan_dokter    = trim($_POST['catatan_dokter']);
    $resep_dokter      = trim($_POST['resep_dokter']);
    $tindakan          = trim($_POST['tindakan']);
    $buat_surat        = isset($_POST['buat_surat']) ? 1 : 0;
    $kategori_baru     = trim($_POST['kategori']);

    // field baru
    $suhu              = $_POST['suhu_demam'] ?? null;
    $catatanKategori   = trim($_POST['catatan_kategori']);
    
    // field checklist
    $status_menyusui   = $_POST['status_menyusui'] ?? null;
    $status_alergi     = $_POST['status_alergi'] ?? null;
    $status_hamil      = $_POST['status_hamil'] ?? null;

    // Update
    $upd = $db->prepare("
        UPDATE riwayat_kesehatan
        SET nama_dokter=?, diagnosa=?, catatan_dokter=?, resep_dokter=?, tindakan=?,
            kategori=?, suhu_demam=?, catatan_kategori=?, 
            status_menyusui=?, status_alergi=?, status_hamil=?,
            updated_at=NOW(), status_akhir='dokter_selesai'
        WHERE pasien_id=? LIMIT 1
    ");
    if ($upd === false) {
        die("Prepare failed: " . htmlspecialchars($db->error));
    }
    $upd->bind_param("sssssssssssi",
        $nama_dokter, $diagnosa, $catatan_dokter, $resep_dokter, $tindakan,
        $kategori_baru, $suhu, $catatanKategori, 
        $status_menyusui, $status_alergi, $status_hamil,
        $pasien_id
    );
    $upd->execute();
    $upd->close();

    // update status pasien
    $db->query("UPDATE pasien SET status='proses_obat' WHERE id=$pasien_id");

    if($buat_surat){
        header("Location: generate_surat_dokter.php?pasien_id=".$pasien_id);
        exit;
    }

    header("Location: dokter.php?sukses=1");
    exit;
}
?>

<h2>Panel Dokter – Pemeriksaan Pasien</h2>

<?php if(isset($_GET['sukses'])): ?>
<p style="color:green;">Data dokter berhasil disimpan.</p>
<?php endif; ?>

<div class="two-col">

<!-- ================= LIST PASIEN ================= -->
<div class="left panel">
    <h3>Daftar Pasien</h3>

    <?php if($list->num_rows==0): ?>
        <p>Tidak ada pasien.</p>
    <?php else: ?>
        <table border="1" cellpadding="6" style="width:100%; border-collapse:collapse;">
            <tr><th>No</th><th>Nama Anak</th><th>Aksi</th></tr>
            <?php while($p=$list->fetch_assoc()): ?>
                <tr>
                    <td style="text-align:center;"><?= htmlspecialchars($p['nomor_antrian']) ?></td>
                    <td><?= htmlspecialchars($p['nama_anak']) ?></td>
                    <td><a href="dokter.php?periksa=<?= $p['id'] ?>">Periksa</a></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>

<!-- ================= DETAIL DOKTER ================= -->
<div class="right panel">

<?php if(!$selected): ?>
<p>Pilih pasien dari kiri.</p>
<?php else: ?>

<h3>Periksa: <?= htmlspecialchars($selected['nama_anak']) ?></h3>
<b>Antrian:</b> <?= htmlspecialchars($selected['nomor_antrian']) ?><br>
<hr>

<!-- TAB -->
<button class="tabBtn" onclick="openTab('laporanPerawat')">Laporan Perawat</button>
<button class="tabBtn" onclick="openTab('formDokter')">Form Dokter</button>
<button class="tabBtn" onclick="openTab('riwayatTab')">Riwayat</button>
<button class="tabBtn" onclick="openTab('grafikTab')">Grafik</button>

<!-- ================= LAPORAN PERAWAT (ditampilkan juga di form dokter) ================= -->
<div id="laporanPerawat" class="tabContent" style="display:block; margin-top:15px;">
    <h4>✔ Laporan Pemeriksaan Perawat</h4>

    <div class="panel-info">
        <b>Kategori (perawat):</b> <?= htmlspecialchars($perawat_data['kategori'] ?? '-') ?><br>
        <b>Keluhan:</b> <?= nl2br(htmlspecialchars($perawat_data['keluhan'] ?? '-')) ?><br><br>

        <b>Tekanan Darah:</b> <?= htmlspecialchars($perawat_data['td'] ?? '-') ?><br>
        <b>Tinggi:</b> <?= htmlspecialchars($perawat_data['tinggi_cm'] ?? '-') ?> cm<br>
        <b>Berat:</b> <?= htmlspecialchars($perawat_data['berat_kg'] ?? '-') ?> kg<br><br>

        <?php if(!empty($perawat_data['catatan_perawat'])): ?>
            <b>Catatan Perawat:</b><br>
            <?= nl2br(htmlspecialchars($perawat_data['catatan_perawat'])) ?><br><br>
        <?php endif; ?>

        <?php if(!empty($perawat_data['resep_perawat'])): ?>
            <b>Resep Perawat:</b><br>
            <?= nl2br(htmlspecialchars($perawat_data['resep_perawat'])) ?><br><br>
        <?php endif; ?>

        <?php if(!empty($perawat_data['suhu_demam'])): ?>
            <b>Suhu tercatat (perawat):</b> <?= htmlspecialchars($perawat_data['suhu_demam']) ?> °C<br>
        <?php endif; ?>
    </div>
</div>

<!-- ================= FORM DOKTER ================= -->
<div id="formDokter" class="tabContent" style="display:none; margin-top:15px;">

<h4>Form Pemeriksaan Dokter</h4>

<!-- TAMPILKAN DATA PERAWAT DI ATAS FORM (read-only) -->
<?php if($perawat_data): ?>
    <div class="panel-info">
        <b>Data dari Perawat (ditampilkan untuk dokter):</b><br><br>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <div>
                <small class="small-muted">Kategori (perawat)</small>
                <div class="readonly-field"><?= htmlspecialchars($perawat_data['kategori'] ?? '-') ?></div>
            </div>
            <div>
                <small class="small-muted">Keluhan</small>
                <div class="readonly-field"><?= nl2br(htmlspecialchars($perawat_data['keluhan'] ?? '-')) ?></div>
            </div>
        </div>
        <br>
        <div style="display:grid; grid-template-columns: repeat(3,1fr); gap:10px;">
            <div>
                <small class="small-muted">Tekanan Darah</small>
                <div class="readonly-field"><?= htmlspecialchars($perawat_data['td'] ?? '-') ?></div>
            </div>
            <div>
                <small class="small-muted">Tinggi (cm)</small>
                <div class="readonly-field"><?= htmlspecialchars($perawat_data['tinggi_cm'] ?? '-') ?></div>
            </div>
            <div>
                <small class="small-muted">Berat (kg)</small>
                <div class="readonly-field"><?= htmlspecialchars($perawat_data['berat_kg'] ?? '-') ?></div>
            </div>
        </div>
        <br>
        <?php if(!empty($perawat_data['catatan_perawat'])): ?>
            <small class="small-muted">Catatan Perawat</small>
            <div class="readonly-field"><?= nl2br(htmlspecialchars($perawat_data['catatan_perawat'])) ?></div><br>
        <?php endif; ?>

        <?php if(!empty($perawat_data['resep_perawat'])): ?>
            <small class="small-muted">Resep Perawat</small>
            <div class="readonly-field"><?= nl2br(htmlspecialchars($perawat_data['resep_perawat'])) ?></div><br>
        <?php endif; ?>

    </div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="pasien_id" value="<?= htmlspecialchars($selected['id']) ?>">

    Nama Dokter:<br>
    <input type="text" name="nama_dokter" required><br><br>

    <!-- CHECKLIST STATUS PASIEN -->
    <div class="checklist-group">
        <h5>📋 Status Pasien</h5>
        
        <div class="checklist-item">
            <strong>Status Menyusui:</strong><br>
            <label>
                <input type="radio" name="status_menyusui" value="menyusui" required>
                Menyusui
            </label>
            <label style="margin-left: 15px;">
                <input type="radio" name="status_menyusui" value="tidak_menyusui">
                Tidak Menyusui
            </label>
        </div>

        <div class="checklist-item">
            <strong>Status Alergi:</strong><br>
            <label>
                <input type="radio" name="status_alergi" value="alergi" required>
                Ada Alergi
            </label>
            <label style="margin-left: 15px;">
                <input type="radio" name="status_alergi" value="tidak_alergi">
                Tidak Ada Alergi
            </label>
        </div>

        <div class="checklist-item">
            <strong>Status Kehamilan:</strong><br>
            <label>
                <input type="radio" name="status_hamil" value="hamil" required>
                Hamil
            </label>
            <label style="margin-left: 15px;">
                <input type="radio" name="status_hamil" value="tidak_hamil">
                Tidak Hamil
            </label>
        </div>
    </div>

    Kategori (ubah jika perlu):<br>
    <select name="kategori" id="kategoriSelect" required>
        <option value="">-- Pilih kategori --</option>
        <?php
            // Buat daftar opsi dan tandai selected jika sama dengan kategori perawat
            $ops = ["Demam","Batuk","Flu","Diare","Luka","Infeksi","Alergi","Lainnya"];
            $katPerawat = $perawat_data['kategori'] ?? '';
            foreach($ops as $op){
                $sel = ($katPerawat !== '' && $op === $katPerawat) ? 'selected' : '';
                echo '<option value="'.htmlspecialchars($op).'" '.$sel.'>'.htmlspecialchars($op).'</option>';
            }
        ?>
    </select>
    <br><br>

    <!-- FIELD SUHU UNTUK DEMAM -->
    <div id="formSuhu" style="display:none;">
        Suhu (°C):<br>
        <input type="number" step="0.1" name="suhu_demam" id="inputSuhu">
        <div class="small-muted">Isi jika pasien demam. Jika perawat sudah mencatat suhu, dokter dapat mengubahnya.</div>
    </div>
    <br>

    Catatan untuk Kategori (opsional):<br>
    <textarea name="catatan_kategori" rows="2"><?= htmlspecialchars($perawat_data['catatan_kategori'] ?? '') ?></textarea><br><br>

    Diagnosa:<br>
    <textarea name="diagnosa" rows="2" required></textarea><br>

    Catatan Dokter:<br>
    <textarea name="catatan_dokter" rows="2"></textarea><br>

    Resep Dokter:<br>
    <textarea name="resep_dokter" rows="2"></textarea><br>

    Tindakan:<br>
    <textarea name="tindakan" rows="2"></textarea><br><br>

    <label><input type="checkbox" name="buat_surat"> Buat Surat Izin Sakit</label><br><br>

    <button name="simpan_dokter">Simpan Pemeriksaan</button>
</form>

</div>

<!-- ================= RIWAYAT ================= -->
<div id="riwayatTab" class="tabContent" style="display:none; margin-top:15px;">
<h4>Riwayat Pemeriksaan</h4>

<?php if ($riwayat && $riwayat->num_rows > 0): ?>
    <?php while($r=$riwayat->fetch_assoc()): ?>
    <div style="border-bottom:1px dashed #ccc; margin-bottom:12px; padding-bottom:8px;">
        <small><?= htmlspecialchars($r['created_at']) ?></small><br>
        <b>Status:</b> <?= warnaStatus($r['status_akhir']) ?><br>
        <b>Kategori:</b> <?= htmlspecialchars($r['kategori'] ?? '-') ?><br>
        <b>Keluhan:</b> <?= nl2br(htmlspecialchars($r['keluhan'] ?? '-')) ?><br>

        <?php if(!empty($r['nama_dokter'])): ?>
            <b>Dokter:</b> <?= htmlspecialchars($r['nama_dokter']) ?><br>
            <?php if($r['status_akhir']=='dokter_selesai'): ?>
                <a href="generate_surat_dokter.php?pasien_id=<?= $r['pasien_id'] ?>"
                   target="_blank" style="color:blue;font-weight:bold;">
                   Lihat / Generate Surat Izin Sakit (PDF)
                </a><br>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>Tidak ada riwayat sebelumnya.</p>
<?php endif; ?>
</div>


<!-- ================= GRAFIK ================= -->
<div id="grafikTab" class="tabContent" style="display:none; margin-top:15px;">
<h4>Grafik Penyakit</h4>

<?php if(empty($kategori_count)): ?>
    <p>Belum ada data.</p>
<?php else: ?>
<?php 
    $max = max($kategori_count);
    foreach($kategori_count as $kat=>$jml):
        $w = ($jml/$max)*100;
?>
    <b><?= htmlspecialchars($kat) ?> (<?= $jml ?>)</b>
    <div style="background:#ddd;height:18px;border-radius:4px;">
        <div style="background:#4CAF50;height:18px;width:<?= $w ?>%;border-radius:4px;"></div>
    </div><br>
<?php endforeach; ?>
<?php endif; ?>
</div>


<?php endif; // end selected ?>

</div>
</div>

<script>
function openTab(tab){
    document.querySelectorAll('.tabContent').forEach(el=>el.style.display='none');
    document.getElementById(tab).style.display='block';
}

// === POPUP & FIELD SUHU ===
const kategoriSelect = document.getElementById("kategoriSelect");
const formSuhu = document.getElementById("formSuhu");
const inputSuhu = document.getElementById("inputSuhu");

// Jika halaman dibuka dan perawat sudah mengisi kategori "Demam", tampilkan field suhu dan isi jika ada suhu perawat
document.addEventListener("DOMContentLoaded", function(){
    const initVal = kategoriSelect.value;
    if(initVal === "Demam"){
        formSuhu.style.display = "block";
        if(inputSuhu) inputSuhu.setAttribute("required","required");
        // jika perawat punya suhu_tercatat, masukkan ke inputSuhu (server-side sudah menempelkan nilai jika ada)
        <?php if(!empty($perawat_data['suhu_demam'])): ?>
            if(document.getElementById('inputSuhu')) document.getElementById('inputSuhu').value = "<?= htmlspecialchars($perawat_data['suhu_demam']) ?>";
        <?php endif; ?>
    }
});

kategoriSelect.addEventListener("change", function () {
    let val = this.value;

    // === tampilkan form suhu jika demam ===
    if (val === "Demam") {
        formSuhu.style.display = "block";
        inputSuhu.setAttribute("required", "required");
    } else {
        formSuhu.style.display = "none";
        inputSuhu.removeAttribute("required");
        inputSuhu.value = "";
    }

    // === popup catatan ===
    let pesan = "";

    switch(val){
        case "Demam":
            pesan = "Catatan Demam:\n• Cek suhu tubuh.\n• Tanyakan riwayat panas 24 jam.\n• Periksa obat sebelumnya.";
            break;
        case "Batuk":
            pesan = "Catatan Batuk:\n• Durasi batuk.\n• Apakah berdahak.\n• Ada sesak atau tidak.";
            break;
        case "Flu":
            pesan = "Catatan Flu:\n• Hidung meler, bersin, sakit kepala.\n• Aktivitas sebelum sakit.";
            break;
        case "Diare":
            pesan = "Catatan Diare:\n• Frekuensi BAB.\n• Makanan terakhir.\n• Tanda dehidrasi.";
            break;
        case "Luka":
            pesan = "Catatan Luka:\n• Kebersihan luka.\n• Ada nanah atau tidak.";
            break;
        case "Infeksi":
            pesan = "Catatan Infeksi:\n• Lokasi infeksi.\n• Lama keluhan.\n• Riwayat alergi.";
            break;
        case "Alergi":
            pesan = "Catatan Alergi:\n• Pemicu alergi.\n• Pernah terjadi sebelumnya atau tidak.";
            break;
        case "Lainnya":
            pesan = "Tambahkan catatan khusus pada kolom catatan kategori.";
            break;
    }

    if (pesan !== "") alert(pesan);
});
</script>

<?php include 'footer_user.php'; ?>
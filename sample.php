<?php
// install_sample_user.php - jalankan sekali untuk buat akun contoh
require 'koneksi.php';

$accounts = [
    ['username'=>'admin','password'=>'admin123','nama'=>'Admin Klinik','role'=>'admin'],
    ['username'=>'perawat','password'=>'perawat123','nama'=>'Perawat Klinik','role'=>'perawat'],
    ['username'=>'dokter','password'=>'dokter123','nama'=>'Dokter Klinik','role'=>'dokter'],
];

$stmt = $db->prepare("INSERT IGNORE INTO users (username, password_hash, nama_lengkap, role) VALUES (?, ?, ?, ?)");
foreach($accounts as $a){
    $pw = password_hash($a['password'], PASSWORD_DEFAULT);
    $stmt->bind_param('ssss', $a['username'], $pw, $a['nama'], $a['role']);
    $stmt->execute();
}
echo "Sample users created. Hapus file ini setelahnya.";

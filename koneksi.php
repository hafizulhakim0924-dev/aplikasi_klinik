<?php
// Cek apakah session sudah dimulai atau belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Koneksi Database
$host = 'localhost';
$user = 'xreiins1_clinic';
$pass = 'Hakim123!';
$dbname = 'xreiins1_clinic';

$db = new mysqli($host, $user, $pass, $dbname);

// Cek koneksi
if ($db->connect_error) {
    die("Koneksi gagal: " . $db->connect_error);
}

// Set charset
$db->set_charset("utf8mb4");

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
?>
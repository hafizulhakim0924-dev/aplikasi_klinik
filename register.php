<?php
require 'koneksi.php';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $nama = trim($_POST['nama']);
    $role = $_POST['role'];

    if(empty($username) || empty($password) || empty($role)){
        $err = "Lengkapi semua field.";
    } else {
        $pw = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, nama_lengkap, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $username, $pw, $nama, $role);
        if($stmt->execute()){
            header("Location: login.php?registered=1");
            exit;
        } else {
            $err = "Gagal menyimpan: " . $db->error;
        }
    }
}
?>
<!doctype html>
<title>Register</title>
<h2>Register</h2>
<?php if(!empty($err)) echo "<p style='color:red;'>$err</p>"; ?>
<form method="post">
    Username: <input name="username"><br>
    Nama Lengkap: <input name="nama"><br>
    Password: <input type="password" name="password"><br>
    Role:
    <select name="role">
        <option value="perawat">perawat</option>
        <option value="dokter">dokter</option>
        <option value="apoteker">apoteker</option>
        <option value="admin">admin</option>
    </select><br>
    <button>Register</button>
</form>

<?php
require 'koneksi.php';
if(is_logged_in()){
    // redirect sesuai role
    $r = $_SESSION['role'];
    if($r==='admin') header('Location: admin.php');
    if($r==='perawat') header('Location: perawat.php');
    if($r==='dokter') header('Location: dokter.php');
    if($r==='apoteker') header('Location: apoteker.php');
    header('Location: user.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT id, username, password_hash, role, nama_lengkap FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
        if(password_verify($password, $row['password_hash'])){
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
            // redirect sesuai role
            if($row['role'] === 'admin') header('Location: admin.php');
            if($row['role'] === 'perawat') header('Location: perawat.php');
            if($row['role'] === 'dokter') header('Location: dokter.php');
            if($row['role'] === 'apoteker') header('Location: apoteker.php');
            header('Location: user.php');
            exit;
        } else {
            $err = "Login gagal: username/password salah.";
        }
    } else {
        $err = "Login gagal: username/password salah.";
    }
}
?>
<!doctype html>
<title>Login</title>
<h2>Login</h2>
<?php if(!empty($_GET['registered'])) echo "<p style='color:green;'>Akun dibuat. Silakan login.</p>"; ?>
<?php if(!empty($err)) echo "<p style='color:red;'>$err</p>"; ?>
<form method="post">
    Username: <input name="username" required><br>
    Password: <input name="password" type="password" required><br>
    <button>Login</button>
</form>

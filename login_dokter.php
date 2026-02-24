<?php
session_start();

// Hardcoded credentials - 3 dokter
$dokter_list = [
    'dokter1' => [
        'password' => 'pass123',
        'nama' => 'Dr. A, Sp.A',
        'nip' => '198501012010011001'
    ],
    'dokter2' => [
        'password' => 'pass456',
        'nama' => 'Dr. B, Sp.A',
        'nip' => '199002152015012002'
    ],
    'dokter3' => [
        'password' => 'pass789',
        'nama' => 'Dr. C, Sp.A',
        'nip' => '198808202012011003'
    ]
];

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if(isset($dokter_list[$username]) && $dokter_list[$username]['password'] === $password){
        $_SESSION['dokter_logged_in'] = true;
        $_SESSION['dokter_username'] = $username;
        $_SESSION['dokter_nama'] = $dokter_list[$username]['nama'];
        $_SESSION['dokter_nip'] = $dokter_list[$username]['nip'];
        
        header("Location: dokter.php");
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Dokter - Posyandu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.95;
            font-weight: 300;
        }
        
        .login-icon {
            width: 70px;
            height: 70px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 35px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c33;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .info-box {
            background: #f7fafc;
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
        }
        
        .info-box h4 {
            color: #2d3748;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .credential-item {
            background: white;
            padding: 10px 12px;
            border-radius: 6px;
            margin-bottom: 8px;
            font-size: 13px;
            color: #4a5568;
            border-left: 3px solid #667eea;
        }
        
        .credential-item:last-child {
            margin-bottom: 0;
        }
        
        .credential-item strong {
            color: #2d3748;
            font-weight: 600;
        }
        
        @media (max-width: 480px) {
            .login-container {
                border-radius: 15px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">🩺</div>
            <h1>Portal Dokter</h1>
            <p>Sistem Informasi Posyandu</p>
        </div>
        
        <div class="login-body">
            <?php if($error): ?>
                <div class="error-message">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           placeholder="Masukkan username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Masukkan password" required>
                </div>
                
                <button type="submit" class="btn-login">
                    Masuk ke Sistem
                </button>
            </form>
            
            <div class="info-box">
                <h4>Demo Credentials</h4>
                <div class="credential-item">
                    <strong>dokter1</strong> / pass123
                </div>
                <div class="credential-item">
                    <strong>dokter2</strong> / pass456
                </div>
                <div class="credential-item">
                    <strong>dokter3</strong> / pass789
                </div>
            </div>
        </div>
    </div>
</body>
</html>
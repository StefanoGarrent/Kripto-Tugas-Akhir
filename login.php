<?php
session_start();
// Jika pengguna sudah login, alihkan ke dashboard
if (isset($_SESSION['email'])) {
    header("location:tampilan.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Komunikasi Aman</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa; /* Latar belakang abu-abu muda */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            width: 350px; /* Sedikit diperlebar */
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50; /* Warna gelap */
        }
        input {
            width: 100%;
            padding: 12px; /* Dibuat lebih besar */
            margin-bottom: 15px; /* Jarak antar input */
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box; 
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #3498db; /* Warna biru primer baru */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #2980b9; /* Biru lebih gelap */
        }
        .message {
            color: #e74c3c; /* Merah */
            font-size: 14px;
            text-align: center;
            padding: 10px;
            background-color: #fbeae8;
            border: 1px solid #e74c3c;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .message.success {
            color: #27ae60; /* Hijau */
            background-color: #eaf7ec;
            border: 1px solid #27ae60;
        }
        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
        .signup-link a {
            color: #3498db;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Login Akun</h2>

    <?php
    if (isset($_GET['pesan'])) {
        $pesan = $_GET['pesan'];
        $kelas = 'message'; // Kelas default untuk error
        $teks = '';

        if ($pesan == "gagal" || $pesan == "password_salah" || $pesan == "email_tidak_ditemukan") {
            $teks = "Login gagal! Email atau password salah.";
        } else if ($pesan == "logout") {
            $teks = "Anda telah berhasil logout.";
            $kelas = 'message success'; // Kelas untuk sukses
        } else if ($pesan == "belum_login") {
            $teks = "Anda harus login untuk mengakses halaman.";
        } else if ($pesan == "signup_sukses") {
            $teks = "Akun berhasil dibuat! Silakan login.";
            $kelas = 'message success';
        }
        
        if ($teks) {
            echo '<div class="' . $kelas . '">' . $teks . '</div>';
        }
    }
    ?>

    <form method="POST" action="cek_login.php">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" placeholder="Masukkan Email" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder="Masukkan Password" required>
        
        <button type="submit">Login</button>
    </form>
    
    <div class="signup-link">
        <p>Belum punya akun? <a href="signin.php">Daftar di sini</a></p>
    </div>
</div>

</body>
</html>
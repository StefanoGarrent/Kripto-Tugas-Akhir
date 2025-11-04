<?php
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['email'])) {
    header("location:login.php?pesan=belum_login");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Komunikasi Aman</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa; /* Latar belakang area konten */
        }

        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #2c3e50; /* Warna sidebar gelap */
            padding-top: 20px;
            color: white;
        }

        .sidebar h3 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.5rem;
        }

        .sidebar-nav {
            list-style: none;
            padding-left: 0;
        }

        .sidebar-nav li {
            padding: 10px 20px;
        }

        .sidebar-nav a {
            text-decoration: none;
            color: #ecf0f1; /* Warna link */
            font-size: 1.1rem;
            display: block;
            transition: background 0.3s;
            border-radius: 5px;
        }

        .sidebar-nav a:hover {
            color: #ffffff;
            background-color: #34495e; /* Warna hover */
        }

        .sidebar-nav a.active {
            background-color: #3498db; /* Warna link aktif */
            color: #ffffff;
        }
        
        .sidebar-logout {
            position: absolute;
            bottom: 20px;
            width: 100%;
            text-align: center;
        }
        
        .sidebar-logout a {
            color: #ecf0f1;
        }
        
        .sidebar-logout a:hover {
            color: #dc3545; /* Warna hover logout */
        }

        .content {
            margin-left: 250px; /* Lebar sidebar */
            padding: 30px;
        }
        
        .header {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #2c3e50;
        }
        
        .header p {
            font-size: 1.2rem;
            color: #6c757d;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .welcome-card h2 {
            margin-bottom: 10px;
        }

    </style>
</head>
<body>

    <div class="sidebar">
        <h3>KriptoSistem</h3>
        <ul class="sidebar-nav">
            <li><a href="tampilan.php" class="active">Dashboard</a></li>
            <li><a href="enkripsi_dekripsi.php">Enkripsi Teks</a></li>
            <li><a href="stegano.php">Steganografi</a></li>
            <li><a href="enkripsi_file.php">Enkripsi File</a></li>
            <li><a href="database_enkripsi.php">Pesan Rahasia (DES)</a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h1>
            <p>Sistem Komunikasi Aman untuk melindungi data Anda.</p>
        </div>
        
        <div class="welcome-card">
            <h2>Mulai Bekerja</h2>
            <p>Pilih salah satu alat keamanan dari menu di sebelah kiri untuk memulai. Jaga kerahasiaan data Anda dengan alat enkripsi dan steganografi kami.</p>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php
include "koneksi.php"; // Menghubungkan ke database

// Ambil data dari form
$nama = $_POST['nama'];
$email = $_POST['email'];
$password = $_POST['password'];


// Validasi input
if (empty($nama) || empty($email) || empty($password)) {
    // Arahkan kembali ke halaman signin (saya asumsikan nama file form-nya signin.php)
    header("location:signin.php?pesan=gagal_input");
    exit();
}

// Hash password sebelum disimpan MENGGUNAKAN MD5
$hashed_password = md5($password);

// Menggunakan prepared statement untuk mencegah SQL Injection
$stmt = $konek->prepare("INSERT INTO login (nama, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $nama, $email, $hashed_password);
$result = $stmt->execute();

if ($result) {
    // Proses input berhasil, arahkan ke halaman login
    header("location:login.php?pesan=signup_sukses");
} else {
    // Tampilkan pesan error
    echo "Gagal: " . $stmt->error;
}

$stmt->close();
$konek->close();
?>
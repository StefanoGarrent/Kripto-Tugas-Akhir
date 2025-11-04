<?php
session_start(); // Memulai session
include "koneksi.php"; // Menghubungkan ke database

// Ambil data dari form login
$email = $_POST['email'];
$password = $_POST['password'];

// Validasi input
if (empty($email) || empty($password)) {
    header("location:login.php?pesan=gagal");
    exit();
}

// 1. Hash password yang diinput pengguna menggunakan MD5
$password_md5 = md5($password);

// 2. Menggunakan prepared statement
// PERUBAHAN: Ambil juga 'id_login'
$stmt = $konek->prepare("SELECT id_login, nama, password FROM login WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // 3. Ambil hash password yang tersimpan di database
    // PERUBAHAN: Bind 'id_login'
    $stmt->bind_result($id_login, $nama, $hashed_password_db);
    $stmt->fetch();

    // 4. Bandingkan hash MD5
    if ($password_md5 === $hashed_password_db) {
        // Simpan nama, email, dan ID ke session
        $_SESSION['email'] = $email;
        $_SESSION['nama'] = $nama; 
        $_SESSION['id_login'] = $id_login; // <-- TAMBAHAN PENTING
        
        header("location:tampilan.php");
    } else {
        // Password salah
        header("location:login.php?pesan=password_salah");
    }
} else {
    // Email tidak ditemukan
    header("location:login.php?pesan=email_tidak_ditemukan");
}
$stmt->close();
$konek->close();
?>
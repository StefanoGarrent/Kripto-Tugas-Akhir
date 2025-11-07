<?php
session_start();
include "koneksi.php";

$email = $_POST['email'];
$password = $_POST['password'];

if (empty($email) || empty($password)) {
    header("location:index.php?pesan=gagal");
    exit();
}

$password_md5 = md5($password);

$stmt = $konek->prepare("SELECT id_login, nama, password FROM login WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id_login, $nama, $hashed_password_db);
    $stmt->fetch();

    if ($password_md5 === $hashed_password_db) {
        $_SESSION['email'] = $email;
        $_SESSION['nama'] = $nama;
        $_SESSION['id_login'] = $id_login;
        
        header("location:tampilan.php");
    } else {
        header("location:index.php?pesan=password_salah");
    }
} else {
    header("location:index.php?pesan=email_tidak_ditemukan");
}

$stmt->close();
$konek->close();
?>
<?php
include "koneksi.php";

$nama = $_POST['nama'];
$email = $_POST['email'];
$password = $_POST['password'];

if (empty($nama) || empty($email) || empty($password)) {
	header('Location:signin.php?pesan=gagal_input');
	exit();
}

$hashed_password = md5($password);

$stmt = $konek->prepare("INSERT INTO login (nama, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $nama, $email, $hashed_password);
$result = $stmt->execute();

if ($result) {
	header("location:index.php?pesan=signup_sukses");
} else {
	echo 'Gagal: ' . $stmt->error;
}

$stmt->close();
$konek->close();
?>
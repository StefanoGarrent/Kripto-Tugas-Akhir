<?php
session_start(); //memulai session
session_destroy(); // menghapus semua session
header("location:index.php?pesan=logout"); // DIUBAH
?>
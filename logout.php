<?php
session_start(); //memulai session
session_destroy(); // menghapus semua session
header("location:login.php?pesan=logout");
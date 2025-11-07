<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['email']) || !isset($_SESSION['id_login'])) {
    header("location:index.php?pesan=belum_login");
    exit(); 
}

$cipher_algo_aes = 'aes-256-cbc';
$id_user_saat_ini = $_SESSION['id_login'];

$error_simpan = "";
$sukses_simpan = "";
$error_buka = "";
$hasil_dekripsi = "";

function scytaleEncrypt($plaintext, $key) {
    if ($key <= 0) return $plaintext;
    $ciphertext = '';
    $len = strlen($plaintext);
    for ($col = 0; $col < $key; $col++) {
        for ($i = $col; $i < $len; $i += $key) {
            $ciphertext .= $plaintext[$i];
        }
    }
    return $ciphertext;
}

function buatKunciAesDariPassword($password) {
    $hash = hash('sha512', $password, true);
    $kunci_aes = substr($hash, 0, 32); 
    $iv_aes = substr($hash, 32, 16);    
    return ['key' => $kunci_aes, 'iv' => $iv_aes];
}

function enkripsiAES($plaintext, $password) {
    global $cipher_algo_aes;
    $kunci = buatKunciAesDariPassword($password);
    $ciphertext = openssl_encrypt($plaintext, $cipher_algo_aes, $kunci['key'], 0, $kunci['iv']);
    return $ciphertext;
}

function scytaleDecrypt($ciphertext, $key) {
    if ($key <= 0) return $ciphertext;
    $len = strlen($ciphertext);
    $cols = (int)ceil($len / $key);
    $remainder = $len % $key;
    $plaintext = '';
    $char_index = 0;
    $grid = array_fill(0, $key, array_fill(0, $cols, null));
    for ($col = 0; $col < $key; $col++) {
        $len_baris_ini = ($remainder > 0 && $col >= $remainder) ? $cols - 1 : $cols;
        for ($baris = 0; $baris < $len_baris_ini; $baris++) {
            if ($char_index < $len) {
                $grid[$col][$baris] = $ciphertext[$char_index++];
            }
        }
    }
    for ($baris = 0; $baris < $cols; $baris++) {
        for ($col = 0; $col < $key; $col++) {
            if (isset($grid[$col][$baris])) {
                $plaintext .= $grid[$col][$baris];
            }
        }
    }
    return $plaintext;
}

function dekripsiAES($base64_ciphertext, $password) {
    global $cipher_algo_aes;
    $kunci = buatKunciAesDariPassword($password);
    $plaintext = openssl_decrypt($base64_ciphertext, $cipher_algo_aes, $kunci['key'], 0, $kunci['iv']);
    return $plaintext; 
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['aksi_simpan'])) {
    $pesan_plaintext = $_POST['pesan_baru'];
    $password_aes    = $_POST['password_aes'];
    $kunci_scytale   = $_POST['kunci_scytale'];
    
    if (empty($pesan_plaintext) || empty($password_aes) || empty($kunci_scytale)) {
        $error_simpan = "Pesan, Password AES, dan Kunci Scytale tidak boleh kosong.";
    } elseif (!ctype_digit($kunci_scytale) || (int)$kunci_scytale <= 0) {
        $error_simpan = "Kunci Scytale harus berupa angka positif (misal: 5).";
    } else {
        $hasil_scytale = scytaleEncrypt($pesan_plaintext, (int)$kunci_scytale);
        $pesan_terenkripsi = enkripsiAES($hasil_scytale, $password_aes);
        
        if ($pesan_terenkripsi === false) {
             $error_simpan = "Enkripsi AES Gagal. Fungsi OpenSSL(AES) mungkin bermasalah.";
        } else {
            $stmt = $konek->prepare("INSERT INTO pesan_rahasia (id_login, isi_pesan_terenkripsi) VALUES (?, ?)");
            $stmt->bind_param("is", $id_user_saat_ini, $pesan_terenkripsi);
            
            if ($stmt->execute()) {
                header("Location: database_enkripsi.php?sukses=disimpan");
                exit();
            } else {
                $error_simpan = "Gagal menyimpan ke database.";
            }
            $stmt->close();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['aksi_buka'])) {
    $pesan_terenkripsi = $_POST['pesan_enkrip'];
    $password_aes      = $_POST['password_aes_buka'];
    $kunci_scytale     = $_POST['kunci_scytale_buka'];
    
    if (empty($pesan_terenkripsi) || empty($password_aes) || empty($kunci_scytale)) {
        $error_buka = "Teks terenkripsi, Password AES, dan Kunci Scytale tidak boleh kosong.";
    } elseif (!ctype_digit($kunci_scytale) || (int)$kunci_scytale <= 0) {
        $error_buka = "Kunci Scytale harus berupa angka positif.";
    } else {
        $stmt_cek = $konek->prepare("SELECT id_login FROM pesan_rahasia WHERE isi_pesan_terenkripsi = ?");
        $stmt_cek->bind_param("s", $pesan_terenkripsi);
        $stmt_cek->execute();
        $result_cek = $stmt_cek->get_result();
        
        $is_owner = false;
        if ($result_cek->num_rows > 0) {
            while ($row_cek = $result_cek->fetch_assoc()) {
                if ($row_cek['id_login'] == $id_user_saat_ini) {
                    $is_owner = true;
                    break; 
                }
            }
        }
        $stmt_cek->close();
        
        if ($is_owner) {
            $hasil_aes = dekripsiAES($pesan_terenkripsi, $password_aes);
            
            if ($hasil_aes === false) {
                $error_buka = "Dekripsi AES Gagal! Pastikan Password AES Anda benar.";
            } else {
                $hasil_dekripsi = scytaleDecrypt($hasil_aes, (int)$kunci_scytale);
            }
        } else {
            $error_buka = "Dekripsi Gagal! Anda bukan pemilik pesan ini, atau pesan tidak ditemukan.";
        }
    }
}

if (isset($_GET['hapus'])) {
    $id_pesan_hapus = (int)$_GET['hapus'];
    
    $stmt_hapus = $konek->prepare("DELETE FROM pesan_rahasia WHERE id_pesan = ? AND id_login = ?");
    $stmt_hapus->bind_param("ii", $id_pesan_hapus, $id_user_saat_ini);
    $stmt_hapus->execute();
    
    header("Location: database_enkripsi.php?sukses=dihapus");
    exit();
}

if (isset($_GET['sukses'])) {
    if ($_GET['sukses'] == 'dihapus') {
        $sukses_simpan = "Pesan berhasil dihapus.";
    }
    if ($_GET['sukses'] == 'disimpan') {
        $sukses_simpan = "Pesan rahasia berhasil disimpan terenkripsi!";
    }
}

$pesan_list = [];
$stmt_select = $konek->prepare("SELECT id_pesan, isi_pesan_terenkripsi, dibuat_pada FROM pesan_rahasia WHERE id_login = ? ORDER BY dibuat_pada DESC");
$stmt_select->bind_param("i", $id_user_saat_ini);
$stmt_select->execute();
$result_select = $stmt_select->get_result();

while ($row = $result_select->fetch_assoc()) {
    $pesan_list[] = $row;
}
$stmt_select->close();
$konek->close(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enkripsi Database (Scytale + AES) - KriptoSistem</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7fa; }
        .sidebar {
            height: 100vh; width: 250px; position: fixed; top: 0; left: 0;
            background-color: #2c3e50; padding-top: 20px; color: white;
        }
        .sidebar h3 { text-align: center; margin-bottom: 30px; font-size: 1.5rem; }
        .sidebar-nav { list-style: none; padding-left: 0; }
        .sidebar-nav li { padding: 10px 20px; }
        .sidebar-nav a { text-decoration: none; color: #ecf0f1; font-size: 1.1rem; display: block;
            transition: background 0.3s; border-radius: 5px; }
        .sidebar-nav a:hover { color: #ffffff; background-color: #34495e; }
        .sidebar-nav a.active { background-color: #3498db; color: #ffffff; }
        .sidebar-logout { position: absolute; bottom: 20px; width: 100%; text-align: center; }
        .sidebar-logout a { color: #ecf0f1; }
        .sidebar-logout a:hover { color: #dc3545; }
        .content { margin-left: 250px; padding: 30px; }
        .header { border-bottom: 1px solid #dee2e6; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #2c3e50; }
        .form-card, .list-card {
            background-color: #ffffff; padding: 30px; border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .btn-primary { background-color: #3498db; border-color: #3498db; }
        .btn-primary:hover { background-color: #2980b9; border-color: #2980b9; }
        .note {
            background: #f8f9fa; border: 1px solid #e9ecef;
            padding: 15px; border-radius: 5px; margin-bottom: 15px;
            word-wrap: break-word;
        }
        .note-meta { font-size: 0.85rem; color: #6c757d; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h3>KriptoSistem</h3>
        <ul class="sidebar-nav">
            <li><a href="tampilan.php">Dashboard</a></li>
            <li><a href="enkripsi_dekripsi.php">Enkripsi Teks</a></li>
            <li><a href="stegano.php">Steganografi</a></li>
            <li><a href="enkripsi_file.php">Enkripsi File</a></li>
            <li><a href="database_enkripsi.php" class="active">Pesan Rahasia</a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h1>Enkripsi Database (Scytale + AES-256)</h1>
            <p class="text-muted">Simpan dan buka catatan rahasia Anda menggunakan enkripsi lapis Scytale dan AES.</p>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-card">
                    <h5>Simpan Pesan Rahasia (Enkripsi)</h5>
                    <hr>
                    
                    <?php if (!empty($error_simpan)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_simpan) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($sukses_simpan)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($sukses_simpan) ?></div>
                    <?php endif; ?>

                    <form method="post" action="database_enkripsi.php">
                        <div class="mb-3">
                            <label for="pesan_baru" class="form-label">Pesan Anda:</label>
                            <textarea class="form-control" id="pesan_baru" name="pesan_baru" rows="3" placeholder="Tulis catatan rahasia..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="kunci_scytale" class="form-label">Kunci Scytale (Angka):</label>
                            <input type="number" class="form-control" id="kunci_scytale" name="kunci_scytale" placeholder="Misal: 5" required>
                        </div>
                        <div class="mb-3">
                            <label for="password_aes" class="form-label">Password AES:</label>
                            <input type="password" class="form-control" id="password_aes" name="password_aes" required>
                        </div>
                        <button type="submit" name="aksi_simpan" class="btn btn-primary">Simpan Terenkripsi</button>
                    </form>
                </div>
                
                <div class="form-card">
                    <h5>Buka Pesan Rahasia (Dekripsi)</h5>
                    <hr>
                    
                    <?php if (!empty($error_buka)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_buka) ?></div>
                    <?php endif; ?>

                    <form method="post" action="database_enkripsi.php#hasil-dekripsi">
                        <div class="mb-3">
                            <label for="pesan_enkrip" class="form-label">Teks Terenkripsi:</label>
                            <textarea class="form-control" id="pesan_enkrip" name="pesan_enkrip" rows="3" placeholder="Paste teks terenkripsi dari daftar di samping..."></textarea>
                        </div>
                         <div class="mb-3">
                            <label for="kunci_scytale_buka" class="form-label">Kunci Scytale (Angka):</label>
                            <input type="number" class="form-control" id="kunci_scytale_buka" name="kunci_scytale_buka" placeholder="Misal: 5" required>
                        </div>
                        <div class="mb-3">
                            <label for="password_aes_buka" class="form-label">Password AES:</label>
                            <input type="password" class="form-control" id="password_aes_buka" name="password_aes_buka" required>
                        </div>
                        <button type="submit" name="aksi_buka" class="btn btn-primary">Buka Pesan</button>
                    </form>
                    
                    <?php if (!empty($hasil_dekripsi)): ?>
                        <div class="alert alert-success mt-3" id="hasil-dekripsi">
                            <strong>Isi Pesan:</strong>
                            <p class="mb-0"><?= htmlspecialchars($hasil_dekripsi) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="list-card">
                    <h5>Daftar Pesan Tersimpan (Milik Anda)</h5>
                    <hr>
                    <?php if (empty($pesan_list)): ?>
                        <p class="text-center text-muted">Belum ada pesan tersimpan.</p>
                    <?php else: ?>
                        <?php foreach ($pesan_list as $pesan): ?>
                            <div class="note">
                                <p><strong><code><?= htmlspecialchars($pesan['isi_pesan_terenkripsi']) ?></code></strong></p>
                                <div class="note-meta">
                                    <span>Disimpan: <?= htmlspecialchars($pesan['dibuat_pada']) ?></span>
                                    <a href="?hapus=<?= $pesan['id_pesan'] ?>" 
                                       class="text-danger float-right" 
                                       onclick="return confirm('Anda yakin ingin menghapus pesan ini?')">
                                        Hapus
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
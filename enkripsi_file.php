<?php
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['email'])) {
    header("location:index.php?pesan=belum_login"); // DIUBAH
    exit(); 
}

/**
 * Fungsi untuk mengenkripsi file menggunakan AES-256-CBC.
 * Kunci AES 32-byte akan dibuat dari password menggunakan SHA-256.
 * IV 16-byte akan dibuat acak dan disimpan di awal file output.
 */
function encryptFile($filePath, $outputPath, $password) {
    if (!file_exists($filePath)) {
        return "Error: File tidak ditemukan.";
    }

    // 1. Buat Kunci AES 32-byte dari password apa pun
    //    'true' = output raw binary (32 byte)
    $key_aes = hash('sha256', $password, true);
    
    // Tentukan panjang IV (selalu 16 byte for AES-CBC)
    $iv_length = 16;
    
    // 2. Buat IV acak yang aman
    $iv = openssl_random_pseudo_bytes($iv_length);
    if ($iv === false) {
        // Ini seharusnya berfungsi, karena berbeda dari key generation
        return "Error: Gagal membuat IV acak (openssl_random_pseudo_bytes).";
    }

    // Baca konten file
    $fileContents = file_get_contents($filePath);

    // 3. Enkripsi data (gunakan OPENSSL_RAW_DATA agar hasilnya binary)
    $encryptedData = openssl_encrypt($fileContents, 'aes-256-cbc', $key_aes, OPENSSL_RAW_DATA, $iv);
    if ($encryptedData === false) {
        return "Error: Gagal mengenkripsi file (openssl_encrypt).";
    }

    // 4. Gabungkan IV (16 byte) + data terenkripsi. Simpan ke file.
    //    Kita simpan data mentah (raw) agar efisien.
    file_put_contents($outputPath, $iv . $encryptedData);
    
    return "File berhasil dienkripsi!";
}

/**
 * Fungsi untuk mendekripsi file.
 * Akan membaca IV dari file dan menggunakan password untuk membuat ulang kunci.
 */
function decryptFile($filePath, $outputPath, $password) {
    if (!file_exists($filePath)) {
        return "Error: File tidak ditemukan.";
    }

    // 1. Buat Kunci AES 32-byte yang SAMA dari password
    $key_aes = hash('sha256', $password, true);

    // 2. Baca seluruh isi file
    $fileContents = file_get_contents($filePath);

    // 3. Tentukan panjang IV
    $iv_length = 16;
    
    // 4. Ekstrak IV (16 byte pertama)
    $iv = substr($fileContents, 0, $iv_length);
    
    // 5. Ekstrak data terenkripsi (sisanya)
    $encryptedData = substr($fileContents, $iv_length);
    
    if (strlen($iv) !== $iv_length) {
        return "Error: File korup atau terlalu kecil (gagal ekstrak IV).";
    }

    // 6. Dekripsi data
    $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', $key_aes, OPENSSL_RAW_DATA, $iv);

    if ($decryptedData === false) {
        return "Error: Gagal mendekripsi file. Pastikan Password / Kunci Anda benar.";
    }

    // 7. Simpan file
    file_put_contents($outputPath, $decryptedData);
    return "File berhasil didekripsi!";
}


// --- Form Handling Logic ---

$result = "";
$outputPath = ""; 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_FILES["fileToProcess"]) && $_FILES["fileToProcess"]["error"] == 0) {
        $filePath = $_FILES["fileToProcess"]["tmp_name"];
        $outputPath = ($_POST["action"] === "encrypt" ? "encrypted_" : "decrypted_") . $_FILES["fileToProcess"]["name"];
        
        // 'key' sekarang diperlakukan sebagai 'password'
        $key_password = $_POST["key"]; 
        $action = $_POST["action"];

        if (empty($key_password)) {
            $result = "Error: Password / Kunci harus diisi.";
        } else {
            // Panggil fungsi baru
            if ($action === "encrypt") {
                $result = encryptFile($filePath, $outputPath, $key_password);
            } elseif ($action === "decrypt") {
                $result = decryptFile($filePath, $outputPath, $key_password);
            }
        }
    } else {
         $result = "Error: Gagal mengunggah file.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enkripsi File - Sistem Komunikasi Aman</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
        }
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #2c3e50;
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
            color: #ecf0f1;
            font-size: 1.1rem;
            display: block;
            transition: background 0.3s;
            border-radius: 5px;
        }
        .sidebar-nav a:hover {
            color: #ffffff;
            background-color: #34495e;
        }
        .sidebar-nav a.active {
            background-color: #3498db;
            color: #ffffff;
        }
        .sidebar-logout {
            position: absolute;
            bottom: 20px;
            width: 100%;
            text-align: center;
        }
        .sidebar-logout a { color: #ecf0f1; }
        .sidebar-logout a:hover { color: #dc3545; }
        .content {
            margin-left: 250px;
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
        .form-card {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .alert-info {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        .alert-danger {
            background-color: #e74c3c;
            color: white;
            border-color: #e74c3c;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h3>KriptoSistem</h3>
        <ul class="sidebar-nav">
            <li><a href="tampilan.php">Dashboard</a></li>
            <li><a href="enkripsi_dekripsi.php">Enkripsi Teks</a></li>
            <li><a href="stegano.php">Steganografi</a></li>
            <li><a href="enkripsi_file.php" class="active">Enkripsi File</a></li>
            <li><a href="database_enkripsi.php">Pesan Rahasia</a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h1>Enkripsi & Dekripsi File (AES-256 + Kunci dari Password)</h1>
        </div>

        <div class="form-card">
            <div class="alert alert-warning">
                <b>Perhatian:</b> Metode ini menggunakan Password Anda untuk membuat Kunci Enkripsi.
                Gunakan password yang kuat dan ingat baik-baik. Password yang sama
                harus digunakan untuk dekripsi.
            </div>
            
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="fileToProcess" class="form-label">Upload File:</label>
                    <input type="file" class="form-control" id="fileToProcess" name="fileToProcess" required>
                </div>

                <div class="mb-3">
                    <label for="key" class="form-label">Password / Kunci Rahasia:</label>
                    <input type="password" class="form-control" id="key" name="key" required>
                </div>

                <div class="mb-3">
                    <label for="action" class="form-label">Pilih Aksi:</label>
                    <select class="form-select" id="action" name="action" required>
                        <option value="encrypt">Enkripsi</option>
                        <option value="decrypt">Dekripsi</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Proses</button>
            </form>

            <?php if (!empty($result)): ?>
                <div class="alert <?php echo (strpos($result, 'Error:') !== false) ? 'alert-danger' : 'alert-info'; ?> mt-4">
                    <strong>Hasil:</strong> <?= htmlspecialchars($result) ?>
                </div>
                <?php if ($result === "File berhasil dienkripsi!" || $result === "File berhasil didekripsi!"): ?>
                    <a href="<?= $outputPath ?>" class="btn btn-success mt-2" download>Download File yang Sudah Diproses</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
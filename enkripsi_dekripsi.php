<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("location:index.php?pesan=belum_login");
    exit(); 
}

function caesarEncrypt($text, $key) {
    $result = "";
    $key = $key % 26; 
    if ($key == 0) return $text; 

    foreach (str_split($text) as $char) {
        if (ctype_upper($char)) {
            $result .= chr((ord($char) - 65 + $key) % 26 + 65);
        } elseif (ctype_lower($char)) {
            $result .= chr((ord($char) - 97 + $key) % 26 + 97);
        } else {
            $result .= $char; 
        }
    }
    return $result;
}

function caesarDecrypt($text, $key) {
    if ($key == 0) return $text;
    return caesarEncrypt($text, 26 - ($key % 26)); 
}

function blockEncryptECB($data, $keyString) {
    $cipher = "AES-256-ECB";
    $encryption_key = hash('sha256', $keyString, true);
    
    $encrypted = openssl_encrypt($data, $cipher, $encryption_key, OPENSSL_RAW_DATA);
    return base64_encode($encrypted);
}

function blockDecryptECB($base64Data, $keyString) {
    $cipher = "AES-256-ECB";
    $encryption_key = hash('sha256', $keyString, true);
    
    $data = base64_decode($base64Data);
    if ($data === false) {
        throw new Exception("Input dekripsi bukan format Base64 yang valid.");
    }

    $decrypted = openssl_decrypt($data, $cipher, $encryption_key, OPENSSL_RAW_DATA);
    
    if ($decrypted === false) {
        throw new Exception("Dekripsi gagal. Periksa Kunci Block Cipher atau data ciphertext.");
    }

    return $decrypted;
}

$result = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $operation = $_POST["operation"];
        $text = $_POST["text"];
        $key_caesar = $_POST["key_caesar"]; 
        $key_aes = $_POST["key_aes"];       

        if (empty($text)) {
            throw new Exception("Teks tidak boleh kosong!");
        }
        if (!ctype_digit($key_caesar)) {
             throw new Exception("Kunci Caesar Cipher harus berupa ANGKA (misal: 3)!");
        }
        if (empty($key_aes)) {
            throw new Exception("Kunci Block Cipher tidak boleh kosong!");
        }

        $caesarShift = (int)$key_caesar;

        if ($operation === "encrypt") {
            $caesarResult = caesarEncrypt($text, $caesarShift);
            $finalResult = blockEncryptECB($caesarResult, $key_aes);
            $result = $finalResult;

        } elseif ($operation === "decrypt") {
            $blockResult = blockDecryptECB($text, $key_aes);
            $finalResult = caesarDecrypt($blockResult, $caesarShift);
            $result = $finalResult;
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enkripsi Teks - KriptoSistem</title>
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
        .result-text {
            word-wrap: break-word;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h3>KriptoSistem</h3>
        <ul class="sidebar-nav">
            <li><a href="tampilan.php">Dashboard</a></li>
            <li><a href="enkripsi_dekripsi.php" class="active">Enkripsi Teks</a></li>
            <li><a href="stegano.php">Steganografi</a></li>
            <li><a href="enkripsi_file.php">Enkripsi File</a></li>
            <li><a href="database_enkripsi.php">Pesan Rahasia</a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h1>Enkripsi Teks</h1>
        </div>

        <div class="form-card">
            <h5 class="mb-0">Enkripsi Lapis</h5>
            <p class="text-muted small">Metode: Caesar Cipher + Block Cipher (AES-256-ECB)</p>
            <hr>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mb-3"> 
                    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($result)): ?>
                <div class="alert alert-info mb-3"> 
                    <strong>Hasil:</strong>
                    <p class="result-text mb-0"><?= htmlspecialchars($result) ?></p>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label for="operation" class="form-label">Operasi</label>
                    <select class="form-select" name="operation" id="operation" required>
                        <option value="encrypt">Enkripsi (Teks -> Caesar -> AES)</option>
                        <option value="decrypt">Dekripsi (Teks -> AES -> Caesar)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="text" class="form-label">Teks Input</label>
                    <textarea class="form-control" name="text" id="text" rows="4" placeholder="Masukkan teks di sini..." required></textarea>
                </div>

                <div class="mb-3">
                    <label for="key_caesar" class="form-label">Kunci Caesar Cipher (Hanya Angka)</label>
                    <input type="text" class="form-control" name="key_caesar" id="key_caesar" placeholder="Contoh: 3" required>
                </div>

                <div class="mb-3">
                    <label for="key_aes" class="form-label">Kunci Block Cipher (Password AES)</label>
                    <input type="password" class="form-control" name="key_aes" id="key_aes" placeholder="Contoh: KunciRahasia123" required>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Proses</button>
                </div>
            </form>
            
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
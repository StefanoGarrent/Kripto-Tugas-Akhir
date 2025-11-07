<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("location:index.php?pesan=belum_login");
    exit();
}

define('OUTPUT_DIR', __DIR__ . '/outputs');
if (!is_dir(OUTPUT_DIR)) {
    @mkdir(OUTPUT_DIR, 0775, true);
}

define('ALPHA_SKIP_THRESHOLD', 127);

function vigenereEncryptBytes(string $plain, string $key): string {
    $keyLen = strlen($key);
    if ($keyLen === 0) return $plain;
    $out = '';
    $n = strlen($plain);
    for ($i = 0; $i < $n; $i++) {
        $out .= chr( (ord($plain[$i]) + ord($key[$i % $keyLen])) & 0xFF );
    }
    return $out;
}

function vigenereDecryptBytes(string $cipher, string $key): string {
    $keyLen = strlen($key);
    if ($keyLen === 0) return $cipher;
    $out = '';
    $n = strlen($cipher);
    for ($i = 0; $i < $n; $i++) {
        $out .= chr( (ord($cipher[$i]) - ord($key[$i % $keyLen]) + 256) & 0xFF );
    }
    return $out;
}

function countUsablePixelsGd($img): int {
    $w = imagesx($img);
    $h = imagesy($img);
    $usable = 0;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $argb = imagecolorat($img, $x, $y);
            $a = ($argb & 0x7F000000) >> 24;
            if ($a < ALPHA_SKIP_THRESHOLD) $usable++;
        }
    }
    return $usable;
}

function embedMessageVigenereLSB(string $imagePath, string $message, string $key, string $outputPath) {
    $src = @imagecreatefrompng($imagePath);
    if (!$src) return "Error: Gagal membuka gambar PNG.";

    $width  = imagesx($src);
    $height = imagesy($src);

    $img = imagecreatetruecolor($width, $height);
    imagealphablending($img, false);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    imagecopy($img, $src, 0, 0, 0, 0, $width, $height);
    imagedestroy($src);

    $cipher = vigenereEncryptBytes($message, $key);
    $len    = strlen($cipher);

    $payload    = pack('N', $len) . $cipher;
    $payloadLen = strlen($payload);

    $usablePixels  = countUsablePixelsGd($img);
    $capacityBits  = $usablePixels * 3;
    $neededBits    = $payloadLen * 8;

    if ($neededBits > $capacityBits) {
        imagedestroy($img);
        $maxBytes = (int)floor($capacityBits / 8) - 4;
        if ($maxBytes < 0) $maxBytes = 0;
        return "Error: Pesan terlalu panjang untuk gambar ini. Maksimal sekitar {$maxBytes} byte.";
    }

    $byteIndex = 0;
    $bitIndex  = 7;

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {

            $argb = imagecolorat($img, $x, $y);
            $a = ($argb & 0x7F000000) >> 24;
            if ($a >= ALPHA_SKIP_THRESHOLD) {
                continue;
            }

            $r = ($argb >> 16) & 0xFF;
            $g = ($argb >> 8)  & 0xFF;
            $b =  $argb        & 0xFF;

            for ($ch = 0; $ch < 3; $ch++) {
                if ($byteIndex >= $payloadLen) break;
                $bit = (ord($payload[$byteIndex]) >> $bitIndex) & 1;

                if ($ch === 0)      { $r = ($r & 0xFE) | $bit; }
                elseif ($ch === 1)  { $g = ($g & 0xFE) | $bit; }
                else                { $b = ($b & 0xFE) | $bit; }

                if ($bitIndex === 0) { $bitIndex = 7; $byteIndex++; }
                else                 { $bitIndex--; }
            }

            $color = imagecolorallocatealpha($img, $r, $g, $b, $a);
            imagesetpixel($img, $x, $y, $color);

            if ($byteIndex >= $payloadLen) break 2;
        }
    }

    $ok = imagepng($img, $outputPath);
    imagedestroy($img);

    if (!$ok) return "Error: Gagal menyimpan gambar keluaran.";
    return "Pesan terenkripsi (Vigenère) berhasil disisipkan ke dalam gambar!";
}

function extractAndDecryptMessage(string $imagePath, string $key) {
    $img = @imagecreatefrompng($imagePath);
    if (!$img) return "Error: Gagal membuka gambar PNG.";

    $width  = imagesx($img);
    $height = imagesy($img);

    $byte = 0;
    $bitCount = 0;
    $header = '';
    $cipher = '';
    $bytesToRead = null;

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {

            $argb = imagecolorat($img, $x, $y);
            $a = ($argb & 0x7F000000) >> 24;
            if ($a >= ALPHA_SKIP_THRESHOLD) {
                continue;
            }

            $r = ($argb >> 16) & 0xFF;
            $g = ($argb >> 8)  & 0xFF;
            $b =  $argb        & 0xFF;

            $bits = [ $r & 1, $g & 1, $b & 1 ];

            foreach ($bits as $lsb) {
                $byte = (($byte << 1) | $lsb) & 0xFF;
                $bitCount++;

                if ($bitCount === 8) {
                    $ch = chr($byte);

                    if ($bytesToRead === null) {
                        $header .= $ch;
                        if (strlen($header) === 4) {
                            $un = unpack('Nlen', $header);
                            $bytesToRead = $un['len'];

                            if ($bytesToRead < 0) {
                                imagedestroy($img);
                                return "Error: Header panjang tidak valid.";
                            }
                            if ($bytesToRead === 0) {
                                imagedestroy($img);
                                return "";
                            }
                        }
                    } else {
                        $cipher .= $ch;
                        if (strlen($cipher) === $bytesToRead) {
                            imagedestroy($img);
                            return vigenereDecryptBytes($cipher, $key);
                        }
                    }

                    $byte = 0;
                    $bitCount = 0;
                }
            }
        }
    }

    imagedestroy($img);
    return "Error: Data tersembunyi tidak lengkap atau gambar tidak berisi pesan.";
}

$result = "";
$action = "";
$fileType = "";
$outputWebPath = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $key    = $_POST["key"]    ?? "";

    if (empty($key)) {
        $result = "Error: Kunci Vigenère wajib diisi.";
    } elseif (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $imagePath = $_FILES["image"]["tmp_name"];
        $message   = $_POST["message"] ?? "";

        $fileType = @mime_content_type($imagePath);
        if ($fileType === false || (strpos($fileType, 'png') === false)) {
            $result = "Error: File yang diunggah bukan gambar PNG.";
        } else {
            if ($action === "embed") {
                if ($message === "") {
                    $result = "Error: Pesan tidak boleh kosong untuk proses penyisipan.";
                } else {
                    $rand = '';
                    try { $rand = bin2hex(random_bytes(2)); } catch (Throwable $e) { $rand = (string)mt_rand(1000,9999); }
                    $filename = 'steg_' . date('Ymd_His') . '_' . $rand . '.png';
                    $outputPath = OUTPUT_DIR . '/' . $filename;
                    $outputWebPath = 'outputs/' . $filename;

                    $result = embedMessageVigenereLSB($imagePath, $message, $key, $outputPath);
                }
            } elseif ($action === "extract") {
                $result = extractAndDecryptMessage($imagePath, $key);
            } else {
                $result = "Error: Aksi tidak dikenal.";
            }
        }
    } else {
        $result = "Error: Gagal mengunggah file gambar.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Steganografi (Vigenère + LSB) — KriptoSistem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .form-card {
            background-color: #ffffff; padding: 30px; border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.1);
        }
        .btn-primary { background-color: #3498db; border-color: #3498db; }
        .btn-primary:hover { background-color: #2980b9; border-color: #2980b9; }
        .alert-info { background-color: #3498db; color: white; border-color: #3498db; }
        .alert-danger { background-color: #e74c3c; color: white; border-color: #e74c3c; }
        .small-muted { font-size: .9rem; color: #6c757d; }
        pre.result { white-space: pre-wrap; word-wrap: break-word; background: #f8f9fa;
            padding: 12px; border-radius: 8px; border: 1px solid #e9ecef; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h3>KriptoSistem</h3>
        <ul class="sidebar-nav">
            <li><a href="tampilan.php">Dashboard</a></li>
            <li><a href="enkripsi_dekripsi.php">Enkripsi Teks</a></li>
            <li><a href="stegano.php" class="active">Steganografi</a></li>
            <li><a href="enkripsi_file.php">Enkripsi File</a></li>
            <li><a href="database_enkripsi.php">Pesan Rahasia</a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h1>Steganografi — <span class="text-primary">Vigenère Cipher + LSB</span></h1>
            <p class="small-muted mb-0">Plaintext akan <strong>dienkripsi Vigenère (byte-wise)</strong> lalu disisipkan ke LSB piksel PNG (R,G,B). Piksel <em>fully transparent</em> akan dilewati.</p>
        </div>

        <div class="form-card">
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="image" class="form-label">Upload Gambar (Hanya .PNG):</label>
                    <input type="file" class="form-control" id="image" name="image" accept=".png,image/png" required>
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label">Pesan (plaintext) — kosongkan jika <em>Ekstrak</em>:</label>
                    <textarea class="form-control" id="message" name="message" rows="3" placeholder="Tulis pesan yang ingin disisipkan..."></textarea>
                </div>

                <div class="mb-3">
                    <label for="key" class="form-label">Kunci Vigenère <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="key" name="key" placeholder="Masukkan kunci Vigenère" required>
                    <div class="form-text">Gunakan kunci yang sama saat ekstraksi.</div>
                </div>

                <div class="mb-3">
                    <label for="action" class="form-label">Pilih Aksi:</label>
                    <select class="form-select" id="action" name="action" required>
                        <option value="embed">Sisipkan (Enkripsi + LSB)</option>
                        <option value="extract">Ekstrak (LSB + Dekripsi)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Proses</button>
            </form>

            <?php if (!empty($result)): ?>
                <div class="alert <?php echo (strpos($result, 'Error:') !== false) ? 'alert-danger' : 'alert-info'; ?> mt-4">
                    <strong>Hasil:</strong>
                    <?php if ($action === "extract" && strpos($result, 'Error:') === false): ?>
                        <pre class="result mb-0"><?= htmlspecialchars($result) ?></pre>
                    <?php else: ?>
                        <span><?= htmlspecialchars($result) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($action === "embed" && isset($outputWebPath) && $outputWebPath !== "" && strpos($result, 'berhasil') !== false): ?>
                    <a href="<?= htmlspecialchars($outputWebPath) ?>" class="btn btn-success mt-2" download>Download Gambar dengan Pesan</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
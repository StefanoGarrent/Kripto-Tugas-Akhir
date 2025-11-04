<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Sistem Komunikasi Aman</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa; /* Latar belakang abu-abu muda */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            width: 350px;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50; /* Warna gelap */
        }
        input {
            width: 100%;
            padding: 12px; 
            margin-bottom: 15px; 
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box; 
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #3498db; /* Warna biru primer baru */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #2980b9; /* Biru lebih gelap */
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
        .login-link a {
            color: #3498db;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Daftar Akun Baru</h2>
    <form method="POST" action="signup.php">
        <label for="nama">Nama Lengkap:</label>
        <input type="text" id="nama" name="nama" placeholder="Masukkan Nama" required>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" placeholder="Masukkan Email" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder="Masukkan Password" required>
        
        <button type="submit">Daftar</button>
    </form>
    
    <div class="login-link">
        <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</div>

</body>
</html>
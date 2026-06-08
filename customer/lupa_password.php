<?php
session_start();
require_once '../config/koneksi.php';

if(isset($_SESSION['pelanggan_id'])) {
    header("Location: index.php");
    exit;
}

$pesan_error = '';
$pesan_sukses = '';

if(isset($_POST['reset_password'])) {
    $email = strtolower(mysqli_real_escape_string($conn, trim($_POST['email'])));
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    
    $password_baru = password_hash($_POST['password_baru'], PASSWORD_DEFAULT); 

    $cek_akun = mysqli_query($conn, "SELECT id FROM pelanggan WHERE email = '$email' AND nama_lengkap = '$nama'");
    
    if(mysqli_num_rows($cek_akun) > 0) {
        $update_pelanggan = mysqli_query($conn, "UPDATE pelanggan SET password = '$password_baru' WHERE email = '$email'");
        
        $update_users = mysqli_query($conn, "UPDATE users SET password = '$password_baru' WHERE email = '$email'");

        if($update_pelanggan) {
            $pesan_sukses = "Password berhasil diubah! Silakan kembali ke halaman Login untuk masuk.";
        } else {
            $pesan_error = "Terjadi kesalahan sistem saat memperbarui password.";
        }
    } else {
        $pesan_error = "Data tidak ditemukan! Pastikan Email dan Nama Lengkap sama persis dengan saat pendaftaran.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Lupa Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --navy: #2F4156;
            --teal: #567C8D;
            --sky-blue: #C8D9E6;
            --beige: #F5EFEB;
            --white: #FFFFFF;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.5);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--beige); color: var(--navy); display: flex; align-items: center; justify-content: center; min-height: 100vh; overflow: hidden; position: relative; }
        a { text-decoration: none; color: var(--teal); font-weight: 700; transition: 0.3s; cursor: pointer; }
        a:hover { color: var(--navy); }

        .blob { position: absolute; border-radius: 50%; filter: blur(60px); z-index: -1; opacity: 0.6; }
        .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: var(--sky-blue); }
        .blob-2 { bottom: -10%; right: -5%; width: 400px; height: 400px; background: var(--teal); }

        .auth-container { width: 500px; max-width: 95%; background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 32px; box-shadow: 0 20px 50px rgba(47, 65, 86, 0.1); padding: 50px; position: relative; z-index: 10; }
        
        .auth-container h3 { font-size: 28px; font-weight: 800; color: var(--navy); margin-bottom: 10px; text-align: center; }
        .auth-container > p { font-size: 14px; color: var(--teal); margin-bottom: 30px; font-weight: 500; text-align: center; line-height: 1.5; }
        
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; font-size: 13px; font-weight: 700; color: var(--navy); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .input-group input { width: 100%; padding: 14px 20px; border-radius: 12px; border: 1px solid var(--sky-blue); background: var(--white); font-family: inherit; font-size: 15px; color: var(--navy); transition: 0.3s; outline: none; }
        .input-group input:focus { border-color: var(--teal); box-shadow: 0 0 0 4px rgba(86, 124, 141, 0.1); }
        
        .btn-submit { width: 100%; padding: 16px; background: var(--teal); color: var(--white); border: none; border-radius: 100px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { background: var(--navy); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(47, 65, 86, 0.15); }

        .alert { padding: 12px 20px; border-radius: 12px; font-size: 14px; font-weight: 600; margin-bottom: 20px; text-align: center; line-height: 1.4; }
        .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #f87171; }
        .alert-success { background: #dcfce3; color: #16a34a; border: 1px solid #86efac; }
        
        .btn-back { position: absolute; top: 30px; left: 30px; background: var(--white); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--navy); font-weight: bold; border: 1px solid var(--sky-blue); transition: 0.3s; z-index: 100; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .btn-back:hover { background: var(--teal); color: var(--white); transform: translateX(-5px); }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <a href="login_pelanggan.php" class="btn-back">←</a>

    <div class="auth-container">
        <h3>Pemulihan Sandi</h3>
        <p>Demi keamanan, silakan verifikasi identitas dengan memasukkan Email dan Nama Lengkap yang terdaftar.</p>

        <?php if($pesan_error) echo "<div class='alert alert-error'>$pesan_error</div>"; ?>
        <?php if($pesan_sukses) echo "<div class='alert alert-success'>$pesan_sukses</div>"; ?>

        <?php if(empty($pesan_sukses)): ?>
        <form action="" method="POST">
            <div class="input-group">
                <label>Email Terdaftar</label>
                <input type="email" name="email" placeholder="contoh@gmail.com" required>
            </div>
            
            <div class="input-group">
                <label>Nama Lengkap (Sesuai Akun)</label>
                <input type="text" name="nama_lengkap" placeholder="John Doe" required>
            </div>
            
            <div class="input-group">
                <label>Buat Password Baru</label>
                <input type="password" name="password_baru" placeholder="Minimal 6 karakter" minlength="6" required>
            </div>
            
            <button type="submit" name="reset_password" class="btn-submit">Ubah Password</button>
        </form>
        <?php else: ?>
            <button onclick="window.location.href='login_pelanggan.php'" class="btn-submit" style="background: var(--navy);">Kembali ke Halaman Login</button>
        <?php endif; ?>
    </div>

</body>
</html> 
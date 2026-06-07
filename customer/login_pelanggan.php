<?php
session_start();
require_once '../config/koneksi.php';

// Jika sudah login, tendang balik ke halaman utama
if(isset($_SESSION['pelanggan_id'])) {
    header("Location: index.php");
    exit;
}

$pesan_error = '';
$pesan_sukses = '';

// ==========================================
// LOGIKA REGISTER
// ==========================================
if(isset($_POST['register'])) {
    // Trim untuk menghapus spasi kosong di awal/akhir input
    $nama = trim(mysqli_real_escape_string($conn, $_POST['nama_lengkap']));
    $email = strtolower(trim(mysqli_real_escape_string($conn, $_POST['email'])));
    $no_hp = trim(mysqli_real_escape_string($conn, $_POST['no_hp']));
    $password_raw = $_POST['password']; 

    // VALIDASI KETAT SESUAI TEST CASE:

    // 4, 7, 9. Pengecekan input tidak boleh kosong (walau spasi doang)
    if (empty($nama) || empty($email) || empty($password_raw) || empty($no_hp)) {
        $pesan_error = "Pendaftaran gagal! Semua kolom wajib diisi.";
    } 
    // 1. Nama minimal karakter (misal 3 karakter) -> Pakai mb_strlen untuk dukung karakter asing
    elseif (mb_strlen($nama) < 3) {
        $pesan_error = "Pendaftaran gagal! Nama lengkap minimal 3 karakter.";
    }
    // 3. Nama maksimal karakter (mencegah overflow database)
    elseif (mb_strlen($nama) > 50) {
        $pesan_error = "Pendaftaran gagal! Nama lengkap maksimal 50 karakter.";
    }
    // 5. Nama hanya boleh huruf dan spasi (mendukung Arab, Korea, Kanji, dll pakai \p{L})
    elseif (!preg_match('/^[\p{L}\p{M}\s]+$/u', $nama)) {
        $pesan_error = "Pendaftaran gagal! Nama hanya boleh berisi huruf dan spasi (angka dan simbol tidak diizinkan).";
    }
    // 8. Email format valid & wajib @gmail.com
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/', $email)) {
        $pesan_error = "Pendaftaran gagal! Format email tidak valid atau tidak menggunakan domain @gmail.com.";
    }
    // 10. Password kurang dari 6 karakter
    elseif (strlen($password_raw) < 6) {
        $pesan_error = "Pendaftaran gagal! Password minimal 6 karakter.";
    }
    // 11. Password harus ada angka (Alfanumerik)
    elseif (!preg_match('/[0-9]/', $password_raw)) {
        $pesan_error = "Pendaftaran gagal! Password harus mengandung setidaknya satu angka.";
    }
    // 11. Password harus ada huruf (Alfanumerik)
    elseif (!preg_match('/[A-Za-z]/', $password_raw)) {
        $pesan_error = "Pendaftaran gagal! Password harus mengandung setidaknya satu huruf.";
    }
    // 12. Password harus ada huruf kapital
    elseif (!preg_match('/[A-Z]/', $password_raw)) {
        $pesan_error = "Pendaftaran gagal! Password harus mengandung setidaknya satu huruf kapital.";
    }
    // 13. Password harus ada karakter khusus
    elseif (!preg_match('/[\W_]/', $password_raw)) {
        $pesan_error = "Pendaftaran gagal! Password harus mengandung setidaknya satu karakter khusus (contoh: !@#$%^&*).";
    } 
    else {
        // 6. Cek apakah email sudah dipakai di database
        $cek_email = mysqli_query($conn, "SELECT email FROM pelanggan WHERE email = '$email'");
        if(mysqli_num_rows($cek_email) > 0) {
            $pesan_error = "Email sudah terdaftar! Silakan gunakan email lain atau langsung Login.";
        } else {
            // Semua validasi lolos, hash password dan masukkan ke DB
            $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);
            $insert = mysqli_query($conn, "INSERT INTO pelanggan (nama_lengkap, email, password, no_hp) VALUES ('$nama', '$email', '$password_hashed', '$no_hp')");
            
            if($insert) {
                // Pastikan juga ada entri pengguna di tabel users supaya order dapat disimpan dengan foreign key valid
                $existingUser = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
                if(mysqli_num_rows($existingUser) == 0) {
                    mysqli_query($conn, "INSERT INTO users (nama, email, password, no_hp, role) VALUES ('$nama', '$email', '$password_hashed', '$no_hp', 'customer')");
                }
                $pesan_sukses = "Akun berhasil dibuat! Silakan Login untuk melanjutkan.";
            } else {
                $pesan_error = "Terjadi kesalahan sistem saat menyimpan data.";
            }
        }
    }
}

// ==========================================
// LOGIKA LOGIN
// ==========================================
if(isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM pelanggan WHERE email = '$email'");
    
    if(mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        if(password_verify($password, $row['password'])) {
            $_SESSION['pelanggan_id'] = $row['id'];
            $_SESSION['pelanggan_nama'] = $row['nama_lengkap'];

            $userQuery = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' LIMIT 1");
            if(mysqli_num_rows($userQuery) > 0) {
                $userRow = mysqli_fetch_assoc($userQuery);
                $_SESSION['user_id'] = $userRow['id'];
            } else {
                $nama_user = mysqli_real_escape_string($conn, $row['nama_lengkap']);
                $email_user = mysqli_real_escape_string($conn, $row['email']);
                $password_user = mysqli_real_escape_string($conn, $row['password']);
                $hp_user = mysqli_real_escape_string($conn, $row['no_hp']);
                mysqli_query($conn, "INSERT INTO users (nama, email, password, no_hp, role) VALUES ('$nama_user', '$email_user', '$password_user', '$hp_user', 'customer')");
                $_SESSION['user_id'] = mysqli_insert_id($conn);
            }
            
            header("Location: index.php");
            exit;
        } else {
            $pesan_error = "Password yang Anda masukkan salah!";
        }
    } else {
        $pesan_error = "Akun dengan email tersebut tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Masuk & Daftar</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* PALET WARNA SIGNATURE */
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

        /* Blob Backgrounds */
        .blob { position: absolute; border-radius: 50%; filter: blur(60px); z-index: -1; opacity: 0.6; }
        .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: var(--sky-blue); }
        .blob-2 { bottom: -10%; right: -5%; width: 400px; height: 400px; background: var(--teal); }

        /* MAIN CONTAINER GLASSMORPHISM */
        /* TINGGI DIPERBESAR MENJADI 680px AGAR KONTEN TIDAK TUMPAH */
        .auth-container { width: 900px; max-width: 95%; height: 680px; background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 40px; box-shadow: 0 20px 50px rgba(47, 65, 86, 0.1); display: flex; overflow: hidden; position: relative; }

        /* VISUAL PANEL (Bagian Kiri/Brand Info) */
        .visual-panel { 
            flex: 1; 
            background: linear-gradient(to bottom, rgba(47, 65, 86, 0.3), rgba(47, 65, 86, 0.95)), url('../assets/dokumentasi/iklan1.jpg');
            background-size: cover;
            background-position: center;
            color: var(--white); 
            padding: 50px; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
            position: relative; 
            overflow: hidden; 
            z-index: 10; 
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .visual-panel h2 { font-size: 36px; font-weight: 800; line-height: 1.1; margin-bottom: 20px; letter-spacing: -1px; }
        .visual-panel p { font-size: 15px; color: var(--sky-blue); line-height: 1.6; }
        .visual-logo { font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
        
        .deco-circle { position: absolute; bottom: -50px; right: -50px; width: 200px; height: 200px; border-radius: 50%; background: var(--teal); opacity: 0.5; filter: blur(20px); }

        /* FORM PANEL (Area Input) */
        .form-panel { flex: 1; position: relative; }
        
        /* PADDING ATAS-BAWAH DIKURANGI SEDIKIT AGAR LEBIH LEGA */
        .form-wrapper { position: absolute; top: 0; left: 0; width: 100%; height: 100%; padding: 40px 50px; display: flex; flex-direction: column; justify-content: center; transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
        
        .form-login { transform: translateX(0); opacity: 1; pointer-events: auto; }
        .form-register { transform: translateX(100%); opacity: 0; pointer-events: none; }

        .auth-container.sign-up-mode .form-login { transform: translateX(-100%); opacity: 0; pointer-events: none; }
        .auth-container.sign-up-mode .form-register { transform: translateX(0); opacity: 1; pointer-events: auto; }
        
        .form-wrapper h3 { font-size: 28px; font-weight: 800; color: var(--navy); margin-bottom: 8px; }
        .form-wrapper > p { font-size: 14px; color: var(--teal); margin-bottom: 20px; font-weight: 500; }
        
        /* MARGIN INPUT DIKURANGI AGAR TIDAK TERLALU RENGGANG */
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; font-size: 12px; font-weight: 700; color: var(--navy); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .input-group input { width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--sky-blue); background: var(--white); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; color: var(--navy); transition: 0.3s; outline: none; }
        .input-group input:focus { border-color: var(--teal); box-shadow: 0 0 0 4px rgba(86, 124, 141, 0.1); }
        
        .btn-submit { width: 100%; padding: 16px; background: var(--teal); color: var(--white); border: none; border-radius: 100px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 5px; }
        .btn-submit:hover { background: var(--navy); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(47, 65, 86, 0.15); }

        .toggle-text { text-align: center; margin-top: 20px; font-size: 14px; color: var(--navy); font-weight: 500; }

        .alert { padding: 10px 15px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-bottom: 15px; text-align: center; line-height: 1.4; }
        .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #f87171; }
        .alert-success { background: #dcfce3; color: #16a34a; border: 1px solid #86efac; }
        
        .btn-back { position: absolute; top: 30px; left: 30px; background: var(--white); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--navy); font-weight: bold; border: 1px solid var(--sky-blue); transition: 0.3s; z-index: 100; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .btn-back:hover { background: var(--teal); color: var(--white); transform: translateX(-5px); }

        .forgot-password { display: block; text-align: right; font-size: 13px; color: var(--teal); margin-top: -8px; margin-bottom: 15px; font-weight: 700; transition: 0.3s; }
        .forgot-password:hover { color: var(--navy); text-decoration: underline; }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <a href="index.php" class="btn-back">←</a>

    <div class="auth-container" id="authContainer">
        
        <div class="visual-panel">
            <div class="visual-logo">PERSCENTS.</div>
            <div>
                <h2>Your Identity<br>in a Bottle.</h2>
                <p>Ribuan pelanggan telah menemukan aroma yang benar-benar mencerminkan diri mereka. Kini giliranmu menciptakan signature scent yang meninggalkan kesan di setiap momen.</p>
            </div>
            <div class="deco-circle"></div>
        </div>

        <div class="form-panel">
            
            <div class="form-wrapper form-login">
                <h3>Selamat Datang</h3>
                <p>Masuk untuk melacak pesanan atau melanjutkan racikan.</p>

                <?php if($pesan_error && isset($_POST['login'])) echo "<div class='alert alert-error'>$pesan_error</div>"; ?>
                <?php if($pesan_sukses) echo "<div class='alert alert-success'>$pesan_sukses</div>"; ?>

                <form action="" method="POST">
                    <div class="input-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="contoh@gmail.com" required>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    
                    <a href="lupa_password.php" class="forgot-password">Lupa Password?</a>

                    <button type="submit" name="login" class="btn-submit">Masuk Sekarang</button>
                </form>

                <p class="toggle-text">Belum punya akun? <a onclick="toggleMode()">Daftar di sini</a></p>
            </div>

            <div class="form-wrapper form-register">
                <h3>Buat Akun Baru</h3>
                <p>Lengkapi data di bawah untuk memulai perjalananmu.</p>

                <?php if($pesan_error && isset($_POST['register'])) echo "<div class='alert alert-error'>$pesan_error</div>"; ?>

                <form action="" method="POST">
                    <div class="input-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" placeholder="John Doe" minlength="3" maxlength="50" required>
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <div class="input-group" style="flex: 1;">
                            <label>Email (@gmail.com)</label>
                            <input type="email" name="email" placeholder="contoh@gmail.com" pattern=".*@gmail\.com$" title="Harus menggunakan domain @gmail.com" required>
                        </div>
                        <div class="input-group" style="flex: 1;">
                            <label>No. WhatsApp</label>
                            <input type="text" name="no_hp" placeholder="0812..." required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Min 6 huruf, ada kapital, angka & simbol" minlength="6" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{6,}" title="Password minimal 6 karakter, harus mengandung huruf kapital, huruf kecil, angka, dan karakter khusus." required>
                    </div>
                    <button type="submit" name="register" class="btn-submit">Daftar Sekarang</button>
                </form>

                <p class="toggle-text">Sudah punya akun? <a onclick="toggleMode()">Masuk di sini</a></p>
            </div>

        </div>
    </div>

    <script>
        function toggleMode() {
            const container = document.getElementById('authContainer');
            container.classList.toggle('sign-up-mode');
        }

        <?php if(isset($_POST['register']) && $pesan_error): ?>
            document.getElementById('authContainer').classList.add('sign-up-mode');
        <?php endif; ?>
    </script>
</body>
</html>
<?php
session_start();
require_once '../config/koneksi.php';

if(!isset($_SESSION['pelanggan_id'])) {
    header("Location: login_pelanggan.php");
    exit;
}

$id_pelanggan = (int)$_SESSION['pelanggan_id'];
$pesan_error = '';
$pesan_sukses = '';

// Cek apakah ada notifikasi sukses dari redirect
if (isset($_GET['status']) && $_GET['status'] === 'sukses') {
    $pesan_sukses = 'Profil berhasil diperbarui.';
}

$queryPelanggan = mysqli_query($conn, "SELECT nama_lengkap, email, no_hp, alamat, created_at FROM pelanggan WHERE id = $id_pelanggan LIMIT 1");

if(!$queryPelanggan || mysqli_num_rows($queryPelanggan) === 0) {
    header("Location: logout.php");
    exit;
}

$pelanggan = mysqli_fetch_assoc($queryPelanggan);

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if($nama === '' || $email === '' || $no_hp === '') {
        $pesan_error = 'Nama lengkap, email, dan nomor WhatsApp wajib diisi.';
    } else {
        $nama_sql = mysqli_real_escape_string($conn, $nama);
        $email_sql = mysqli_real_escape_string($conn, $email);
        $no_hp_sql = mysqli_real_escape_string($conn, $no_hp);
        $alamat_sql = mysqli_real_escape_string($conn, $alamat);
        $email_lama_sql = mysqli_real_escape_string($conn, $pelanggan['email']);

        $cekEmail = mysqli_query($conn, "SELECT id FROM pelanggan WHERE email = '$email_sql' AND id != $id_pelanggan LIMIT 1");
        if($cekEmail && mysqli_num_rows($cekEmail) > 0) {
            $pesan_error = 'Email sudah digunakan oleh akun lain.';
        } else {
            $updatePelanggan = mysqli_query($conn, "
                UPDATE pelanggan
                SET nama_lengkap = '$nama_sql',
                    email = '$email_sql',
                    no_hp = '$no_hp_sql',
                    alamat = '$alamat_sql'
                WHERE id = $id_pelanggan
            ");

            if($updatePelanggan) {
                mysqli_query($conn, "
                    UPDATE users
                    SET nama = '$nama_sql',
                        email = '$email_sql',
                        no_hp = '$no_hp_sql'
                    WHERE email = '$email_lama_sql' OR id = " . (int)($_SESSION['user_id'] ?? 0) . "
                ");
                $_SESSION['pelanggan_nama'] = $nama;
                
                // INI KUNCI FIX-NYA: Redirect paksa ke profil mode normal membawa status sukses
                header("Location: profil.php?status=sukses");
                exit;
                
            } else {
                $pesan_error = 'Gagal memperbarui profil. Silakan coba lagi.';
            }
        }
    }
}

$modeEdit = isset($_GET['edit']) && $_GET['edit'] === '1';
$tanggalDaftar = !empty($pelanggan['created_at']) ? date('d F Y, H:i', strtotime($pelanggan['created_at'])) . ' WIB' : '-';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Profil Saya</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #2F4156;
            --teal: #567C8D;
            --sky-blue: #C8D9E6;
            --beige: #F5EFEB;
            --white: #FFFFFF;
            --glass-bg: rgba(255, 255, 255, 0.4);
            --glass-border: rgba(255, 255, 255, 0.5);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--beige); color: var(--navy); min-height: 100vh; overflow-x: hidden; }
        a { color: inherit; text-decoration: none; }

        .blob { position: fixed; border-radius: 50%; filter: blur(60px); z-index: -1; opacity: 0.5; }
        .blob-1 { top: -10%; left: -8%; width: 460px; height: 460px; background: var(--sky-blue); }
        .blob-2 { right: -8%; bottom: -8%; width: 420px; height: 420px; background: var(--teal); }

        nav { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 1200px; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; z-index: 1000; background: var(--glass-bg); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); border-radius: 100px; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); }
        .logo { font-size: 20px; font-weight: 800; color: var(--navy); letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { font-size: 14px; font-weight: 600; color: var(--navy); transition: 0.3s; }
        .nav-links a:hover { color: var(--teal); }
        .nav-actions { display: flex; gap: 10px; align-items: center; }
        .btn-profile, .btn-cart, .btn-logout { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-profile svg, .btn-cart svg, .btn-logout svg { width: 24px; height: 24px; color: currentColor; }

        .page { max-width: 980px; margin: 150px auto 90px; padding: 0 20px; }
        .profile-card { background: rgba(255,255,255,0.78); border: 1px solid var(--sky-blue); border-radius: 32px; padding: 38px; box-shadow: 0 25px 60px rgba(47,65,86,0.08); }
        .profile-head { display: flex; align-items: center; gap: 22px; padding-bottom: 28px; border-bottom: 1px solid var(--sky-blue); margin-bottom: 28px; }
        .avatar { width: 82px; height: 82px; border-radius: 24px; background: var(--navy); color: var(--white); display: grid; place-items: center; font-size: 30px; font-weight: 900; }
        .profile-head h1 { font-size: 34px; font-weight: 900; margin-bottom: 6px; }
        .profile-head p { color: var(--teal); font-size: 14px; font-weight: 700; }

        .info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .info-box { background: var(--white); border: 1px solid rgba(200,217,230,0.9); border-radius: 18px; padding: 20px; }
        .info-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--teal); font-weight: 900; margin-bottom: 8px; }
        .info-value { font-size: 17px; font-weight: 800; line-height: 1.5; overflow-wrap: anywhere; }
        .info-box.full { grid-column: 1 / -1; }
        .profile-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-label { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--teal); font-weight: 900; margin-bottom: 8px; }
        .form-input, .form-textarea { width: 100%; border: 1px solid rgba(200,217,230,0.9); border-radius: 18px; background: var(--white); color: var(--navy); padding: 18px 20px; font-family: inherit; font-size: 16px; font-weight: 800; outline: none; transition: border 0.2s ease, box-shadow 0.2s ease; }
        .form-textarea { min-height: 110px; resize: vertical; line-height: 1.5; }
        .form-input:focus, .form-textarea:focus { border-color: var(--teal); box-shadow: 0 0 0 4px rgba(86,124,141,0.12); }
        .alert { padding: 14px 18px; border-radius: 16px; font-size: 14px; font-weight: 800; margin-bottom: 18px; }
        .alert-success { background: rgba(22,163,74,0.12); color: #166534; border: 1px solid rgba(22,163,74,0.24); }
        .alert-error { background: rgba(220,38,38,0.12); color: #991b1b; border: 1px solid rgba(220,38,38,0.24); }
        .actions { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 28px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 14px 22px; border-radius: 999px; border: 1.5px solid var(--sky-blue); font-size: 14px; font-weight: 900; cursor: pointer; }
        .btn-dark { background: var(--navy); color: var(--white); border-color: var(--navy); }
        button.btn { font-family: inherit; }

        @media (max-width: 720px) {
            nav { width: calc(100% - 24px); padding: 14px 18px; gap: 12px; flex-wrap: wrap; }
            .nav-links { order: 3; width: 100%; justify-content: center; gap: 18px; }
            .page { margin-top: 170px; }
            .profile-card { padding: 26px; }
            .profile-head { align-items: flex-start; flex-direction: column; }
            .info-grid, .profile-form { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <nav>
        <div class="logo">PERSCENTS.</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="track_order.php">Track Order</a>
        </div>
        <div class="nav-actions">
            <button class="btn-cart" onclick="window.location.href='keranjang.php'" aria-label="Keranjang">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            </button>
            <button class="btn-profile" onclick="window.location.href='profil.php'" aria-label="Profilku">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </button>
            <button class="btn-logout" onclick="window.location.href='logout.php'" aria-label="Logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </button>
        </div>
    </nav>

    <main class="page">
        <section class="profile-card">
            <div class="profile-head">
                <div class="avatar"><?= strtoupper(substr($pelanggan['nama_lengkap'], 0, 1)); ?></div>
                <div>
                    <h1><?= $modeEdit ? 'Edit Profil' : 'Profil Pelanggan'; ?></h1>
                    <p><?= $modeEdit ? 'Perbarui informasi akun pelanggan Anda.' : 'Informasi akun yang Anda masukkan saat registrasi.'; ?></p>
                </div>
            </div>

            <?php if($pesan_sukses): ?><div class="alert alert-success"><?= htmlspecialchars($pesan_sukses); ?></div><?php endif; ?>
            <?php if($pesan_error): ?><div class="alert alert-error"><?= htmlspecialchars($pesan_error); ?></div><?php endif; ?>

            <?php if($modeEdit): ?>
                <form method="post" class="profile-form">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-input" value="<?= htmlspecialchars($pelanggan['nama_lengkap']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor WhatsApp</label>
                        <input type="text" name="no_hp" class="form-input" value="<?= htmlspecialchars($pelanggan['no_hp'] ?: ''); ?>" required>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($pelanggan['email']); ?>" required>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-textarea" placeholder="Masukkan alamat Anda"><?= htmlspecialchars($pelanggan['alamat'] ?: ''); ?></textarea>
                    </div>
                    <div class="actions">
                        <button type="submit" name="update_profil" class="btn btn-dark">Simpan Perubahan</button>
                        <a href="profil.php" class="btn">Batal</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="info-grid">
                    <div class="info-box">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value"><?= htmlspecialchars($pelanggan['nama_lengkap']); ?></div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Nomor WhatsApp</div>
                        <div class="info-value"><?= htmlspecialchars($pelanggan['no_hp'] ?: '-'); ?></div>
                    </div>
                    <div class="info-box full">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($pelanggan['email']); ?></div>
                    </div>
                    <div class="info-box full">
                        <div class="info-label">Alamat</div>
                        <div class="info-value"><?= htmlspecialchars($pelanggan['alamat'] ?: 'Belum ditambahkan'); ?></div>
                    </div>
                    <div class="info-box full">
                        <div class="info-label">Tanggal Daftar</div>
                        <div class="info-value"><?= htmlspecialchars($tanggalDaftar); ?></div>
                    </div>
                </div>

                <div class="actions">
                    <a href="index.php" class="btn btn-dark">Kembali ke Beranda</a>
                    <a href="profil.php?edit=1" class="btn">Edit Profil</a>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
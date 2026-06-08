<?php
session_start();
require_once '../config/koneksi.php';

$queryNotes = mysqli_query($conn, "SELECT * FROM notes_aroma_custom ORDER BY kategori ASC, nama ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Ensiklopedia Notes</title>
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--beige); color: var(--navy); overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }
        
        .blob { position: absolute; border-radius: 50%; filter: blur(60px); z-index: -1; opacity: 0.5; }
        .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: var(--sky-blue); }
        .blob-2 { top: 40%; right: -5%; width: 400px; height: 400px; background: var(--teal); }

        nav { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 1200px; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; z-index: 1000; background: var(--glass-bg); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); border-radius: 100px; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); }
        .logo { font-size: 20px; font-weight: 800; color: var(--navy); letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { font-size: 14px; font-weight: 600; color: var(--navy); transition: 0.3s; }
        .nav-links a:hover { color: var(--teal); }
        .btn-nav-login { padding: 10px 24px; background: var(--navy); color: var(--white); border-radius: 100px; font-weight: 700; font-size: 14px; transition: 0.3s; border: none; cursor: pointer; }
        .btn-nav-login:hover { background: var(--teal); transform: translateY(-2px); }
        .nav-actions { display: flex; gap: 10px; align-items: center; }
        .btn-profile { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-profile svg { width: 24px; height: 24px; color: currentColor; }
        .btn-cart { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-cart svg { width: 24px; height: 24px; color: currentColor; }
        .btn-logout { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-logout svg { width: 24px; height: 24px; color: currentColor; }

        .page-header { max-width: 1200px; margin: 150px auto 50px; padding: 0 20px; display: flex; justify-content: space-between; align-items: flex-end; }
        .header-text h1 { font-size: 48px; font-weight: 800; color: var(--navy); letter-spacing: -1.5px; line-height: 1.1; margin-bottom: 15px; }
        .header-text p { font-size: 16px; color: var(--teal); font-weight: 500; max-width: 500px; }
        
        .btn-beli { padding: 16px 32px; background: var(--teal); color: var(--white); border-radius: 100px; font-weight: 700; font-size: 16px; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; border: none; cursor: pointer; box-shadow: 0 10px 20px rgba(86, 124, 141, 0.2); }
        .btn-beli:hover { background: var(--navy); transform: translateY(-3px); box-shadow: 0 15px 25px rgba(47, 65, 86, 0.3); }

        .notes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto 100px; padding: 0 20px; }
        
        .note-card { background: var(--white); border-radius: 24px; padding: 20px; border: 1px solid var(--sky-blue); transition: 0.3s; display: flex; flex-direction: column; }
        .note-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(47, 65, 86, 0.08); }
        
        .note-img-box { width: 100%; height: 220px; border-radius: 16px; background: var(--beige); overflow: hidden; margin-bottom: 20px; position: relative; }
        .note-img-box img { width: 100%; height: 100%; object-fit: cover; }
        
        .badge-kategori { position: absolute; top: 15px; right: 15px; padding: 6px 14px; border-radius: 100px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; backdrop-filter: blur(8px); }
        .badge-top { background: rgba(200, 217, 230, 0.9); color: var(--navy); }
        .badge-middle { background: rgba(86, 124, 141, 0.9); color: var(--white); }
        .badge-base { background: rgba(47, 65, 86, 0.9); color: var(--white); }
        .badge-default { background: rgba(255, 255, 255, 0.9); color: var(--navy); }

        .note-info { flex: 1; display: flex; flex-direction: column; }
        .note-info h3 { font-size: 20px; font-weight: 800; color: var(--navy); margin-bottom: 10px; }
        .note-info p { font-size: 14px; color: var(--teal); line-height: 1.6; font-weight: 500; margin-bottom: 15px; flex: 1; }

        footer { background: var(--navy); color: var(--white); padding: 40px; text-align: center; font-weight: 600; font-size: 14px; border-radius: 40px 40px 0 0; }
        
        @media (max-width: 768px) {
            .page-header { flex-direction: column; align-items: flex-start; gap: 25px; margin-top: 120px; }
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
        
        <?php if(isset($_SESSION['pelanggan_id'])) : ?>
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
        <?php else : ?>
            <button class="btn-nav-login" onclick="window.location.href='login_pelanggan.php'">Masuk</button>
        <?php endif; ?>
    </nav>

    <div class="page-header">
        <div class="header-text">
            <h1>Ensiklopedia Notes</h1>
            <p>Jelajahi koleksi notes pilihan kami. Kenali karakter setiap aroma, lalu padukan sesuai seleramu untuk menciptakan parfum yang benar-benar unik.</p>
        </div>
        <button class="btn-beli" onclick="window.location.href='buat_custom.php'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20.94c1.5 0 2.75 1.06 4 1.06 3 0 6-8 6-12.22A4.91 4.91 0 0 0 17 5c-2.22 0-4 1.44-5 2-1-.56-2.78-2-5-2a4.9 4.9 0 0 0-5 4.78C2 14 5 22 8 22c1.25 0 2.5-1.06 4-1.06Z"></path><path d="M10 2c1 .5 2 2 2 5"></path></svg>
            Mulai Meracik (Beli)
        </button>
    </div>

    <div class="notes-grid">
        <?php 
        if(mysqli_num_rows($queryNotes) > 0) {
            while($note = mysqli_fetch_assoc($queryNotes)) {
                $foto_path = (!empty($note['foto'])) ? '../' . $note['foto'] : '../assets/perscents_kotak.png';
                
                $kategori = strtolower($note['kategori']);
                $badge_class = 'badge-default';
                if (strpos($kategori, 'top') !== false) $badge_class = 'badge-top';
                elseif (strpos($kategori, 'middle') !== false || strpos($kategori, 'heart') !== false) $badge_class = 'badge-middle';
                elseif (strpos($kategori, 'base') !== false) $badge_class = 'badge-base';
        ?>
        
        <div class="note-card">
            <div class="note-img-box">
                <div class="badge-kategori <?= $badge_class; ?>">
                    <?= htmlspecialchars($note['kategori']); ?> Note
                </div>
                <img src="<?= htmlspecialchars($foto_path); ?>" alt="<?= htmlspecialchars($note['nama']); ?>">
            </div>
            
            <div class="note-info">
                <h3><?= htmlspecialchars($note['nama']); ?></h3>
                <p><?= htmlspecialchars($note['deskripsi']); ?></p>
            </div>
        </div>

        <?php 
            }
        } else {
            echo "<p style='grid-column: 1/-1; text-align: center; color: var(--teal);'>Belum ada data notes aroma di database.</p>";
        }
        ?>
    </div>

    <footer>
        <p>&copy; 2026 PERSCENTS. Crafted with passion.</p>
    </footer>

</body>
</html>

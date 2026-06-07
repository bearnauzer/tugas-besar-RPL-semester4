<?php
session_start();
require_once '../config/koneksi.php';

// Ambil semua ulasan dari database
$queryUlasan = mysqli_query($conn, "SELECT * FROM ulasan_pelanggan ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Ulasan Pelanggan</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --navy: #2F4156; --teal: #567C8D; --sky-blue: #C8D9E6; 
            --beige: #F5EFEB; --white: #FFFFFF;
            --glass-bg: rgba(255, 255, 255, 0.4);
            --glass-border: rgba(255, 255, 255, 0.5);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--beige); color: var(--navy); overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }

        /* Blobs */
        .blob { position: fixed; border-radius: 50%; filter: blur(60px); z-index: -1; opacity: 0.6; }
        .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: var(--sky-blue); }
        .blob-2 { bottom: -10%; right: -5%; width: 600px; height: 600px; background: var(--teal); }

        /* NAVBAR (Sama seperti index) */
        nav { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 1200px; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; z-index: 1000; background: var(--glass-bg); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); border-radius: 100px; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); }
        .logo { font-size: 20px; font-weight: 800; color: var(--navy); letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { font-size: 14px; font-weight: 600; color: var(--navy); transition: 0.3s; }
        .nav-links a:hover { color: var(--teal); }
        .btn-back { padding: 10px 24px; background: var(--navy); color: var(--white); border-radius: 100px; font-weight: 700; font-size: 14px; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-back:hover { background: var(--teal); transform: translateY(-2px); }

        /* HEADER HALAMAN */
        .page-header { text-align: center; margin: 150px auto 60px; padding: 0 20px; }
        .page-header h1 { font-size: 48px; font-weight: 800; color: var(--navy); letter-spacing: -1px; margin-bottom: 15px; }
        .page-header p { font-size: 16px; color: var(--teal); font-weight: 500; max-width: 600px; margin: 0 auto; }

        /* MASONRY/GRID ULASAN */
        .reviews-container { max-width: 1200px; margin: 0 auto 100px; padding: 0 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; }
        
        .review-card { background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); padding: 40px; border-radius: 32px; transition: 0.4s; box-shadow: 0 10px 30px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
        .review-card:hover { transform: translateY(-5px); background: var(--white); border-color: var(--sky-blue); box-shadow: 0 20px 40px rgba(47, 65, 86, 0.1); }
        
        .stars { color: #f5b83f; font-size: 22px; margin-bottom: 20px; letter-spacing: 2px; }
        .review-text { font-size: 15px; color: var(--navy); font-weight: 500; margin-bottom: 30px; line-height: 1.6; flex-grow: 1; }
        
        .reviewer { display: flex; align-items: center; gap: 15px; border-top: 1px solid var(--sky-blue); padding-top: 20px; }
        .reviewer-avatar { width: 50px; height: 50px; border-radius: 50%; background: var(--navy); color: var(--white); display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; flex-shrink: 0; }
        .reviewer-info h5 { font-size: 16px; font-weight: 800; color: var(--navy); margin-bottom: 3px; }
        .reviewer-info span { font-size: 12px; color: var(--teal); font-weight: 600; display: block; }
        .reviewer-info .product-name { color: var(--navy); font-style: italic; opacity: 0.8; margin-top: 2px; }

        .empty-state { text-align: center; grid-column: 1 / -1; padding: 60px 20px; background: var(--glass-bg); border-radius: 32px; border: 1px dashed var(--sky-blue); }
        .empty-state h3 { font-size: 24px; color: var(--navy); margin-bottom: 10px; }
        .empty-state p { color: var(--teal); font-weight: 500; }
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
        <a href="index.php" class="btn-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali
        </a>
    </nav>

    <div class="page-header">
        <h1>Suara Pelanggan Kami</h1>
        <p>Jelajahi pengalaman mereka yang telah menemukan *signature scent* bersama PERSCENTS. Setiap ulasan adalah cerita baru.</p>
    </div>

    <div class="reviews-container">
        <?php 
        if(mysqli_num_rows($queryUlasan) > 0) {
            while($ulasan = mysqli_fetch_assoc($queryUlasan)) {
                $rating = (int)$ulasan['rating'];
        ?>
        <div class="review-card">
            <div class="stars">
                <?= str_repeat('★', $rating) . str_repeat('☆', 5 - $rating); ?>
            </div>
            <p class="review-text">"<?= nl2br(htmlspecialchars($ulasan['komentar'])); ?>"</p>
            
            <div class="reviewer">
                <div class="reviewer-avatar">
                    <?= strtoupper(substr($ulasan['nama_reviewer'], 0, 1)); ?>
                </div>
                <div class="reviewer-info">
                    <h5><?= htmlspecialchars($ulasan['nama_reviewer']); ?></h5>
                    <span>Pelanggan <?= htmlspecialchars($ulasan['kategori_pembeli'] ?? 'PERSCENTS'); ?></span>
                    <div class="product-name">Varian: <?= htmlspecialchars($ulasan['nama_produk']); ?></div>
                </div>
            </div>
        </div>
        <?php 
            }
        } else {
        ?>
            <div class="empty-state">
                <h3>Belum Ada Ulasan</h3>
                <p>Jadilah yang pertama membagikan pengalamanmu meracik parfum bersama kami!</p>
            </div>
        <?php } ?>
    </div>

</body>
</html>
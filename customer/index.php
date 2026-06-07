<?php
session_start();
require_once '../config/koneksi.php';

$queryTrending = mysqli_query($conn, "
    SELECT dp.produk_id, dp.nama_parfum, dp.tipe, SUM(dp.jumlah) as total_terjual, pc.foto, pc.kategori 
    FROM detail_pesanan dp
    JOIN pesanan p ON dp.pesanan_id = p.id
    JOIN produk_collection pc ON dp.produk_id = pc.id
    WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
      AND dp.tipe = 'collection'
    GROUP BY dp.produk_id, dp.nama_parfum, dp.tipe, pc.foto, pc.kategori
    ORDER BY total_terjual DESC
    LIMIT 3
");

if(mysqli_num_rows($queryTrending) == 0) {
    $queryTrending = mysqli_query($conn, "
        SELECT dp.produk_id, dp.nama_parfum, dp.tipe, SUM(dp.jumlah) as total_terjual, pc.foto, pc.kategori 
        FROM detail_pesanan dp
        JOIN produk_collection pc ON dp.produk_id = pc.id
        WHERE dp.tipe = 'collection'
        GROUP BY dp.produk_id, dp.nama_parfum, dp.tipe, pc.foto, pc.kategori
        ORDER BY total_terjual DESC
        LIMIT 3
    ");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Create Your Signature</title>
    <!-- Font Bersih, Modern, dan Bubbly: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* PALET WARNA SIGNATURE */
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--beige); color: var(--navy); overflow-x: hidden; scroll-behavior: smooth; }
        a { text-decoration: none; color: inherit; }
        
        /* Animasi Scroll */
        .reveal { opacity: 0; transform: translateY(40px); transition: all 0.8s cubic-bezier(0.5, 0, 0, 1); }
        .reveal.active { opacity: 1; transform: translateY(0); }

        /* Blob Backgrounds */
        .blob { position: absolute; border-radius: 50%; filter: blur(60px); z-index: -1; opacity: 0.6; }
        .blob-1 { top: 10%; left: -10%; width: 400px; height: 400px; background: var(--sky-blue); }
        .blob-2 { top: 40%; right: -5%; width: 500px; height: 500px; background: var(--teal); }
        .blob-3 { bottom: 10%; left: 20%; width: 600px; height: 600px; background: var(--sky-blue); }

        /* NAVBAR */
        nav { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 1200px; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; z-index: 1000; background: var(--glass-bg); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); border-radius: 100px; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); }
        .logo { font-size: 20px; font-weight: 800; color: var(--navy); letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { font-size: 14px; font-weight: 600; color: var(--navy); transition: 0.3s; }
        .nav-links a:hover { color: var(--teal); }
        .btn-nav-login { padding: 10px 24px; background: var(--navy); color: var(--white); border-radius: 100px; font-weight: 700; font-size: 14px; transition: 0.3s; border: none; cursor: pointer; }
        .btn-nav-login:hover { background: var(--teal); transform: translateY(-2px); }

        /* New navbar action buttons */
        .nav-actions { display: flex; gap: 10px; align-items: center; }
        .btn-profile { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-profile svg { width: 24px; height: 24px; color: currentColor; }

        .btn-cart { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-cart svg { width: 24px; height: 24px; color: currentColor; }

        .btn-logout { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-logout svg { width: 24px; height: 24px; color: currentColor; }

        /* HERO SECTION */
        .hero { position: relative; height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .hero-video-container { position: absolute; top: 20px; left: 20px; right: 20px; bottom: 20px; border-radius: 32px; overflow: hidden; }
        .hero-video { width: 100%; height: 100%; object-fit: cover; }
        .hero-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(47, 65, 86, 0.4), rgba(86, 124, 141, 0.6)); }
        
        .hero-content { position: relative; z-index: 2; background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(16px); border: 1px solid var(--glass-border); padding: 60px 40px; border-radius: 32px; text-align: center; max-width: 800px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .hero-content h1 { font-size: 64px; font-weight: 800; color: var(--white); line-height: 1.1; margin-bottom: 20px; letter-spacing: -2px; }
        .hero-content p { font-size: 18px; font-weight: 400; color: var(--white); margin-bottom: 40px; opacity: 0.9; }
        .btn-primary { padding: 10px 24px; background: var(--navy); color: var(--white); border-radius: 100px; font-weight: 700; font-size: 14px; transition: 0.3s; border: none; cursor: pointer; display: inline-block; }
        .btn-primary:hover { background: var(--sky-blue); border-color: var(--sky-blue); color: var(--navy); transform: translateY(-3px); }

        /* PROMO CAMPAIGN SLIDER */
        .promo-section { position: relative; width: 100%; max-width: 1200px; margin: -50px auto 100px; z-index: 10; }
        .slider-wrapper { position: relative; width: 100%; height: 350px; overflow: hidden; border-radius: 32px; background: var(--white); box-shadow: 0 20px 40px rgba(47, 65, 86, 0.05); border: 1px solid var(--glass-border); }
        .slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; transition: opacity 0.6s ease; display: flex; align-items: center; justify-content: space-between; padding: 40px 60px; background-size: cover; background-position: center; }
        .slide.active { opacity: 1; z-index: 2; }
        .slide-content { background: var(--glass-bg); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); padding: 40px; border-radius: 24px; max-width: 500px; text-align: left; }
        .slide-content h4 { font-size: 14px; font-weight: 700; color: var(--teal); text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px; }
        .slide-content h2 { font-size: 36px; font-weight: 800; color: var(--navy); margin-bottom: 15px; line-height: 1.2; letter-spacing: -1px; }
        .slide-content p { font-size: 15px; color: var(--navy); margin-bottom: 25px; opacity: 0.8; }
        .slider-controls { position: absolute; bottom: 30px; right: 50px; display: flex; gap: 15px; z-index: 3; }
        .slider-btn { width: 45px; height: 45px; border-radius: 50%; background: var(--white); border: 1px solid var(--sky-blue); color: var(--navy); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; font-size: 18px; }
        .slider-btn:hover { background: var(--navy); color: var(--white); }

        /* GENERAL SECTION HEADER */
        .section-header { text-align: center; margin-bottom: 60px; padding: 0 20px; }
        .section-header h2 { font-size: 42px; font-weight: 800; color: var(--navy); letter-spacing: -1px; }
        .section-header p { font-size: 16px; color: var(--teal); font-weight: 500; }

        /* TRENDING NOW */
        .trending-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; max-width: 1200px; margin: 0 auto 100px; padding: 0 20px; }
        .trend-card { background: var(--white); border-radius: 24px; padding: 20px; border: 1px solid var(--sky-blue); transition: 0.3s; display: flex; flex-direction: column; }
        .trend-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(47, 65, 86, 0.08); }
        .trend-img-box { width: 100%; height: 280px; border-radius: 16px; background: var(--beige); overflow: hidden; margin-bottom: 20px; }
        .trend-img-box img { width: 100%; height: 100%; object-fit: cover; }
        .trend-info { flex: 1; display: flex; flex-direction: column; }
        .trend-info h3 { font-size: 20px; font-weight: 800; color: var(--navy); margin-bottom: 5px; }
        .trend-info p { font-size: 13px; color: var(--teal); margin-bottom: 15px; font-weight: 600; text-transform: uppercase; }
        .trend-price { margin-top: auto; display: flex; justify-content: space-between; align-items: center; font-size: 16px; font-weight: 800; color: var(--navy); }
        .btn-shop { background: var(--sky-blue); color: var(--navy); border: none; padding: 10px 20px; border-radius: 100px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-shop:hover { background: var(--teal); color: var(--white); }

        /* REVIEWS */
        .reviews-section { position: relative; padding: 0 20px 120px; text-align: center; }
        .reviews-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; max-width: 1200px; margin: 0 auto; }
        .review-card { background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); padding: 40px; border-radius: 32px; text-align: left; cursor: pointer; transition: 0.4s; box-shadow: 0 10px 30px rgba(0,0,0,0.05); position: relative; }
        .review-card:hover { transform: translateY(-10px); background: var(--white); border-color: var(--sky-blue); }
        .play-btn-mini { position: absolute; top: 30px; right: 30px; width: 40px; height: 40px; background: var(--teal); color: var(--white); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .stars { color: #f5b83f; font-size: 18px; margin-bottom: 20px; letter-spacing: 2px; }
        .review-card p { font-size: 16px; color: var(--navy); font-weight: 500; margin-bottom: 30px; line-height: 1.6; }
        .reviewer { display: flex; align-items: center; gap: 15px; }
        .reviewer img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--sky-blue); }
        .reviewer h5 { font-size: 16px; font-weight: 800; color: var(--navy); }
        .reviewer span { font-size: 13px; color: var(--teal); font-weight: 600; }

        /* CUSTOM & COLLECTION SECTION */
        .lab-section { max-width: 1200px; margin: 0 auto 120px; padding: 0 20px; display: flex; flex-direction: column; gap: 40px; }
        .lab-wrapper { background: var(--white); border-radius: 40px; display: flex; align-items: center; padding: 50px; border: 1px solid var(--sky-blue); box-shadow: 0 20px 50px rgba(47, 65, 86, 0.05); }
        .lab-wrapper.reverse { flex-direction: row-reverse; }
        .lab-text { flex: 1; padding: 0 40px; }
        .lab-text h2 { font-size: 42px; font-weight: 800; color: var(--navy); line-height: 1.1; margin-bottom: 15px; letter-spacing: -1px; }
        .lab-text p { font-size: 16px; color: var(--teal); line-height: 1.6; margin-bottom: 35px; font-weight: 500; }
        .lab-visual { flex: 1; height: 380px; background: var(--beige); border-radius: 24px; overflow: hidden; border: 1px solid var(--sky-blue); }
        .lab-visual img { width: 100%; height: 100%; object-fit: cover; }

        /* OUR JOURNEY */
        .journey-section { max-width: 1000px; margin: 0 auto 80px; padding: 0 20px; }
        .timeline-card { background: var(--glass-bg); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); padding: 30px; border-radius: 24px; margin-bottom: 30px; display: flex; gap: 30px; align-items: flex-start; transition: 0.3s; }
        .timeline-card:hover { background: var(--white); transform: translateX(10px); }
        .year-badge { background: var(--navy); color: var(--white); font-size: 14px; font-weight: 800; padding: 10px 20px; border-radius: 100px; min-width: 80px; text-align: center; }
        .timeline-desc h3 { font-size: 22px; font-weight: 800; color: var(--navy); margin-bottom: 10px; }
        .timeline-desc p { font-size: 15px; color: var(--teal); font-weight: 500; line-height: 1.6; }

        /* MODAL YOUTUBE */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(47, 65, 86, 0.8); backdrop-filter: blur(10px); display: none; align-items: center; justify-content: center; z-index: 9999; opacity: 0; transition: 0.4s; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-card { background: var(--white); padding: 40px; border-radius: 32px; max-width: 600px; width: 90%; text-align: left; position: relative; transform: scale(0.9); transition: 0.4s; border: 1px solid var(--sky-blue); }
        .modal-overlay.active .modal-card { transform: scale(1); }
        .close-btn { position: absolute; top: 20px; right: 20px; background: var(--beige); border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 20px; color: var(--navy); cursor: pointer; transition: 0.3s; font-weight: bold; }
        .close-btn:hover { background: var(--teal); color: var(--white); transform: rotate(90deg); }
        .modal-media { margin-bottom: 25px; border-radius: 20px; overflow: hidden; }
        .video-wrapper { position: relative; padding-bottom: 56.25%; height: 0; background: var(--beige); display: none; }
        .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }
        .modal-card h3 { font-size: 24px; font-weight: 800; color: var(--navy); margin-bottom: 5px; }
        .modal-card #revStars { color: #f5b83f; margin-bottom: 15px; }
        .modal-card #revText { font-size: 16px; color: var(--navy); line-height: 1.6; font-weight: 500; }

        footer { background: var(--navy); color: var(--white); padding: 40px; text-align: center; font-weight: 600; font-size: 14px; border-radius: 40px 40px 0 0; }
    </style>
</head>
<body>

    <!-- Blobs Background untuk Efek Fresh -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <!-- NAVBAR GLASSMORPHISM -->
    <nav>
        <div class="logo">PERSCENTS.</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="track_order.php">Track Order</a>
        </div>
        
        <!-- LOGIKA NAVBAR DINAMIS -->
        <?php if(isset($_SESSION['pelanggan_id'])) : ?>
            <div class="nav-actions">
                <button class="btn-cart" onclick="window.location.href='keranjang.php'" aria-label="Keranjang">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </button>

                <button class="btn-profile" onclick="window.location.href='profil.php'" aria-label="Profilku">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </button>

                <button class="btn-logout" onclick="window.location.href='logout.php'" aria-label="Logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </button>
            </div>
        <?php else : ?>
            <button class="btn-nav-login" onclick="window.location.href='login_pelanggan.php'">
                <span>Masuk</span>
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-left:8px;">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
            </button>
        <?php endif; ?>
    </nav>

    <!-- HERO SECTION -->
    <div class="hero">
        <div class="hero-video-container">
            <video class="hero-video" autoplay loop muted playsinline>
                <source src="../assets/dokumentasi/biru.mp4" type="video/mp4">
            </video>
            <div class="hero-overlay"></div>
        </div>
        
        <div class="hero-content reveal">
            <h1>Discover Your<br>True Scent.</h1>
            <p>Parfum bukan sekadar wangi, ia adalah identitas. Temukan dan rancang aroma yang merepresentasikan siapa kamu sebenarnya.</p>
            <a href="../customer/shop.php" class="btn-primary">Belanja Sekarang</a>
        </div>
    </div>

    <!-- PROMO SLIDER -->
    <div class="promo-section reveal">
        <div class="slider-wrapper">
            <div class="slide active" style="background-image: url('../assets/dokumentasi/iklan2.jpg');">
                <div class="slide-content">
                    <h4>Featured Collection</h4>
                    <h2>Crafted for Every Personality</h2>
                    <p>Temukan aroma yang selaras dengan karakter dan ceritamu. Dari yang berani dan energik hingga tenang dan elegan, setiap parfum dirancang untuk menjadi bagian dari identitasmu.</p>
                </div>
            </div>
            <div class="slide" style="background-image: url('../assets/dokumentasi/iklan3.jpg');">
                <div class="slide-content">
                    <h4>Custom Fragrance Lab</h4>
                    <h2>Create Your Own Signature Scent</h2>
                    <p>Pilih ketahanan, padukan notes favoritmu, dan racik parfum yang benar-benar unik. Karena aroma terbaik adalah yang diciptakan khusus untukmu.</p>
                </div>
            </div>
        </div>
        <div class="slider-controls">
            <button class="slider-btn" onclick="gantiSlide(-1)">←</button>
            <button class="slider-btn" onclick="gantiSlide(1)">→</button>
        </div>
    </div>

    <!-- 1. TRENDING NOW -->
    <div id="katalog" class="section-header reveal">
        <h2>Trending Now</h2>
        <p>Pilihan terpopuler minggu ini dari pelanggan kami.</p>
    </div>
    
    <div class="trending-grid reveal">
        <?php 
        if(mysqli_num_rows($queryTrending) > 0) {
            while($trend = mysqli_fetch_assoc($queryTrending)) {
                $foto_path = (!empty($trend['foto'])) ? '../' . $trend['foto'] : '../assets/perscents_kotak.png';
                $kategori_text = ($trend['tipe'] == 'custom') ? 'Custom Blend' : ($trend['kategori'] ?? 'Katalog Produk');
        ?>
        <div class="trend-card">
            <div class="trend-img-box"><img src="<?= htmlspecialchars($foto_path); ?>" alt="Product"></div>
            <div class="trend-info">
                <h3><?= htmlspecialchars($trend['nama_parfum']); ?></h3>
                <p>Tipe: <?= htmlspecialchars(ucfirst($trend['tipe'])); ?> | <?= htmlspecialchars($kategori_text); ?></p>
                
                <!-- LOGIKA TOMBOL DETAIL DINAMIS -->
                <div class="trend-price">
                    Info Lengkap 
                    <?php if($trend['tipe'] == 'custom'): ?>
                        <button class="btn-shop" onclick="window.location.href='buat_custom.php'">Racik</button>
                    <?php else: ?>
                        <!-- Arahkan ke detail.php dengan membawa ID produk -->
                        <button class="btn-shop" onclick="window.location.href='detail.php?id=<?= $trend['produk_id']; ?>'">Lihat Detail</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php 
            }
        } else {
            echo "<p style='grid-column: 1 / -1; text-align: center; color: var(--teal);'>Belum ada data penjualan.</p>";
        }
        ?>
    </div>

    <!-- 2. REVIEWS -->
    <div class="reviews-section">
        <div class="section-header reveal">
            <h2>Apa Kata Mereka?</h2>
            <p>Ribuan aroma, berbagai karakter, dan satu tujuan: menemukan parfum yang terasa paling "kamu". Berikut pengalaman mereka yang telah mencobanya.</p>
        </div>
        
        <div class="reviews-grid">
            <div class="review-card reveal" onclick="openReview('Yuha H2H.', 'Varian ESFJ ini gila sih. Seger banget, awet seharian dipakai ngampus. Temen-temen pada nanya pakai parfum apa!', '5', '', 'H22zwVvBQE0')">
                <div class="play-btn-mini">▶</div>
                <div class="stars">★★★★★</div>
                <p>"Varian ESFJ ini gila sih. Seger banget, awet seharian dipakai ngampus..."</p>
                <div class="reviewer">
                    <img src="../assets/dokumentasi/avatar.jpg" alt="Ava">
                    <div>
                        <h5>Yuha H2H</h5>
                        <span>Mahasiswa & idol</span>
                    </div>
                </div>
            </div>
            <div class="review-card reveal" onclick="openReview('Song Weilong', 'Fitur custom-nya ngebantu banget buat bikin kado yang personal. Packaging rapi, pengiriman aman.', '5', '', 'bpmiPp7djSI')">
                <div class="play-btn-mini">▶</div>
                <div class="stars">★★★★★</div>
                <p>"Fitur custom-nya ngebantu banget buat bikin kado yang personal. Packaging rapi..."</p>
                <div class="reviewer">
                    <img src="../assets/dokumentasi/avatar.jpg" alt="Ava">
                    <div>
                        <h5>Song Weilong</h5>
                        <span>Customer Custom</span>
                    </div>
                </div>
            </div>
            <div class="review-card reveal" onclick="openReview('Keluarga Syisal', 'Wanginya enak, botolnya gemes. Tapi yang Kucing Pintar agak kemanisan buat seleraku.', '4', '', 'NtjXvUS8Cq8')">
                <div class="play-btn-mini">▶</div>
                <div class="stars">★★★★☆</div>
                <p>"Wanginya enak, botolnya gemes. Tapi yang Kucing Pintar agak kemanisan..."</p>
                <div class="reviewer">
                    <img src="../assets/dokumentasi/avatar.jpg" alt="Ava">
                    <div>
                        <h5>Keluarga Syisal</h5>
                        <span>Kids Collection</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOMBOL LIHAT SEMUA ULASAN DITAMBAHKAN DI SINI -->
        <div class="reveal" style="margin-top: 50px;">
            <a href="ulasan.php" class="btn-primary" style="padding: 16px 36px; font-size: 16px; display: inline-block;">
                Lihat Semua Ulasan Pelanggan
            </a>
        </div>
    </div>

    <!-- 3. DUAL CARDS: CUSTOM LAB & COLLECTIONS -->
    <div class="lab-section reveal">
        <div class="lab-wrapper">
            <div class="lab-text">
                <h2>Be the<br>Master Perfumer.</h2>
                <p>Mengapa memakai aroma yang sama dengan semua orang jika kamu bisa menciptakan milikmu sendiri? Pilih tingkat ketahanan yang kamu inginkan, padukan notes favoritmu, dan racik aroma yang benar-benar merepresentasikan dirimu.</p>
                <button class="btn-primary" style="padding: 16px 32px; font-size: 16px;" onclick="window.location.href='notes.php'">Lihat Semua Notes</button>
            </div>
            <div class="lab-visual">
                <video autoplay loop muted playsinline style="width: 100%; height: 100%; object-fit: cover;">
                    <source src="../assets/dokumentasi/depan_custom.mp4" type="video/mp4">
                </video>
            </div>
        </div>

        <div class="lab-wrapper reverse">
            <div class="lab-text">
                <h2>Explore the<br>Collections.</h2>
                <p>Setiap koleksi memiliki karakter dan ceritanya sendiri. Mulai dari parfum yang merefleksikan kepribadianmu hingga aroma lembut untuk buah hati, semuanya dirancang untuk menemani setiap momen spesial.</p>
                <button class="btn-primary" style="padding: 16px 32px; font-size: 16px; border-color: var(--navy);" onclick="window.location.href='collection.php'">Lihat Semua Katalog</button>
            </div>
            <div class="lab-visual">
                <video autoplay loop muted playsinline style="width: 100%; height: 100%; object-fit: cover;">
                    <source src="../assets/dokumentasi/depan_collection.mp4" type="video/mp4">
                </video>
            </div>
        </div>
    </div>

    <!-- 4. OUR JOURNEY -->
    <div id="cerita" class="section-header reveal">
        <h2>Perjalanan PERSCENTS</h2>
        <p>Lebih dari sekadar parfum, inilah cerita di balik setiap aroma yang kami ciptakan.</p>
    </div>

    <div class="journey-section">
        <div class="timeline-card reveal">
            <div class="year-badge">2023</div>
            <div class="timeline-desc">
                <h3>The Beginning</h3>
                <p>Semua berawal dari sebuah gagasan sederhana: setiap orang layak memiliki aroma yang unik, bukan sekadar mengikuti tren yang sama. Dari ide tersebut, kami mulai bereksperimen dan meracik formula pertama kami.</p>
            </div>
        </div>
        <div class="timeline-card reveal">
            <div class="year-badge">2024</div>
            <div class="timeline-desc">
                <h3>MBTI Meets Perfumery</h3>
                <p>Kami menghadirkan koleksi parfum berbasis 16 tipe kepribadian MBTI, salah satu yang pertama di Indonesia. Perpaduan antara psikologi dan seni wewangian ini menghadirkan pengalaman yang lebih personal, menyenangkan, dan penuh karakter.</p>
            </div>
        </div>
        <div class="timeline-card reveal">
            <div class="year-badge">2026</div>
            <div class="timeline-desc">
                <h3>Scale & Expansion</h3>
                <p>Inovasi kami berlanjut dengan hadirnya laboratorium kustomisasi digital. Kini, pelanggan dapat merancang kombinasi notes favorit mereka sendiri dan menciptakan aroma yang benar-benar mencerminkan identitas mereka, langsung dari layar perangkat mereka.</p>
            </div>
        </div>
    </div>

    <!-- MODAL YOUTUBE -->
    <div class="modal-overlay" id="modalReview">
        <div class="modal-card">
            <button class="close-btn" onclick="closeReview()">×</button>
            <div class="modal-media">
                <img id="revImg" src="" alt="Client" style="display: none; border-radius: 20px; width: 100px; height: 100px;">
                <div class="video-wrapper" id="videoContainer">
                    <iframe id="revVideo" src="" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                </div>
            </div>
            <h3 id="revName">Nama</h3>
            <div id="revStars">★★★★★</div>
            <p id="revText">Ulasan lengkap...</p>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <p>&copy; 2026 PERSCENTS. Crafted with passion.</p>
    </footer>

    <!-- SCRIPT LOGIC -->
    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));

        let currentSlideIdx = 0;
        const slides = document.querySelectorAll('.slide');
        function tampilkanSlide(index) {
            slides.forEach(s => s.classList.remove('active'));
            slides[index].classList.add('active');
        }
        function gantiSlide(step) {
            currentSlideIdx += step;
            if(currentSlideIdx >= slides.length) currentSlideIdx = 0;
            if(currentSlideIdx < 0) currentSlideIdx = slides.length - 1;
            tampilkanSlide(currentSlideIdx);
        }
        setInterval(() => gantiSlide(1), 5000);

        function openReview(name, text, rating, img, videoId) {
            document.getElementById('revName').innerText = name;
            document.getElementById('revText').innerText = '"' + text + '"';
            
            let starsHTML = '';
            for(let i=0; i<5; i++) {
                if(i < rating) starsHTML += '★'; else starsHTML += '☆';
            }
            document.getElementById('revStars').innerText = starsHTML;
            
            if(videoId && videoId.trim() !== '') {
                document.getElementById('revImg').style.display = 'none';
                document.getElementById('videoContainer').style.display = 'block';
                document.getElementById('revVideo').src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1';
            } else {
                document.getElementById('videoContainer').style.display = 'none';
                document.getElementById('revVideo').src = ''; 
                document.getElementById('revImg').src = img;
                document.getElementById('revImg').style.display = 'block';
            }
            document.getElementById('modalReview').classList.add('active');
        }

        function closeReview() {
            document.getElementById('modalReview').classList.remove('active');
            document.getElementById('revVideo').src = '';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('modalReview')) closeReview();
        }
    </script>
</body>
</html>
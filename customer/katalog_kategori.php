<?php
session_start();
require_once '../config/koneksi.php';

function ensure_ulasan_rating_columns($conn) {
    $columns = [
        'produk_id' => "ALTER TABLE ulasan_pelanggan ADD produk_id INT(11) NULL AFTER id_pelanggan",
        'tipe_produk' => "ALTER TABLE ulasan_pelanggan ADD tipe_produk VARCHAR(30) NULL AFTER produk_id"
    ];
    foreach($columns as $column => $alterSql) {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM ulasan_pelanggan LIKE '$column'");
        if($check && mysqli_num_rows($check) === 0) {
            mysqli_query($conn, $alterSql);
        }
    }
}

ensure_ulasan_rating_columns($conn);

if (!isset($_GET['kategori']) || empty($_GET['kategori'])) {
    header("Location: shop.php");
    exit;
}

$kategori_req = mysqli_real_escape_string($conn, strtolower(trim($_GET['kategori'])));

$queryKoleksi = mysqli_query($conn, "
    SELECT pc.*, COALESCE(AVG(u.rating), 0) AS avg_rating, COUNT(u.id) AS total_rating
    FROM produk_collection pc
    LEFT JOIN ulasan_pelanggan u ON u.produk_id = pc.id AND u.tipe_produk = 'collection'
    WHERE LOWER(pc.kategori) = '$kategori_req'
    GROUP BY pc.id
    ORDER BY pc.nama ASC
");

$judul_halaman = "Koleksi " . ucfirst($kategori_req);
$deskripsi_halaman = "Eksplorasi mahakarya wewangian yang diracik khusus untuk " . ucfirst($kategori_req) . ".";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | <?= $judul_halaman; ?></title>
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
        .logo { font-size: 20px; font-weight: 800; color: var(--navy); }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { font-size: 14px; font-weight: 600; transition: 0.3s; }
        .nav-links a:hover { color: var(--teal); }
        .btn-nav-login { padding: 10px 24px; background: var(--navy); color: var(--white); border-radius: 100px; font-weight: 700; font-size: 14px; border: none; cursor: pointer; transition: 0.3s; }
        .btn-nav-login:hover { background: var(--teal); transform: translateY(-2px); }
        .nav-actions { display: flex; gap: 10px; align-items: center; }
        .btn-profile { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-profile svg { width: 24px; height: 24px; color: currentColor; }
        .btn-cart { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-cart svg { width: 24px; height: 24px; color: currentColor; }
        .btn-logout { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-logout svg { width: 24px; height: 24px; color: currentColor; }

        .page-header { max-width: 1200px; margin: 150px auto 60px; padding: 0 20px; text-align: center; }
        .page-header h1 { font-size: 48px; font-weight: 800; color: var(--navy); letter-spacing: -1.5px; margin-bottom: 15px; text-transform: capitalize; }
        .page-header p { font-size: 16px; color: var(--teal); font-weight: 500; max-width: 600px; margin: 0 auto; line-height: 1.6; }

        .btn-back-shop { display: inline-flex; align-items: center; gap: 8px; color: var(--teal); font-weight: 700; font-size: 14px; margin-bottom: 30px; transition: 0.3s; }
        .btn-back-shop:hover { color: var(--navy); transform: translateX(-5px); }

        .koleksi-wrapper { max-width: 1200px; margin: 0 auto 100px; padding: 0 20px; }
        .koleksi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }
        
        .koleksi-card { background: var(--white); border-radius: 24px; padding: 20px; border: 1px solid var(--sky-blue); transition: all 0.4s cubic-bezier(0.5, 0, 0, 1); display: flex; flex-direction: column; }
        .koleksi-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(47, 65, 86, 0.08); border-color: var(--teal); }
        
        .card-img-box { width: 100%; height: 280px; border-radius: 16px; background: var(--beige); overflow: hidden; margin-bottom: 20px; position: relative; display: flex; align-items: center; justify-content: center; }
        .card-img-box img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .koleksi-card:hover .card-img-box img { transform: scale(1.05); }
        
        .badge-kategori { position: absolute; top: 15px; left: 15px; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px); color: var(--navy); padding: 6px 14px; border-radius: 100px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }

        .card-info { flex: 1; display: flex; flex-direction: column; }
        .card-info h3 { font-size: 22px; font-weight: 800; color: var(--navy); margin-bottom: 8px; }
        .card-info p { font-size: 13px; color: var(--teal); line-height: 1.6; font-weight: 500; margin-bottom: 20px; flex: 1; }
        .rating-summary { display: flex; align-items: center; gap: 8px; color: var(--teal); font-size: 13px; font-weight: 800; margin: 0 0 16px; }
        .rating-stars-display { color: #f5b83f; letter-spacing: 1px; font-size: 15px; }
        
        .card-action { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--sky-blue); padding-top: 15px; }
        .harga { font-size: 18px; font-weight: 800; color: var(--navy); }
        .btn-beli { padding: 10px 24px; background: var(--teal); color: var(--white); border-radius: 100px; font-weight: 700; font-size: 14px; transition: 0.3s; border: none; cursor: pointer; }
        .btn-beli:hover { background: var(--navy); }

        footer { background: var(--navy); color: var(--white); padding: 40px; text-align: center; font-weight: 600; font-size: 14px; border-radius: 40px 40px 0 0; }
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
        <h1><?= $judul_halaman; ?></h1>
        <p><?= $deskripsi_halaman; ?></p>
    </div>

    <div class="koleksi-wrapper">
        <a href="shop.php" class="btn-back-shop">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Kembali ke Pilihan Kategori
        </a>

        <div class="koleksi-grid">
            <?php 
            if(mysqli_num_rows($queryKoleksi) > 0) {
                while($item = mysqli_fetch_assoc($queryKoleksi)) {
                    $kategori_asli = $item['kategori'] ?? 'Unisex';
                    $foto_path = (!empty($item['foto'])) ? '../' . $item['foto'] : '../assets/perscents_kotak.png';
                    $harga = isset($item['harga']) ? 'Rp ' . number_format($item['harga'], 0, ',', '.') : 'Harga Varian';
            ?>
            
            <div class="koleksi-card">
                <div class="card-img-box">
                    <div class="badge-kategori"><?= htmlspecialchars($kategori_asli); ?></div>
                    <img src="<?= htmlspecialchars($foto_path); ?>" alt="<?= htmlspecialchars($item['nama']); ?>">
                </div>
                <div class="card-info">
                    <h3><?= htmlspecialchars($item['nama']); ?></h3>
                    <p><?= htmlspecialchars($item['deskripsi'] ?? 'Wewangian eksklusif dari koleksi katalog PERSCENTS.'); ?></p>
                    <?php
                        $avgRating = (float)($item['avg_rating'] ?? 0);
                        $roundedRating = (int)round($avgRating);
                        $totalRating = (int)($item['total_rating'] ?? 0);
                    ?>
                    <div class="rating-summary">
                        <span class="rating-stars-display"><?= str_repeat('★', $roundedRating) . str_repeat('☆', 5 - $roundedRating); ?></span>
                        <span><?= $totalRating > 0 ? number_format($avgRating, 1, ',', '.') . " ($totalRating)" : 'Belum ada rating'; ?></span>
                    </div>
                </div>
                <div class="card-action">
                    <div class="harga"><?= $harga; ?></div>
                    <button class="btn-beli" onclick="window.location.href='detail.php?id=<?= $item['id']; ?>'">Lihat Detail</button>
                </div>
            </div>

            <?php 
                }
            } else {
                echo "<div style='grid-column: 1/-1; text-align: center; padding: 50px; background: var(--white); border-radius: 24px; border: 1px dashed var(--teal);'>";
                echo "<h3 style='color: var(--navy); margin-bottom: 10px;'>Katalog Kosong</h3>";
                echo "<p style='color: var(--teal);'>Belum ada parfum untuk kategori " . ucfirst($kategori_req) . ".</p>";
                echo "</div>";
            }
            ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 PERSCENTS. Crafted with passion.</p>
    </footer>

</body>
</html>

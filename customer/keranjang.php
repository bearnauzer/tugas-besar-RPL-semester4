<?php
session_start();
require_once '../config/koneksi.php';

if(!isset($_SESSION['pelanggan_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu!'); window.location.href='login_pelanggan.php';</script>";
    exit;
}

$id_pelanggan = $_SESSION['pelanggan_id'];

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id > 0) {
        $deleteQuery = mysqli_query($conn, "DELETE FROM keranjang WHERE id = $hapus_id AND id_pelanggan = $id_pelanggan");
    }
    header('Location: keranjang.php');
    exit;
}

$queryKeranjang = mysqli_query($conn, "
    SELECT k.*, 
           pv.id_produk, p.nama AS nama_katalog, p.gambar AS gambar_katalog,
           u.nama AS ukuran_nama, u.ml,
           ket.nama AS ketahanan_nama, ket.durasi
    FROM keranjang k
    LEFT JOIN produk_varian pv ON k.id_varian = pv.id
    LEFT JOIN produk_collection p ON pv.id_produk = p.id
    LEFT JOIN mst_ukuran u ON (k.tipe = 'katalog' AND pv.id_ukuran = u.id) OR (k.tipe = 'custom' AND k.id_ukuran = u.id)
    LEFT JOIN mst_ketahanan ket ON (k.tipe = 'katalog' AND pv.id_ketahanan = ket.id) OR (k.tipe = 'custom' AND k.id_ketahanan = ket.id)
    WHERE k.id_pelanggan = $id_pelanggan
    ORDER BY k.created_at DESC
");

$total_belanja = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Keranjang Belanja</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --navy: #2F4156; --teal: #567C8D; --sky-blue: #C8D9E6; 
            --beige: #F5EFEB; --white: #FFFFFF; --light-bg: #F8FAF9;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--light-bg); color: var(--navy); }
        
        nav { background: var(--white); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 40px; }
        .logo { font-size: 24px; font-weight: 800; color: var(--navy); text-decoration: none; }
        .btn-back { display: flex; align-items: center; gap: 8px; color: var(--teal); font-weight: 700; text-decoration: none; }
        
        .container { max-width: 1000px; margin: 0 auto 100px; padding: 0 20px; }
        .page-title { font-size: 32px; font-weight: 800; margin-bottom: 30px; letter-spacing: -0.5px; }

        .cart-card { background: var(--white); border-radius: 24px; padding: 25px; display: flex; gap: 25px; margin-bottom: 20px; border: 1px solid var(--sky-blue); align-items: center; }
        .cart-img { width: 120px; height: 120px; border-radius: 16px; background: var(--beige); object-fit: contain; padding: 10px; }
        
        .cart-info { flex: 1; }
        .badge-tipe { display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: 800; border-radius: 6px; text-transform: uppercase; margin-bottom: 8px; }
        .badge-katalog { background: #e0f2fe; color: #0284c7; }
        .badge-custom { background: #fce7f3; color: #db2777; }
        
        .item-name { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
        .item-specs { font-size: 14px; color: var(--teal); font-weight: 500; margin-bottom: 12px; }
        .item-price { font-size: 18px; font-weight: 800; color: var(--navy); }

        .btn-delete { background: #fee2e2; color: #dc2626; border: none; padding: 12px; border-radius: 12px; cursor: pointer; transition: 0.3s; }
        .btn-delete:hover { background: #f87171; color: white; }

        .checkout-box { background: var(--navy); color: var(--white); border-radius: 32px; padding: 40px; margin-top: 40px; display: flex; justify-content: space-between; align-items: center; }
        .total-label { font-size: 16px; color: var(--sky-blue); margin-bottom: 5px; }
        .total-price { font-size: 32px; font-weight: 800; color: var(--beige); }
        .btn-checkout { background: var(--teal); color: var(--white); text-decoration: none; padding: 18px 40px; border-radius: 100px; font-size: 16px; font-weight: 700; transition: 0.3s; }
        .btn-checkout:hover { background: var(--beige); color: var(--navy); }
    </style>
</head>
<body>

    <nav>
        <a href="index.php" class="logo">PERSCENTS.</a>
        <a href="shop.php" class="btn-back">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Lanjut Belanja
        </a>
    </nav>

    <div class="container">
        <h1 class="page-title">Keranjang Belanja</h1>

        <?php if(mysqli_num_rows($queryKeranjang) > 0): ?>
            
            <?php while($item = mysqli_fetch_assoc($queryKeranjang)): 
                $total_belanja += $item['subtotal'];
                
                if($item['tipe'] == 'katalog') {
                    $nama_produk = $item['nama_katalog'];
                    $gambar_produk = (!empty($item['gambar_katalog'])) ? '../' . $item['gambar_katalog'] : '../assets/perscents_kotak.png';
                    $badge_class = 'badge-katalog';
                } else {
                    $nama_produk = $item['nama_custom'];
                    $gambar_produk = '../assets/perscents_kotak.png'; 
                    $badge_class = 'badge-custom';
                }
            ?>
            <div class="cart-card">
                <img src="<?= htmlspecialchars($gambar_produk); ?>" alt="Product" class="cart-img">
                <div class="cart-info">
                    <span class="badge-tipe <?= $badge_class; ?>"><?= $item['tipe']; ?></span>
                    <div class="item-name"><?= htmlspecialchars($nama_produk); ?></div>
                    <div class="item-specs">
                        <?= htmlspecialchars($item['ukuran_nama']); ?> (<?= $item['ml']; ?>ml) • <?= htmlspecialchars($item['ketahanan_nama']); ?>
                    </div>
                    <div class="item-price">Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></div>
                </div>
                
                <form method="post" style="margin:0;">
                    <input type="hidden" name="hapus_id" value="<?= intval($item['id']); ?>">
                    <button class="btn-delete" type="submit" title="Hapus Item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                </form>
            </div>
            <?php endwhile; ?>

            <div class="checkout-box">
                <div>
                    <div class="total-label">Total Belanja</div>
                    <div class="total-price">Rp <?= number_format($total_belanja, 0, ',', '.'); ?></div>
                </div>
                <a href="checkout.php" class="btn-checkout">Proses Checkout</a>
            </div>

        <?php else: ?>
            <div style="text-align: center; padding: 80px 20px; background: var(--white); border-radius: 32px; border: 1px dashed var(--sky-blue);">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--sky-blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 20px;"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <h3 style="margin-bottom: 10px;">Keranjang Masih Kosong</h3>
                <p style="color: var(--teal); margin-bottom: 30px;">Belum ada mahakarya parfum yang Anda pilih.</p>
                <a href="shop.php" class="btn-checkout" style="display: inline-block;">Mulai Belanja</a>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
<?php
session_start();
require_once '../config/koneksi.php';

$flashMessage = null;
$flashType = null;
if(isset($_SESSION['flash_message'])) {
    $flashMessage = $_SESSION['flash_message'];
    $flashType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$queryProduk = mysqli_query($conn, "SELECT * FROM produk_collection WHERE id = $id_produk");
if(mysqli_num_rows($queryProduk) == 0) {
    echo "<script>alert('Produk tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}
$produk = mysqli_fetch_assoc($queryProduk);

$queryVarian = mysqli_query($conn, "
    SELECT 
        pv.id AS id_varian, 
        pv.id_ukuran, 
        u.nama AS nama_ukuran, 
        u.ml, 
        pv.id_ketahanan, 
        k.nama AS nama_ketahanan, 
        k.durasi, 
        pv.harga_final 
    FROM produk_varian pv
    JOIN mst_ukuran u ON pv.id_ukuran = u.id
    JOIN mst_ketahanan k ON pv.id_ketahanan = k.id
    WHERE pv.id_produk = $id_produk
");

$varians = [];
$sizes = [];
$durations = [];

while($row = mysqli_fetch_assoc($queryVarian)) {
    $varians[] = $row;
    
    $sizes[$row['id_ukuran']] = [
        'nama' => $row['nama_ukuran'],
        'ml' => $row['ml']
    ];
    
    $durations[$row['id_ketahanan']] = [
        'nama' => $row['nama_ketahanan'],
        'durasi' => $row['durasi']
    ];
}

if(isset($_POST['add_to_cart'])) {
    if(!isset($_SESSION['pelanggan_id'])) {
        echo "<script>alert('Silakan login terlebih dahulu untuk menambahkan ke keranjang.'); window.location.href='login_pelanggan.php';</script>";
        exit;
    }

    $id_pelanggan = $_SESSION['pelanggan_id'];
    $id_varian = (int)$_POST['id_varian'];
    
    $cekHarga = mysqli_query($conn, "SELECT harga_final FROM produk_varian WHERE id = $id_varian");
    if(mysqli_num_rows($cekHarga) > 0) {
        $hargaData = mysqli_fetch_assoc($cekHarga);
        $subtotal = $hargaData['harga_final']; 
        
        $insertCart = mysqli_query($conn, "INSERT INTO keranjang (id_pelanggan, tipe, id_varian, jumlah, subtotal) VALUES ('$id_pelanggan', 'katalog', '$id_varian', 1, '$subtotal')");
        
        if($insertCart) {
            $_SESSION['flash_message'] = 'Berhasil ditambahkan ke keranjang!';
            $_SESSION['flash_type'] = 'success';
            header('Location: keranjang.php');
            exit;
        } else {
            $_SESSION['flash_message'] = 'Gagal menambahkan ke keranjang.';
            $_SESSION['flash_type'] = 'error';
            header('Location: detail.php?id=' . $id_produk);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | <?= htmlspecialchars($produk['nama']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --navy: #2F4156;
            --teal: #567C8D;
            --sky-blue: #C8D9E6;
            --beige: #F5EFEB;
            --white: #FFFFFF;
            --light-bg: #F8F9FA;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--light-bg); color: var(--navy); display: flex; justify-content: center; min-height: 100vh; padding: 40px 20px; }
        a { text-decoration: none; color: inherit; }

        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: var(--teal); font-weight: 600; font-size: 15px; margin-bottom: 30px; transition: 0.3s; }
        .btn-back:hover { color: var(--navy); transform: translateX(-5px); }

        .detail-container { max-width: 1000px; width: 100%; display: flex; gap: 60px; background: var(--white); padding: 40px; border-radius: 32px; box-shadow: 0 20px 40px rgba(47, 65, 86, 0.05); }

        .img-section { flex: 1; background: var(--beige); border-radius: 24px; overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 40px; position: relative; }
        .img-section img { width: 100%; max-width: 350px; object-fit: contain; filter: drop-shadow(0 20px 30px rgba(0,0,0,0.15)); }

        .info-section { flex: 1; display: flex; flex-direction: column; }
        .product-title { font-size: 32px; font-weight: 800; color: var(--navy); margin-bottom: 8px; letter-spacing: -0.5px; }
        .product-desc { font-size: 16px; color: var(--teal); font-weight: 500; margin-bottom: 40px; line-height: 1.6; }

        .section-label { font-size: 15px; font-weight: 700; color: var(--teal); margin-bottom: 12px; }

        .radio-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
        .radio-grid-full { display: flex; flex-direction: column; gap: 12px; margin-bottom: 40px; }

        .radio-card { position: relative; cursor: pointer; }
        .radio-card input { position: absolute; opacity: 0; cursor: pointer; }
        
        .radio-box { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 15px; border: 1.5px solid var(--sky-blue); border-radius: 16px; background: var(--white); transition: all 0.3s ease; text-align: center; }
        .radio-card input:checked ~ .radio-box { border-color: var(--navy); box-shadow: 0 0 0 1px var(--navy); background: rgba(200, 217, 230, 0.2); }
        .radio-card input:hover ~ .radio-box { border-color: var(--teal); }

        .radio-box-row { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border: 1.5px solid var(--sky-blue); border-radius: 16px; background: var(--white); transition: all 0.3s ease; }
        .radio-card input:checked ~ .radio-box-row { border-color: var(--navy); box-shadow: 0 0 0 1px var(--navy); background: rgba(200, 217, 230, 0.2); }

        .opt-title { font-size: 14px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
        .opt-sub { font-size: 12px; color: var(--teal); font-weight: 500; }

        .price-box { border: 1.5px solid var(--navy); border-radius: 16px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .price-label { font-size: 16px; font-weight: 600; color: var(--teal); }
        .price-total { font-size: 24px; font-weight: 800; color: var(--navy); }

        .btn-add { width: 100%; padding: 18px; background: var(--navy); color: var(--white); border: none; border-radius: 16px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .btn-add:hover { background: var(--teal); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(47, 65, 86, 0.15); }
        .btn-add:disabled { background: var(--sky-blue); cursor: not-allowed; transform: none; box-shadow: none; }

        .toast-container { position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; pointer-events: none; }
        .toast { min-width: 280px; max-width: 360px; padding: 18px 20px; border-radius: 20px; box-shadow: 0 24px 80px rgba(47,65,86,0.18); backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,0.55); display: flex; align-items: center; gap: 14px; color: #102a43; font-weight: 700; background: rgba(255,255,255,0.95); opacity: 0; transform: translateY(-16px); animation: slideIn 0.45s ease forwards; pointer-events: auto; }
        .toast.success { border-color: #bae6fd; background: linear-gradient(180deg, rgba(235,248,255,0.95), rgba(222,239,255,0.92)); color: #0f172a; }
        .toast.error { border-color: #fecaca; background: linear-gradient(180deg, rgba(255,243,244,0.95), rgba(254,226,230,0.92)); color: #7f1d1d; }
        .toast-icon { width: 24px; height: 24px; flex-shrink: 0; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 768px) {
            .detail-container { flex-direction: column; padding: 20px; }
            .radio-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="toast-container" id="toastContainer"></div>
    <div style="width: 100%; max-width: 1000px;">
        <a href="javascript:history.back()" class="btn-back">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Kembali
        </a>

        <div class="detail-container">
            <div class="img-section">
                <?php 
                $kolom_foto = !empty($produk['foto']) ? $produk['foto'] : (!empty($produk['gambar']) ? $produk['gambar'] : '');
                $foto_path = (!empty($kolom_foto)) ? '../' . $kolom_foto : '../assets/perscents_kotak.png'; 
                ?>
                <img src="<?= htmlspecialchars($foto_path); ?>" alt="<?= htmlspecialchars($produk['nama']); ?>">
            </div>

            <div class="info-section">
                <h1 class="product-title"><?= htmlspecialchars($produk['nama']); ?></h1>
                <p class="product-desc"><?= htmlspecialchars($produk['deskripsi'] ?? 'Wewangian eksklusif dengan karakter khas.'); ?></p>

                <form action="" method="POST" id="formCart">
                    <?php if(empty($varians)): ?>
                        <div style="background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; text-align: center; border: 1px solid #f87171;">
                            Oops! Varian ukuran dan ketahanan untuk produk ini belum tersedia.
                        </div>
                    <?php else: ?>
                    
                    <input type="hidden" name="id_varian" id="inputIdVarian" value="">

                    <div class="section-label">Choose Size</div>
                    <div class="radio-grid">
                        <?php $i=0; foreach($sizes as $id_uk => $uk): ?>
                        <label class="radio-card">
                            <input type="radio" name="ukuran" value="<?= $id_uk; ?>" <?= ($i==0)?'checked':''; ?> onchange="updatePrice()">
                            <div class="radio-box">
                                <div class="opt-title"><?= htmlspecialchars($uk['nama']); ?></div>
                                <div class="opt-sub"><?= htmlspecialchars($uk['ml']); ?>ml</div>
                            </div>
                        </label>
                        <?php $i++; endforeach; ?>
                    </div>

                    <div class="section-label">Choose Duration</div>
                    <div class="radio-grid-full">
                        <?php $j=0; foreach($durations as $id_ket => $ket): ?>
                        <label class="radio-card">
                            <input type="radio" name="ketahanan" value="<?= $id_ket; ?>" <?= ($j==0)?'checked':''; ?> onchange="updatePrice()">
                            <div class="radio-box-row">
                                <div>
                                    <div class="opt-title"><?= htmlspecialchars($ket['nama']); ?></div>
                                    <div class="opt-sub"><?= htmlspecialchars($ket['durasi']); ?></div>
                                </div>
                            </div>
                        </label>
                        <?php $j++; endforeach; ?>
                    </div>

                    <div class="price-box">
                        <div class="price-label">Total Price:</div>
                        <div class="price-total" id="displayTotal">Rp 0</div>
                    </div>

                    <button type="submit" name="add_to_cart" class="btn-add" id="btnSubmit">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        Add to Cart
                    </button>
                    
                    <?php endif; ?> 
                </form>
            </div>
        </div>
    </div>

    <script>
        const dataVarian = <?= json_encode($varians); ?>;

        function updatePrice() {
            if (dataVarian.length === 0) return; 

            const selectedUkuran = document.querySelector('input[name="ukuran"]:checked');
            const selectedKetahanan = document.querySelector('input[name="ketahanan"]:checked');

            if(!selectedUkuran || !selectedKetahanan) return;

            const idUkuran = selectedUkuran.value;
            const idKetahanan = selectedKetahanan.value;

            const varianDitemukan = dataVarian.find(v => v.id_ukuran == idUkuran && v.id_ketahanan == idKetahanan);

            const btnSubmit = document.getElementById('btnSubmit');
            const displayTotal = document.getElementById('displayTotal');
            const inputIdVarian = document.getElementById('inputIdVarian');

            if(varianDitemukan) {
                const hargaFormat = new Intl.NumberFormat('id-ID').format(varianDitemukan.harga_final);
                displayTotal.innerText = 'Rp ' + hargaFormat;
                inputIdVarian.value = varianDitemukan.id_varian;
                
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg> Add to Cart`;
            } else {
                displayTotal.innerText = 'Stok Kosong';
                inputIdVarian.value = '';
                btnSubmit.disabled = true;
                btnSubmit.innerText = 'Tidak Tersedia';
            }
        }

        window.onload = updatePrice;

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast ' + type;
            toast.innerHTML = `
                <div class="toast-icon">${type === 'success' ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>' : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12" y2="16"/></svg>'}</div>
                <div>${message}</div>
            `;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 3800);
        }

        <?php if($flashMessage): ?>
            showToast(<?= json_encode($flashMessage); ?>, <?= json_encode($flashType); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
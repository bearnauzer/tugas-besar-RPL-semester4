<?php
session_start();
require_once '../config/koneksi.php';

if(!isset($_SESSION['pelanggan_id'])) {
    header("Location: login_pelanggan.php");
    exit;
}

$id_pelanggan = $_SESSION['pelanggan_id'];

// Ambil data pelanggan untuk default value
$qPelanggan = mysqli_query($conn, "SELECT * FROM pelanggan WHERE id = $id_pelanggan");
$pelanggan = mysqli_fetch_assoc($qPelanggan);

// Pastikan selalu ada user_id yang valid untuk tabel pesanan
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if(!$user_id && $pelanggan) {
    $email_pelanggan = mysqli_real_escape_string($conn, $pelanggan['email']);
    $queryUser = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_pelanggan' LIMIT 1");
    if(mysqli_num_rows($queryUser) > 0) {
        $userRow = mysqli_fetch_assoc($queryUser);
        $user_id = $userRow['id'];
        $_SESSION['user_id'] = $user_id;
    } else {
        mysqli_query($conn, "INSERT INTO users (nama, email, password, no_hp, role) VALUES ('" . mysqli_real_escape_string($conn, $pelanggan['nama_lengkap']) . "', '$email_pelanggan', '" . mysqli_real_escape_string($conn, $pelanggan['password']) . "', '" . mysqli_real_escape_string($conn, $pelanggan['no_hp']) . "', 'customer')");
        $user_id = mysqli_insert_id($conn);
        $_SESSION['user_id'] = $user_id;
    }
}

if(!$user_id) {
    die('Gagal membuat referensi pengguna untuk pesanan. Silakan login kembali.');
}

// Ambil data keranjang (pastikan id_ketahanan/id_ukuran tersedia baik untuk katalog maupun custom)
$queryKeranjang = mysqli_query($conn, "
    SELECT k.*, 
           pv.id_produk, p.nama AS nama_katalog,
           -- gunakan pv.id_ukuran atau fallback ke k.id_ukuran (untuk custom)
           COALESCE(pv.id_ukuran, k.id_ukuran) AS id_ukuran,
           COALESCE(pv.id_ketahanan, k.id_ketahanan) AS id_ketahanan,
           u.nama AS ukuran_nama, u.ml,
           ket.nama AS ketahanan_nama, ket.durasi
    FROM keranjang k
    LEFT JOIN produk_varian pv ON k.id_varian = pv.id
    LEFT JOIN produk_collection p ON pv.id_produk = p.id
    LEFT JOIN mst_ukuran u ON (COALESCE(pv.id_ukuran, k.id_ukuran) = u.id)
    LEFT JOIN mst_ketahanan ket ON (COALESCE(pv.id_ketahanan, k.id_ketahanan) = ket.id)
    WHERE k.id_pelanggan = $id_pelanggan
");

if(mysqli_num_rows($queryKeranjang) == 0) {
    echo "<script>alert('Keranjang Anda kosong!'); window.location.href='shop.php';</script>";
    exit;
}

$total_belanja = 0;
$items = [];
while($row = mysqli_fetch_assoc($queryKeranjang)) {
    $total_belanja += $row['subtotal'];
    $items[] = $row;
}

// PROSES EKSEKUSI CHECKOUT
if(isset($_POST['proses_checkout'])) {
    $nama_pemesan = mysqli_real_escape_string($conn, $_POST['nama_pemesan']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $metode_bayar = mysqli_real_escape_string($conn, $_POST['metode_bayar']);
    
    // Generate Kode Pesanan
    $kode_pesanan = 'PRSC-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 5));

    // Masukkan ke pesanan (Status: pending, tenggat belum di-set sampai mereka upload bukti)
    $insertPesanan = "INSERT INTO pesanan (kode_pesanan, user_id, nama_pemesan, no_hp, total_harga, status, metode_bayar) 
                      VALUES ('$kode_pesanan', $user_id, '$nama_pemesan', '$no_hp', $total_belanja, 'pending', '$metode_bayar')";
    
    if(mysqli_query($conn, $insertPesanan)) {
        $pesanan_id = mysqli_insert_id($conn);

        // Pindahkan ke detail_pesanan (sanitasi dan handle NULL dengan aman)
        foreach($items as $item) {
            $nama_parfum = ($item['tipe'] == 'katalog') ? mysqli_real_escape_string($conn, $item['nama_katalog']) : mysqli_real_escape_string($conn, $item['nama_custom']);
            $tipe = ($item['tipe'] == 'katalog') ? 'collection' : 'custom';

            // numeric fields: set to integer or literal NULL
            $id_ket = !empty($item['id_ketahanan']) ? intval($item['id_ketahanan']) : 'NULL';
            $id_uk = !empty($item['id_ukuran']) ? intval($item['id_ukuran']) : 'NULL';
            $produk_id_val = !empty($item['id_produk']) ? intval($item['id_produk']) : 'NULL';

            // notes: collect and escape; store as comma-separated string or NULL
            $notes_dipilih = [];
            if(!empty($item['id_notes_top'])) $notes_dipilih[] = intval($item['id_notes_top']);
            if(!empty($item['id_notes_middle'])) $notes_dipilih[] = intval($item['id_notes_middle']);
            if(!empty($item['id_notes_base'])) $notes_dipilih[] = intval($item['id_notes_base']);
            if(empty($notes_dipilih)) {
                $notes_string = 'NULL';
            } else {
                $notes_escaped = mysqli_real_escape_string($conn, implode(',', $notes_dipilih));
                $notes_string = "'" . $notes_escaped . "'";
            }

            $jumlah = intval($item['jumlah']);
            $subtotal_item = floatval($item['subtotal']);

            $insertDetail = "INSERT INTO detail_pesanan (pesanan_id, produk_id, nama_parfum, tipe, id_ketahanan, id_ukuran, notes_dipilih, jumlah, subtotal, status_racik) ";
            $insertDetail .= "VALUES ($pesanan_id, $produk_id_val, '" . $nama_parfum . "', '" . $tipe . "', $id_ket, $id_uk, $notes_string, $jumlah, $subtotal_item, 'menunggu')";
            if(!mysqli_query($conn, $insertDetail)) {
                die('Gagal menyimpan detail pesanan: ' . mysqli_error($conn));
            }
        }

        // Hapus Keranjang
        mysqli_query($conn, "DELETE FROM keranjang WHERE id_pelanggan = $id_pelanggan");

        // Lempar ke Halaman Payment
        header("Location: payment.php?id=$pesanan_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Checkout</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --navy: #2F4156; --teal: #567C8D; --sky-blue: #C8D9E6; --beige: #F5EFEB; --white: #FFFFFF; --light-bg: #F8FAF9; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--light-bg); color: var(--navy); }
        nav { background: var(--white); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 40px; }
        .logo { font-size: 24px; font-weight: 800; color: var(--navy); text-decoration: none; }
        .btn-back { display: flex; align-items: center; gap: 8px; color: var(--teal); font-weight: 700; text-decoration: none; }
        .container { max-width: 1000px; margin: 0 auto 100px; padding: 0 20px; display: flex; gap: 40px; }
        .form-section { flex: 2; background: var(--white); padding: 40px; border-radius: 32px; border: 1px solid var(--sky-blue); }
        .section-title { font-size: 24px; font-weight: 800; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 700; margin-bottom: 8px; color: var(--teal); }
        .form-input { width: 100%; padding: 15px; border: 1.5px solid var(--sky-blue); border-radius: 12px; font-family: inherit; font-size: 15px; }
        .form-input:focus { outline: none; border-color: var(--navy); }
        .payment-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .pay-card { position: relative; cursor: pointer; }
        .pay-card input { position: absolute; opacity: 0; cursor: pointer; }
        .pay-box { padding: 20px; border: 1.5px solid var(--sky-blue); border-radius: 16px; text-align: center; font-weight: 700; transition: 0.3s; }
        .pay-card input:checked ~ .pay-box { border-color: var(--navy); background: rgba(200, 217, 230, 0.2); box-shadow: 0 0 0 1px var(--navy); }
        .summary-section { flex: 1; background: var(--navy); color: var(--white); padding: 35px; border-radius: 32px; position: sticky; top: 40px; height: fit-content; }
        .sum-item { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sum-item-name { font-size: 15px; font-weight: 700; margin-bottom: 5px; }
        .sum-item-price { font-size: 14px; color: var(--sky-blue); }
        .sum-total { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; font-size: 18px; font-weight: 800; }
        .sum-total span:last-child { font-size: 24px; color: var(--beige); }
        .btn-submit { width: 100%; padding: 18px; background: var(--teal); color: var(--white); border: none; border-radius: 16px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 30px; }
        .btn-submit:hover { background: var(--beige); color: var(--navy); }
        @media(max-width: 768px) { .container { flex-direction: column; } }
    </style>
</head>
<body>

    <nav>
        <a href="index.php" class="logo">PERSCENTS.</a>
        <a href="keranjang.php" class="btn-back">Kembali</a>
    </nav>

    <form action="" method="POST" class="container">
        <div class="form-section">
            <h2 class="section-title">Detail Pengiriman</h2>
            <div class="form-group">
                <label class="form-label">Nama Penerima</label>
                <input type="text" name="nama_pemesan" class="form-input" value="<?= htmlspecialchars($pelanggan['nama_lengkap']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor WhatsApp</label>
                <input type="text" name="no_hp" class="form-input" value="<?= htmlspecialchars($pelanggan['no_hp']); ?>" required>
            </div>

            <h2 class="section-title" style="margin-top: 40px;">Pilih Metode Pembayaran</h2>
            <div class="payment-grid">
                <label class="pay-card">
                    <input type="radio" name="metode_bayar" value="QRIS" checked>
                    <div class="pay-box">QRIS (Gopay, OVO, Dana)</div>
                </label>
                <label class="pay-card">
                    <input type="radio" name="metode_bayar" value="Virtual Account">
                    <div class="pay-box">Virtual Account Bank</div>
                </label>
            </div>
        </div>

        <div class="summary-section">
            <h2 class="section-title" style="color: var(--white); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">Ringkasan</h2>
            <div style="max-height: 300px; overflow-y: auto; padding-right: 10px;">
                <?php foreach($items as $itm): ?>
                    <?php $nama_tampil = ($itm['tipe'] == 'katalog') ? $itm['nama_katalog'] : $itm['nama_custom']; ?>
                    <div class="sum-item">
                        <div class="sum-item-name"><?= htmlspecialchars($nama_tampil); ?></div>
                        <div class="sum-item-price"><?= $itm['jumlah']; ?>x - Rp <?= number_format($itm['subtotal'], 0, ',', '.'); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="sum-total">
                <span>Total Bayar</span>
                <span>Rp <?= number_format($total_belanja, 0, ',', '.'); ?></span>
            </div>
            <button type="submit" name="proses_checkout" class="btn-submit">Selesaikan Pesanan</button>
        </div>
    </form>

</body>
</html>

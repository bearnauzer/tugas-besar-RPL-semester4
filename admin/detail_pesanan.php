<?php
require_once '../config/cek_akses.php';
require_once '../config/koneksi.php';

$id_pesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (isset($_POST['update_status_pesanan'])) {
    $status_baru = mysqli_real_escape_string($conn, $_POST['status_pesanan']);
    $cekStatus = mysqli_query($conn, "SELECT status FROM pesanan WHERE id = $id_pesanan");
    $data_lama = mysqli_fetch_assoc($cekStatus);
    $status_lama = $data_lama ? $data_lama['status'] : '';
    mysqli_query($conn, "UPDATE pesanan SET status = '$status_baru' WHERE id = $id_pesanan");

    if ($status_baru == 'lunas' && $status_lama != 'lunas') {
        $queryStok = mysqli_query($conn, "SELECT produk_id, jumlah FROM detail_pesanan WHERE pesanan_id = $id_pesanan AND produk_id IS NOT NULL");
        if ($queryStok && mysqli_num_rows($queryStok) > 0) {
            while ($item = mysqli_fetch_assoc($queryStok)) {
                $id_produk = $item['produk_id'];
                $jumlah_beli = $item['jumlah'];
                mysqli_query($conn, "UPDATE produk_collection SET stok = stok - $jumlah_beli WHERE id = '$id_produk'");
            }
        }
    }

    if (($status_baru == 'dibatalkan' || $status_baru == 'batal') && $status_lama == 'lunas') {
        $queryStok = mysqli_query($conn, "SELECT produk_id, jumlah FROM detail_pesanan WHERE pesanan_id = $id_pesanan AND produk_id IS NOT NULL");
        if ($queryStok && mysqli_num_rows($queryStok) > 0) {
            while ($item = mysqli_fetch_assoc($queryStok)) {
                $id_produk = $item['produk_id'];
                $jumlah_beli = $item['jumlah'];
                mysqli_query($conn, "UPDATE produk_collection SET stok = stok + $jumlah_beli WHERE id = '$id_produk'");
            }
        }
    }

    echo "<script>alert('Status Pembayaran berhasil diperbarui dan Stok disesuaikan!'); window.location.href='detail_pesanan.php?id=$id_pesanan';</script>";
    exit;
}

$queryPesanan = mysqli_query($conn, "SELECT * FROM pesanan WHERE id = $id_pesanan");
$pesanan = mysqli_fetch_assoc($queryPesanan);

if (!$pesanan) {
    echo "<script>alert('Data pesanan tidak ditemukan!'); window.location.href='pendapatan.php';</script>";
    exit;
}

$queryDetail = mysqli_query($conn, "
    SELECT dp.*, pc.foto as foto_collection, mk.nama as nama_ketahanan, mu.nama as nama_ukuran, mu.ml
    FROM detail_pesanan dp 
    LEFT JOIN produk_collection pc ON dp.produk_id = pc.id 
    LEFT JOIN mst_ketahanan mk ON dp.id_ketahanan = mk.id
    LEFT JOIN mst_ukuran mu ON dp.id_ukuran = mu.id
    WHERE dp.pesanan_id = $id_pesanan
");

$badge_class = "badge-warning";
if($pesanan['status'] == 'lunas') $badge_class = "badge-success";
if($pesanan['status'] == 'dibatalkan' || $pesanan['status'] == 'batal') $badge_class = "badge-danger";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - PERSCENTS</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #F4F7FE; color: #2B3674; display: flex; font-size: 14px; }
        .sidebar { width: 260px; background: #ffffff; height: 100vh; position: fixed; left: 0; top: 0; box-shadow: 4px 0 20px rgba(0,0,0,0.03); display: flex; flex-direction: column; padding: 24px 20px; z-index: 100; }
        .sidebar-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 40px; padding: 0 10px; }
        .sidebar-logo img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #F4F7FE; padding: 4px; }
        .sidebar-logo h2 { font-size: 20px; font-weight: 700; color: #2B3674; letter-spacing: 0.5px; }
        .sidebar-menu { list-style: none; display: flex; flex-direction: column; gap: 8px; flex-grow: 1; }
        .sidebar-menu a { display: flex; align-items: center; gap: 14px; padding: 14px 18px; color: #A3AED0; text-decoration: none; font-weight: 500; border-radius: 12px; transition: all 0.3s ease; }
        .sidebar-menu a i { font-size: 18px; width: 24px; text-align: center; }
        .sidebar-menu a:hover { background: #F4F7FE; color: #5A738E; }
        .sidebar-menu a.active { background: #5A738E; color: #ffffff; box-shadow: 0 4px 12px rgba(90, 115, 142, 0.3); }
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); min-height: 100vh; padding: 30px 40px; display: flex; flex-direction: column; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .topbar .page-title { font-size: 26px; font-weight: 700; color: #2B3674; }
        .table-responsive { width: 100%; overflow-x: auto; }
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .data-table th { color: #A3AED0; font-weight: 500; font-size: 13px; text-transform: uppercase; padding: 16px; border-bottom: 1px solid #E9EDF7; text-align: left; }
        .data-table td { padding: 16px; font-size: 14px; color: #2B3674; font-weight: 500; border-bottom: 1px solid #F4F7FE; vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #F8FAFC; }
        .badge { padding: 6px 12px; border-radius: 30px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; text-transform: capitalize; }
        .badge-success { background: #E5F8ED; color: #05CD99; }
        .badge-warning { background: #FFF4E5; color: #FFB547; }
        .badge-danger { background: #FEECEE; color: #EE5D50; }
        .badge-blue { background: #E0F2FE; color: #0284C7; }
        .btn-primary { background: #5A738E; color: white; padding: 10px 20px; border-radius: 12px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; transition: 0.3s; border: none; cursor: pointer; }
        .btn-primary:hover { background: #495e75; box-shadow: 0 4px 12px rgba(90, 115, 142, 0.3); transform: translateY(-2px); }
        .form-control { padding: 12px 16px; border: 2px solid #E9EDF7; border-radius: 12px; font-size: 14px; color: #2B3674; outline: none; transition: 0.3s; background: white; }
        .form-control:focus { border-color: #5A738E; }
        .prod-info { display: flex; align-items: center; gap: 12px; }
        .prod-thumb { width: 44px; height: 44px; border-radius: 10px; object-fit: cover; background: #F4F7FE; padding: 2px; }
        .invoice-card { max-width: 950px; margin: 0 auto; background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); width: 100%; }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px dashed #E9EDF7; padding-bottom: 24px; margin-bottom: 24px; }
        .invoice-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .info-box { background: #F8FAFC; padding: 20px; border-radius: 16px; border: 1px solid #E9EDF7; }
        .info-box h4 { font-size: 12px; color: #A3AED0; text-transform: uppercase; margin-bottom: 12px; font-weight: 600; }
        .invoice-total { display: flex; justify-content: flex-end; margin-top: 30px; background: #F8FAFC; padding: 24px; border-radius: 16px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; color: #5A738E; width: 300px; }
        .total-row.grand { font-size: 20px; font-weight: 700; color: #2B3674; border-top: 2px solid #E9EDF7; padding-top: 12px; margin-top: 4px; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="<?= $base_url ?? '..'; ?>/assets/Logo_Perscents.png" alt="Logo" onerror="this.src='../assets/perscents_kotak.png'">
            <h2>Perscents</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="produk.php"><i class="fa-solid fa-box-open"></i> Produk & Stok</a></li>
            <li><a href="variant_management.php"><i class="fa-solid fa-layer-group"></i> Varian Custom</a></li>
            <li><a href="pendapatan.php" class="active"><i class="fa-solid fa-wallet"></i> Pendapatan</a></li>
            <li style="margin-top: auto;"><a href="logout_admin.php" style="color: #EE5D50;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a></li>
        </ul>
    </aside>

    <main class="main-wrapper">
        <header class="topbar">
            <h1 class="page-title">Verifikasi Pesanan</h1>
            <a href="pendapatan.php" class="btn-primary" style="background:#E9EDF7; color:#5A738E;"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </header>

        <div class="invoice-card">
            <div class="invoice-header">
                <div>
                    <h2 style="font-size: 28px; color: #2B3674; font-weight: 800; margin-bottom: 4px;">Order #<?= htmlspecialchars($pesanan['kode_pesanan']); ?></h2>
                    <p style="color: #A3AED0; font-size: 14px;"><i class="fa-regular fa-calendar"></i> <?= date('d F Y, H:i', strtotime($pesanan['created_at'])); ?> WIB</p>
                </div>
                <div>
                    <span class="badge <?= $badge_class; ?>" style="font-size: 16px; padding: 8px 20px;"><?= ucfirst($pesanan['status']); ?></span>
                </div>
            </div>

            <div class="invoice-info-grid">
                <div class="info-box">
                    <h4>Informasi Pelanggan</h4>
                    <p style="font-size: 15px; font-weight: 600; color: #2B3674; margin-bottom: 4px;"><i class="fa-regular fa-user" style="margin-right:5px;"></i> <?= htmlspecialchars($pesanan['nama_pemesan']); ?></p>
                    <p style="font-size: 13px; color: #5A738E;"><i class="fa-brands fa-whatsapp" style="margin-right:5px;"></i> <?= htmlspecialchars($pesanan['no_hp']); ?></p>
                </div>
                
                <div class="info-box">
                    <h4>Bukti Transfer / Resi</h4>
                    <?php if(!empty($pesanan['bukti_pembayaran'])): ?>
                        <p style="font-size: 13px; color: #05CD99; font-weight: 600; margin-bottom: 10px;"><i class="fa-solid fa-circle-check"></i> Bukti telah diunggah</p>
                        <a href="../<?= htmlspecialchars($pesanan['bukti_pembayaran']); ?>" target="_blank" class="btn-primary" style="background:#E0F2FE; color:#0284C7; padding: 8px 12px; font-size: 12px; width: 100%;"><i class="fa-solid fa-file-invoice"></i> Cek Gambar Resi</a>
                    <?php else: ?>
                        <p style="font-size: 13px; color: #EE5D50; font-weight: 600; margin-top: 5px;"><i class="fa-solid fa-circle-xmark"></i> Belum ada bukti transfer</p>
                    <?php endif; ?>
                </div>

                <div class="info-box">
                    <h4>Verifikasi Pembayaran</h4>
                    <form method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                        <select name="status_pesanan" class="form-control" style="padding: 8px 12px; font-size: 13px;">
                            <option value="pending" <?= ($pesanan['status'] == 'pending') ? 'selected' : ''; ?>>Pending (Menunggu)</option>
                            <option value="lunas" <?= ($pesanan['status'] == 'lunas') ? 'selected' : ''; ?>>Lunas (Diterima)</option>
                            <option value="dibatalkan" <?= ($pesanan['status'] == 'dibatalkan' || $pesanan['status'] == 'batal') ? 'selected' : ''; ?>>Dibatalkan (Palsu/Expired)</option>
                        </select>
                        <button type="submit" name="update_status_pesanan" class="btn-primary" style="padding: 8px; font-size: 13px;">Simpan Status</button>
                    </form>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rincian Item Parfum</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Subtotal</th>
                        <th style="text-align: right; width: 160px;">Status Racik (Lab)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal_semua = 0;
                    if($queryDetail && mysqli_num_rows($queryDetail) > 0) {
                        while($item = mysqli_fetch_assoc($queryDetail)) { 
                            $subtotal_semua += $item['subtotal'];
                            $path_foto = ($item['tipe'] == 'collection' && !empty($item['foto_collection'])) ? $item['foto_collection'] : 'assets/perscents_kotak.png';
                            $tipe_badge = ($item['tipe'] == 'custom') ? "<span class='badge' style='background:#F3E8FF; color:#7E22CE;'>Custom</span>" : "<span class='badge badge-blue'>Katalog</span>";
                            
                            $bg_racik = "background: #FFF4E5; color: #FFB547;"; 
                            if(strtolower($item['status_racik']) == 'diracik') $bg_racik = "background: #E0F2FE; color: #0284C7;"; // Biru
                            if(strtolower($item['status_racik']) == 'selesai') $bg_racik = "background: #E5F8ED; color: #05CD99;"; // Hijau
                    ?>
                    <tr>
                        <td>
                            <div class="prod-info">
                                <img src="<?= $base_url ?? '..'; ?>/<?= htmlspecialchars($path_foto); ?>" class="prod-thumb" onerror="this.src='../assets/perscents_kotak.png'">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span style="font-weight: 600; color: #2B3674; font-size: 15px;"><?= htmlspecialchars($item['nama_parfum']); ?> <?= $tipe_badge; ?></span>
                                    <div style="font-size: 13px; color: #A3AED0;">
                                        <?= htmlspecialchars($item['nama_ukuran'] ?? '-'); ?> (<?= htmlspecialchars($item['ml'] ?? '0'); ?> ml) &bull; <?= htmlspecialchars($item['nama_ketahanan'] ?? '-'); ?>
                                        <?php if($item['tipe'] == 'custom' && !empty($item['notes_dipilih'])): ?>
                                            <br><span style="color: #7E22CE;"><i class="fa-solid fa-flask"></i> Notes: <?= htmlspecialchars($item['notes_dipilih']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align: center; font-weight: 700; font-size: 16px;"><?= $item['jumlah']; ?>x</td>
                        <td style="text-align: right; font-weight: 600;">Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></td>
                        
                        <td style="text-align: right;">
                            <span class="badge" style="<?= $bg_racik; ?> border-radius: 8px; font-size: 12px; padding: 6px 12px;">
                                <?= ucfirst($item['status_racik']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php } } else { echo "<tr><td colspan='4' style='text-align:center;'>Detail tidak ditemukan.</td></tr>"; } ?>
                </tbody>
            </table>

            <div class="invoice-total">
                <div>
                    <div class="total-row"><span>Subtotal Item:</span><span>Rp <?= number_format($subtotal_semua, 0, ',', '.'); ?></span></div>
                    <?php 
                        $biaya_lain = $pesanan['total_harga'] - $subtotal_semua;
                        if($biaya_lain > 0) { 
                    ?>
                    <div class="total-row"><span>Biaya Tambahan:</span><span>Rp <?= number_format($biaya_lain, 0, ',', '.'); ?></span></div>
                    <?php } ?>
                    <div class="total-row grand"><span>Total Bayar:</span><span>Rp <?= number_format($pesanan['total_harga'], 0, ',', '.'); ?></span></div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
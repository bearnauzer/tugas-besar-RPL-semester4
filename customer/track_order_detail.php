<?php
session_start();
require_once '../config/koneksi.php';

if(!isset($_SESSION['pelanggan_id'])) {
    header("Location: login_pelanggan.php");
    exit;
}

$id_pelanggan = (int)$_SESSION['pelanggan_id'];
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if(!$user_id) {
    $pelangganData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email FROM pelanggan WHERE id = $id_pelanggan"));
    if($pelangganData) {
        $email_pelanggan = mysqli_real_escape_string($conn, $pelangganData['email']);
        $queryUser = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_pelanggan' LIMIT 1");
        if(mysqli_num_rows($queryUser) > 0) {
            $userData = mysqli_fetch_assoc($queryUser);
            $user_id = (int)$userData['id'];
            $_SESSION['user_id'] = $user_id;
        }
    }
}

function ensure_ulasan_order_columns($conn) {
    $columns = [
        'pesanan_id' => "ALTER TABLE ulasan_pelanggan ADD pesanan_id INT(11) NULL AFTER id_pelanggan",
        'detail_pesanan_id' => "ALTER TABLE ulasan_pelanggan ADD detail_pesanan_id INT(11) NULL AFTER pesanan_id",
        'produk_id' => "ALTER TABLE ulasan_pelanggan ADD produk_id INT(11) NULL AFTER detail_pesanan_id",
        'tipe_produk' => "ALTER TABLE ulasan_pelanggan ADD tipe_produk VARCHAR(30) NULL AFTER produk_id",
        'nama_produk' => "ALTER TABLE ulasan_pelanggan ADD nama_produk VARCHAR(150) NULL AFTER tipe_produk"
    ];

    foreach($columns as $column => $alterSql) {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM ulasan_pelanggan LIKE '$column'");
        if($check && mysqli_num_rows($check) === 0) {
            mysqli_query($conn, $alterSql);
        }
    }
}
ensure_ulasan_order_columns($conn);
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_taken') {
    $kode = mysqli_real_escape_string($conn, $_POST['kode'] ?? '');
    if($kode) {
        mysqli_query($conn, "UPDATE pesanan SET status = 'selesai' WHERE kode_pesanan = '$kode' AND user_id = $user_id AND status_pengambilan = 'diambil'");
    }
    header("Location: track_order_detail.php?kode=$kode");
    exit;
}

$kode = isset($_GET['kode']) ? mysqli_real_escape_string($conn, $_GET['kode']) : '';
if(!$kode) {
    header('Location: track_order.php');
    exit;
}

$q = mysqli_query($conn, "SELECT * FROM pesanan WHERE kode_pesanan = '$kode' AND user_id = $user_id LIMIT 1");
if(mysqli_num_rows($q) === 0) {
    header('Location: track_order.php');
    exit;
}
$pesanan = mysqli_fetch_assoc($q);

$qDetail = mysqli_query($conn, "
    SELECT
        dp.*,
        mk.nama AS nama_ketahanan,
        mk.durasi AS durasi_ketahanan,
        mu.nama AS nama_ukuran,
        mu.ml AS ml_ukuran
    FROM detail_pesanan dp
    LEFT JOIN mst_ketahanan mk ON dp.id_ketahanan = mk.id
    LEFT JOIN mst_ukuran mu ON dp.id_ukuran = mu.id
    WHERE dp.pesanan_id = " . (int)$pesanan['id'] . "
");

$items = [];
$semua_selesai = true;
$ada_diracik = false;
$ada_selesai = false;
if($qDetail && mysqli_num_rows($qDetail) > 0) {
    while($row = mysqli_fetch_assoc($qDetail)) {
        $items[] = $row;
        if($row['status_racik'] !== 'selesai') {
            $semua_selesai = false;
        }
        if($row['status_racik'] === 'diracik') {
            $ada_diracik = true;
        }
        if($row['status_racik'] === 'selesai') {
            $ada_selesai = true;
        }
    }
} else {
    $semua_selesai = false;
}

if(count($items) === 0) {
    header('Location: track_order.php?invalid=detail');
    exit;
}

$db_status = strtolower(trim($pesanan['status']));
$db_pengambilan = $pesanan['status_pengambilan'] ?? 'belum_diambil';

$currentStep = 1;
if ($db_status === 'selesai' || $db_pengambilan === 'diambil') {
    $currentStep = 4; 
} elseif ($semua_selesai) {
    $currentStep = 3; 
} elseif ($ada_diracik || $ada_selesai) {
    $currentStep = 2; 
}

$isSelesaiTutupBuku = ($db_status === 'selesai'); 

function get_status_ui($currentStep, $db_status, $db_pengambilan) {
    if ($db_status === 'selesai') {
        return [
            'title' => 'Pesanan Selesai!',
            'note' => 'Terima kasih telah berbelanja di PERSCENTS. Semoga Anda menyukai racikan kami.'
        ];
    }
    if ($db_pengambilan === 'diambil') {
        return [
            'title' => 'Pesanan Telah Diserahkan',
            'note' => 'Peracik telah menyerahkan pesanan Anda. Silakan klik "Konfirmasi Diterima" di bawah untuk menyelesaikan pesanan.'
        ];
    }
    if ($currentStep === 3) {
        return [
            'title' => 'Pesanan Siap Diambil!',
            'note' => 'Racikan Anda telah selesai dibuat. Silakan menuju PERSCENTS Lab untuk pengambilan.'
        ];
    }
    if ($currentStep === 2) {
        return [
            'title' => 'Pesanan Sedang Diracik',
            'note' => 'Ahli parfum kami sedang meracik formula pesanan Anda di lab.'
        ];
    }
    return [
        'title' => 'Pembayaran Diterima',
        'note' => 'Pesanan Anda masuk ke dalam antrean lab kami.'
    ];
}

$statusUI = get_status_ui($currentStep, $db_status, $db_pengambilan);

function receipt_meta($item) {
    $parts = [];
    $ukuran = trim((string)($item['nama_ukuran'] ?? ''));
    $ml = trim((string)($item['ml_ukuran'] ?? ''));
    $ketahanan = trim((string)($item['nama_ketahanan'] ?? ''));

    if($ukuran !== '' && $ml !== '') {
        $parts[] = $ukuran . ' (' . $ml . 'ml)';
    } elseif($ukuran !== '') {
        $parts[] = $ukuran;
    } elseif($ml !== '') {
        $parts[] = $ml . 'ml';
    }

    if($ketahanan !== '') {
        $parts[] = $ketahanan;
    }

    return implode(' &bull; ', $parts);
}

$namaPelanggan = $pesanan['nama_pemesan'] ?? '-';
$noHp = $pesanan['no_hp'] ?? '-';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_rating' && $isSelesaiTutupBuku) {
    $ratings = $_POST['rating'] ?? [];
    $komentars = $_POST['komentar'] ?? [];
    $itemsById = [];
    foreach($items as $item) {
        $itemsById[(int)$item['id']] = $item;
    }

    foreach($ratings as $detailId => $ratingValue) {
        $detailId = (int)$detailId;
        if(!isset($itemsById[$detailId])) continue;

        $rating = max(1, min(5, (int)$ratingValue));
        $komentar = mysqli_real_escape_string($conn, trim($komentars[$detailId] ?? ''));
        if($rating < 1 || $komentar === '') continue;

        $item = $itemsById[$detailId];
        $produkId = !empty($item['produk_id']) ? (int)$item['produk_id'] : 'NULL';
        $tipeProduk = mysqli_real_escape_string($conn, $item['tipe'] ?? '');
        $namaProduk = mysqli_real_escape_string($conn, $item['nama_parfum'] ?? 'Parfum');
        $namaReviewer = mysqli_real_escape_string($conn, $namaPelanggan);
        $kategoriPembeli = mysqli_real_escape_string($conn, ucfirst($tipeProduk ?: 'customer'));
        $pesananId = (int)$pesanan['id'];

        $existing = mysqli_query($conn, "SELECT id FROM ulasan_pelanggan WHERE id_pelanggan = $id_pelanggan AND detail_pesanan_id = $detailId LIMIT 1");
        if($existing && mysqli_num_rows($existing) > 0) {
            $existingRow = mysqli_fetch_assoc($existing);
            $ulasanId = (int)$existingRow['id'];
            mysqli_query($conn, "UPDATE ulasan_pelanggan SET rating = $rating, komentar = '$komentar', nama_reviewer = '$namaReviewer', kategori_pembeli = '$kategoriPembeli', pesanan_id = $pesananId, produk_id = $produkId, tipe_produk = '$tipeProduk', nama_produk = '$namaProduk' WHERE id = $ulasanId");
        } else {
            mysqli_query($conn, "INSERT INTO ulasan_pelanggan (id_pelanggan, pesanan_id, detail_pesanan_id, produk_id, tipe_produk, nama_produk, nama_reviewer, kategori_pembeli, rating, komentar) VALUES ($id_pelanggan, $pesananId, $detailId, $produkId, '$tipeProduk', '$namaProduk', '$namaReviewer', '$kategoriPembeli', $rating, '$komentar')");
        }
    }
    header("Location: track_order_detail.php?kode=" . rawurlencode($pesanan['kode_pesanan']) . "&rated=1");
    exit;
}

$ulasanByDetail = [];
$qUlasan = mysqli_query($conn, "SELECT detail_pesanan_id, rating, komentar FROM ulasan_pelanggan WHERE id_pelanggan = $id_pelanggan AND pesanan_id = " . (int)$pesanan['id']);
if($qUlasan) {
    while($ulasan = mysqli_fetch_assoc($qUlasan)) {
        $ulasanByDetail[(int)$ulasan['detail_pesanan_id']] = $ulasan;
    }
}

$semuaSudahRating = count($items) > 0;
foreach($items as $item) {
    if(!isset($ulasanByDetail[(int)$item['id']])) {
        $semuaSudahRating = false;
        break;
    }
}
$modeEditRating = isset($_GET['edit_rating']) && $_GET['edit_rating'] === '1';

$bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tanggal = strtotime($pesanan['created_at']);
$tanggalPesan = date('d', $tanggal) . ' ' . $bulan[(int)date('m', $tanggal) - 1] . ' ' . date('Y', $tanggal) . ' pukul ' . date('H:i', $tanggal) . ' WIB';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Status Pesanan - <?= htmlspecialchars($pesanan['kode_pesanan']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #2F4156; --teal: #567C8D; --sky: #C8D9E6; --light: #F8F9FA; --white: #FFFFFF;
            --success: #10b981; --success-bg: #dcfce7;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--light); color: #14304b; }
        a { color: inherit; text-decoration: none; }
        
        .page { max-width: 800px; margin: 34px auto 70px; padding: 0 20px; }
        .hero { text-align: center; margin-bottom: 28px; }
        .hero-icon { width: 58px; height: 58px; margin: 0 auto 16px; border-radius: 999px; background: var(--navy); color: #fff; display: grid; place-items: center; box-shadow: 0 12px 22px rgba(47,65,86,0.16); }
        .hero h1 { font-size: 28px; line-height: 1.1; margin-bottom: 8px; font-weight: 800; }
        .hero p { color: var(--teal); font-size: 14px; font-weight: 600; }
        
        .panel { background: var(--white); border: 1.5px solid var(--sky); border-radius: 20px; padding: 35px; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        
        .tracker { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; align-items: start; margin-bottom: 40px; position: relative; }
        .tracker-line-bg { position: absolute; top: 15px; left: 12%; right: 12%; height: 2px; background: var(--sky); z-index: 1; }
        .tracker-active-line { height: 100%; background: var(--navy); transition: width 0.5s ease; }
        .step { position: relative; z-index: 3; text-align: center; font-size: 11px; font-weight: 800; color: var(--teal); text-transform: uppercase; }
        .step.active { color: var(--navy); }
        .dot { width: 32px; height: 32px; border-radius: 999px; margin: 0 auto 10px; display: grid; place-items: center; background: #edf2f5; border: 4px solid var(--white); color: #7a8b97; transition: 0.3s; font-size: 14px; }
        .step.active .dot { background: var(--navy); color: #fff; border-color: var(--sky); }

        .status-box { background: var(--navy); color: #fff; border-radius: 16px; padding: 25px 30px; display: flex; gap: 20px; align-items: center; box-shadow: 0 15px 30px rgba(47,65,86,0.15); margin-bottom: 30px; }
        .status-box.success-mode { background: var(--success); }
        .status-box small { display: block; font-weight: 800; opacity: 0.88; margin-bottom: 5px; text-transform: uppercase; font-size: 12px; }
        .status-box h2 { font-size: 22px; line-height: 1.2; margin-bottom: 6px; }
        .status-box p { font-size: 13px; opacity: 0.9; line-height: 1.5; }
        .circle-icon { width: 40px; height: 40px; flex: 0 0 40px; border-radius: 999px; border: 3px solid rgba(255,255,255,0.7); display: grid; place-items: center; }
        
        .info h3, .receipt h3 { font-size: 16px; margin-bottom: 20px; color: var(--navy); font-weight: 800; }
        .info-row { display: grid; grid-template-columns: 150px 1fr; gap: 16px; font-size: 14px; margin-bottom: 14px; }
        .info-row span { color: var(--teal); font-weight: 600; }
        .info-row strong { text-align: right; color: var(--navy); font-weight: 800; }
        
        .receipt-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
        .download { color: var(--teal); font-size: 13px; font-weight: 800; display: inline-flex; gap: 7px; align-items: center; cursor: pointer; border: none; background: transparent; }
        .download:hover { color: var(--navy); }
        .item { display: grid; grid-template-columns: 1fr auto; gap: 18px; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px dashed var(--sky); }
        .item-name { font-size: 15px; font-weight: 800; margin-bottom: 6px; color: var(--navy); }
        .item-meta { color: var(--teal); font-size: 12px; font-weight: 600; line-height: 1.5; }
        .item-price { font-size: 15px; font-weight: 800; margin-top: 10px; color: var(--navy); }
        .qty { font-size: 14px; font-weight: 800; color: var(--teal); }
        .total { padding-top: 10px; display: flex; justify-content: space-between; font-size: 18px; font-weight: 900; color: var(--navy); }
        
        .location { background: var(--teal); color: #fff; border-radius: 16px; padding: 25px; margin-bottom: 24px; }
        .location-title { display: flex; align-items: center; gap: 10px; font-weight: 900; margin-bottom: 12px; font-size: 15px; }
        .location p { line-height: 1.6; font-size: 14px; font-weight: 600; opacity: 0.95; }
        
        .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .btn { border-radius: 12px; padding: 16px 20px; border: 1.5px solid var(--sky); display: inline-flex; align-items: center; justify-content: center; gap: 10px; font-size: 14px; font-weight: 800; cursor: pointer; transition: 0.3s; }
        .btn-dark { background: var(--navy); color: #fff; border-color: var(--navy); }
        .btn-dark:hover { background: var(--teal); border-color: var(--teal); }
        .btn-success { background: var(--success); color: #fff; border-color: var(--success); }
        .btn-success:hover { background: #059669; }
        
        .rating-panel h3 { font-size: 20px; margin-bottom: 8px; color: var(--navy); font-weight: 800; }
        .rating-panel > p { color: var(--teal); font-size: 14px; font-weight: 600; margin-bottom: 25px; }
        .rating-item { border-top: 1.5px solid var(--sky); padding-top: 20px; margin-top: 20px; }
        .rating-item:first-of-type { border-top: none; padding-top: 0; margin-top: 0; }
        .rating-item h4 { font-size: 16px; font-weight: 800; margin-bottom: 6px; color: var(--navy); }
        .rating-stars { display: inline-flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; margin: 10px 0 15px; }
        .rating-stars input { display: none; }
        .rating-stars label { font-size: 32px; color: #c8d9e6; cursor: pointer; line-height: 1; transition: 0.2s; }
        .rating-stars input:checked ~ label,
        .rating-stars label:hover,
        .rating-stars label:hover ~ label { color: #f5b83f; }
        .rating-comment { width: 100%; min-height: 90px; resize: vertical; border: 1.5px solid var(--sky); border-radius: 12px; padding: 15px; font-family: inherit; color: var(--navy); outline: none; font-weight: 600; font-size: 13px; }
        .rating-comment:focus { border-color: var(--teal); }
        .rating-submit { margin-top: 25px; width: 100%; }
        
        .rating-success { background: var(--success-bg); color: var(--success); border: 1px solid #bbf7d0; border-radius: 12px; padding: 15px; font-size: 14px; font-weight: 800; margin-bottom: 20px; }
        .rating-done-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; margin-bottom: 20px; }
        .rating-done-badge { display: inline-flex; align-items: center; gap: 8px; background: var(--success-bg); color: var(--success); border-radius: 999px; padding: 8px 16px; font-size: 13px; font-weight: 800; }
        .rating-summary-card { border: 1.5px solid var(--sky); border-radius: 16px; padding: 20px; margin-top: 15px; background: var(--light); }
        .rating-summary-card:first-of-type { margin-top: 0; }
        .rating-stars-readonly { color: #f5b83f; letter-spacing: 2px; font-size: 24px; margin: 10px 0; }
        .rating-comment-readonly { background: var(--white); border-radius: 12px; padding: 15px; color: var(--navy); font-size: 14px; font-weight: 600; line-height: 1.6; border: 1px solid var(--sky); }
        
        @media print {
            body * { visibility: hidden; }
            .receipt, .receipt * { visibility: visible; }
            .receipt { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; padding: 0; }
            .download, .tracker, .status-box, .location, .actions, .rating-panel, .hero { display: none !important; }
        }

        @media (max-width: 680px) {
            .tracker { gap: 5px; }
            .step { font-size: 9px; }
            .info-row { grid-template-columns: 1fr; gap: 4px; }
            .info-row strong { text-align: left; }
            .actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero">
            <div class="hero-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><path d="M3.3 7 12 12l8.7-5"></path><path d="M12 22V12"></path></svg>
            </div>
            <h1>Status Pesanan</h1>
            <p>Kode Pesanan: <?= htmlspecialchars($pesanan['kode_pesanan']); ?></p>
        </section>

        <section class="panel">
            <div class="tracker">
                <?php 
                    $steps = ['Dibayar', 'Diracik', 'Siap Diambil', 'Sudah Diambil']; 
                    $progress_width = (($currentStep - 1) / 3) * 100;
                ?>
                <div class="tracker-line-bg">
                    <div class="tracker-active-line" style="width: <?= $progress_width; ?>%;"></div>
                </div>
                
                <?php foreach($steps as $index => $label): ?>
                    <div class="step <?= ($currentStep >= $index + 1) ? 'active' : ''; ?>">
                        <div class="dot"><?= ($currentStep > $index + 1) ? '&#10003;' : ($index + 1); ?></div>
                        <?= $label; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="status-box <?= ($isSelesaiTutupBuku) ? 'success-mode' : ''; ?>">
                <div class="circle-icon">
                    <?php if($isSelesaiTutupBuku): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                    <?php endif; ?>
                </div>
                <div>
                    <small>Status Saat Ini</small>
                    <h2><?= $statusUI['title']; ?></h2>
                    <p><?= $statusUI['note']; ?></p>
                </div>
            </div>

            <div class="info">
                <h3>Informasi Pengambilan</h3>
                <div class="info-row"><span>Nama:</span><strong><?= htmlspecialchars($namaPelanggan); ?></strong></div>
                <div class="info-row"><span>Nomor HP:</span><strong><?= htmlspecialchars($noHp); ?></strong></div>
                <div class="info-row"><span>Tanggal Pesan:</span><strong><?= htmlspecialchars($tanggalPesan); ?></strong></div>
            </div>
        </section>

        <section class="panel receipt">
            <div class="receipt-head">
                <h3>Struk Digital</h3>
                <button onclick="window.print()" class="download">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Download PDF
                </button>
            </div>

            <div style="display: none; text-align: center; margin-bottom: 30px;" class="print-only">
                <h2 style="margin-bottom: 8px; color: var(--navy); font-weight: 800;">PERSCENTS.</h2>
                <p style="font-size: 14px; color: var(--teal); font-weight: 600;">Kode Pesanan: <?= htmlspecialchars($pesanan['kode_pesanan']); ?></p>
            </div>
            <style> @media print { .print-only { display: block !important; } } </style>

            <?php if(count($items) > 0): ?>
                <?php foreach($items as $item): ?>
                    <div class="item">
                        <div>
                            <div class="item-name"><?= htmlspecialchars($item['nama_parfum']); ?></div>
                            <div class="item-meta">
                                <?php if($item['tipe'] == 'custom') echo "Custom Perfume Blend<br>"; ?>
                                <?= receipt_meta($item); ?>
                            </div>
                            <div class="item-price">Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></div>
                        </div>
                        <div class="qty">x<?= (int)$item['jumlah']; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="total">
                <span>Total</span>
                <span>Rp <?= number_format($pesanan['total_harga'], 0, ',', '.'); ?></span>
            </div>
        </section>

        <section class="location">
            <div class="location-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 1 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                Lokasi Pengambilan
            </div>
            <p>PERSCENTS Lab (Itenas)<br>Jl. PHH. Mustofa No.23, Bandung<br>Buka: Senin - Sabtu, 10:00 - 17:00</p>
        </section>

        
        <?php if($isSelesaiTutupBuku): ?>
            <?php if(count($items) > 0): ?>
                <section class="panel rating-panel">
                    <?php if($semuaSudahRating && !$modeEditRating): ?>
                        <div class="rating-done-head">
                            <div>
                                <h3>Rating Anda</h3>
                                <p>Terima kasih, penilaian Anda sangat berharga bagi kami.</p>
                            </div>
                            <span class="rating-done-badge">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"></path></svg>
                                Sudah Dinilai
                            </span>
                        </div>
                        <?php foreach($items as $item): ?>
                            <?php
                                $detailId = (int)$item['id'];
                                $existingRating = (int)($ulasanByDetail[$detailId]['rating'] ?? 0);
                                $existingKomentar = $ulasanByDetail[$detailId]['komentar'] ?? '';
                            ?>
                            <div class="rating-summary-card">
                                <h4><?= htmlspecialchars($item['nama_parfum']); ?></h4>
                                <div class="item-meta" style="margin-bottom:5px; color:var(--teal); font-size:12px; font-weight:600;"><?= receipt_meta($item); ?></div>
                                <div class="rating-stars-readonly"><?= str_repeat('★', $existingRating) . str_repeat('☆', 5 - $existingRating); ?></div>
                                <div class="rating-comment-readonly"><?= nl2br(htmlspecialchars($existingKomentar)); ?></div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="actions" style="margin-top:25px;">
                            <a href="track_order_detail.php?kode=<?= rawurlencode($pesanan['kode_pesanan']); ?>&edit_rating=1" class="btn btn-dark">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                Edit Ulasan
                            </a>
                            <a href="track_order.php?tab=selesai" class="btn">Kembali ke Riwayat</a>
                        </div>
                    <?php else: ?>
                        <h3><?= $modeEditRating ? 'Edit Ulasan Anda' : 'Beri Ulasan Pesanan'; ?></h3>
                        <p>Nilai racikan parfum yang telah Anda terima.</p>
                        
                        <?php if(isset($_GET['rated'])): ?>
                            <div class="rating-success">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:text-bottom; margin-right:5px;"><path d="M20 6 9 17l-5-5"></path></svg>
                                Terima kasih, ulasan Anda berhasil disimpan.
                            </div>
                        <?php endif; ?>
                        
                        <form method="post">
                            <input type="hidden" name="action" value="submit_rating">
                            <?php foreach($items as $item): ?>
                                <?php
                                    $detailId = (int)$item['id'];
                                    $existingRating = (int)($ulasanByDetail[$detailId]['rating'] ?? 0);
                                    $existingKomentar = $ulasanByDetail[$detailId]['komentar'] ?? '';
                                ?>
                                <div class="rating-item">
                                    <h4><?= htmlspecialchars($item['nama_parfum']); ?></h4>
                                    <div class="item-meta" style="color:var(--teal); font-size:12px; font-weight:600;"><?= receipt_meta($item); ?></div>
                                    <div class="rating-stars">
                                        <?php for($star = 5; $star >= 1; $star--): ?>
                                            <input type="radio" id="rating-<?= $detailId; ?>-<?= $star; ?>" name="rating[<?= $detailId; ?>]" value="<?= $star; ?>" <?= ($existingRating === $star) ? 'checked' : ''; ?> required>
                                            <label for="rating-<?= $detailId; ?>-<?= $star; ?>">&#9733;</label>
                                        <?php endfor; ?>
                                    </div>
                                    <textarea class="rating-comment" name="komentar[<?= $detailId; ?>]" placeholder="Ceritakan pendapat Anda tentang aroma dan ketahanannya..." required><?= htmlspecialchars($existingKomentar); ?></textarea>
                                </div>
                            <?php endforeach; ?>
                            <button type="submit" class="btn btn-dark rating-submit">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                <?= $modeEditRating ? 'Simpan Perubahan' : 'Kirim Ulasan'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="actions">
                
                <?php if($db_pengambilan === 'diambil' && $db_status !== 'selesai'): ?>
                    <form method="post" style="grid-column: 1 / -1; margin: 0;">
                        <input type="hidden" name="kode" value="<?= htmlspecialchars($pesanan['kode_pesanan']); ?>">
                        <input type="hidden" name="action" value="mark_taken">
                        <button type="submit" class="btn btn-success" style="width: 100%; border: none;" onclick="return confirm('Apakah Anda yakin telah menerima pesanan ini dengan baik?');">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            Konfirmasi Pesanan Diterima
                        </button>
                    </form>
                    
                <?php elseif($semua_selesai): ?>
                    <div style="grid-column: 1 / -1; text-align: center; background: #e0f2fe; color: #0369a1; padding: 15px; border-radius: 12px; font-weight: 800; font-size: 13px; border: 1.5px dashed #bae6fd;">
                        Menunggu Peracik menyerahkan pesanan Anda...
                    </div>
                <?php else: ?>
                    <a href="index.php" class="btn btn-dark">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Ke Beranda
                    </a>
                <?php endif; ?>
                
                <?php if($db_pengambilan !== 'diambil'): ?>
                    <a href="track_order.php" class="btn">Riwayat Order</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
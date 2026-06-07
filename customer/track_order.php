<?php
session_start();
require_once '../config/koneksi.php';

if(!isset($_SESSION['pelanggan_id'])) {
    header("Location: login_pelanggan.php");
    exit;
}

$id_pelanggan = $_SESSION['pelanggan_id'];
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if(!$user_id) {
    $pelangganData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email FROM pelanggan WHERE id = $id_pelanggan"));
    if($pelangganData) {
        $email_pelanggan = mysqli_real_escape_string($conn, $pelangganData['email']);
        $queryUser = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_pelanggan' LIMIT 1");
        if(mysqli_num_rows($queryUser) > 0) {
            $userData = mysqli_fetch_assoc($queryUser);
            $user_id = $userData['id'];
            $_SESSION['user_id'] = $user_id;
        }
    }
}

function ensure_status_pengambilan_column($conn) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM pesanan LIKE 'status_pengambilan'");
    if($check && mysqli_num_rows($check) === 0) {
        mysqli_query($conn, "ALTER TABLE pesanan ADD status_pengambilan ENUM('belum_diambil','diambil') NOT NULL DEFAULT 'belum_diambil' AFTER status");
    }
}

ensure_status_pengambilan_column($conn);

$statusSudahDibayar = [
    'lunas',
    'paid',
    'diproses',
    'sedang diracik',
    'processing',
    'siap diambil',
    'ready',
    'diambil',
    'selesai',
    'completed'
];
$statusSudahDibayarSql = "'" . implode("','", array_map(function($status) use ($conn) {
    return mysqli_real_escape_string($conn, $status);
}, $statusSudahDibayar)) . "'";

$queryPesanan = mysqli_query($conn, "
    SELECT
        p.*,
        COUNT(dp.id) AS total_item,
        SUM(dp.status_racik = 'menunggu') AS total_menunggu,
        SUM(dp.status_racik = 'diracik') AS total_diracik,
        SUM(dp.status_racik = 'selesai') AS total_selesai
    FROM pesanan p
    JOIN detail_pesanan dp ON dp.pesanan_id = p.id
    WHERE p.user_id = $user_id
      AND LOWER(TRIM(p.status)) IN ($statusSudahDibayarSql)
    GROUP BY p.id
    ORDER BY p.created_at DESC
");

$pesananBelumSelesai = [];
$pesananSelesai = [];
if($queryPesanan) {
    while($rowPesanan = mysqli_fetch_assoc($queryPesanan)) {
        if(($rowPesanan['status_pengambilan'] ?? 'belum_diambil') === 'diambil') {
            $pesananSelesai[] = $rowPesanan;
        } else {
            $pesananBelumSelesai[] = $rowPesanan;
        }
    }
}

$tabAktif = (isset($_GET['tab']) && $_GET['tab'] === 'selesai') ? 'selesai' : 'belum_selesai';
$pesananAktif = ($tabAktif === 'selesai') ? $pesananSelesai : $pesananBelumSelesai;

function render_status_label($pesanan) {
    $status = strtolower(trim($pesanan['status'] ?? ''));
    $status_pengambilan = $pesanan['status_pengambilan'] ?? 'belum_diambil';
    $total_item = (int)($pesanan['total_item'] ?? 0);
    $total_menunggu = (int)($pesanan['total_menunggu'] ?? 0);
    $total_diracik = (int)($pesanan['total_diracik'] ?? 0);
    $total_selesai = (int)($pesanan['total_selesai'] ?? 0);

    if($status_pengambilan === 'diambil') return ['Selesai', 'status-success'];
    if($status === 'dibatalkan') return ['Dibatalkan', 'status-cancel'];
    if($status === 'pending') return ['Menunggu Pembayaran', 'status-pending'];

    if($total_item > 0 && $total_selesai === $total_item) return ['Siap Diambil', 'status-success'];
    if($total_diracik > 0 || $total_selesai > 0) return ['Sedang Diracik', 'status-pending'];
    if($total_item > 0 && $total_menunggu === $total_item) return ['Siap Diracik', 'status-pending'];

    if(in_array($status, ['lunas', 'paid'])) return ['Pembayaran Diterima', 'status-success'];
    if(in_array($status, ['sedang diracik', 'diproses', 'processing'])) return ['Sedang Diracik', 'status-pending'];
    if(in_array($status, ['siap diambil', 'ready'])) return ['Siap Diambil', 'status-success'];
    if(in_array($status, ['diambil', 'selesai', 'completed'])) return ['Selesai', 'status-success'];
    return ['Diproses', 'status-pending'];
}

function render_order_cards($orders) {
    $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    foreach($orders as $pesanan):
        $statusInfo = render_status_label($pesanan);
        $status_text = $statusInfo[0];
        $status_class = $statusInfo[1];
        $tanggal = strtotime($pesanan['created_at']);
        $kodeEncoded = rawurlencode($pesanan['kode_pesanan']);
?>
        <a href="track_order_detail.php?kode=<?= $kodeEncoded; ?>" class="order-link" style="text-decoration:none;color:inherit;">
            <div class="order-card">
                <div class="order-card-header">
                    <div>
                        <div class="order-code">Kode Pesanan</div>
                        <div class="order-code-value"><?= htmlspecialchars($pesanan['kode_pesanan']); ?></div>
                    </div>
                    <span class="status-pill <?= $status_class; ?>"><?= $status_text; ?></span>
                </div>
                <div class="order-card-body">
                    <div>
                        <div class="order-label">Tanggal</div>
                        <div class="order-value"><?= date('d', $tanggal) . ' ' . $bulan[(int)date('m', $tanggal) - 1] . ' ' . date('Y', $tanggal); ?></div>
                    </div>
                    <div>
                        <div class="order-label">Total Harga</div>
                        <div class="order-value">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
        </a>
<?php
    endforeach;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Status Pesanan</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #2F4156;
            --teal: #567C8D;
            --sky-blue: #C8D9E6;
            --beige: #F5EFEB;
            --white: #FFFFFF;
            --light-bg: #F8F9FA;
            --danger: #dc2626;
            --danger-bg: #fee2e2;
            --glass-bg: rgba(255, 255, 255, 0.4);
            --glass-border: rgba(255, 255, 255, 0.5);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--light-bg); color: var(--navy); }
        a { color: inherit; text-decoration: none; }

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

        .container { max-width: 900px; margin: 150px auto 100px; padding: 0 20px; }
        .status-tabs { width: fit-content; margin: 0 auto 42px; padding: 6px; display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.82); border: 1px solid var(--sky-blue); border-radius: 999px; box-shadow: 0 18px 35px rgba(47,65,86,0.08); }
        .status-tabs a { min-width: 150px; padding: 13px 22px; border-radius: 999px; color: var(--teal); text-align: center; font-size: 14px; font-weight: 900; transition: all 0.22s ease; }
        .status-tabs a:hover { color: var(--navy); background: rgba(200,217,230,0.3); }
        .status-tabs a.active { color: var(--white); background: var(--navy); box-shadow: 0 10px 22px rgba(47,65,86,0.18); }
        .notice { max-width: 720px; margin: -18px auto 28px; padding: 14px 18px; border-radius: 16px; background: rgba(86,124,141,0.12); color: var(--navy); font-size: 13px; font-weight: 800; text-align: center; border: 1px solid rgba(200,217,230,0.9); }

        .order-list { display: grid; gap: 18px; }
        .empty-section { background: rgba(255,255,255,0.7); border: 1px dashed var(--sky-blue); border-radius: 18px; padding: 24px; color: var(--teal); font-weight: 700; text-align: center; }
        .order-card { background: var(--white); border-radius: 24px; border: 1px solid rgba(200,217,230,0.8); padding: 28px; box-shadow: 0 25px 40px rgba(47,65,86,0.06); }
        .order-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; margin-bottom: 18px; }
        .order-code { font-size: 12px; text-transform: uppercase; letter-spacing: 0.15em; color: var(--teal); font-weight: 700; margin-bottom: 6px; }
        .order-code-value { font-size: 20px; font-weight: 800; color: var(--navy); }
        .order-card-body { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; padding-top: 10px; border-top: 1px solid rgba(200,217,230,0.8); }
        .order-label { font-size: 12px; text-transform: uppercase; font-weight: 700; color: var(--teal); margin-bottom: 6px; }
        .order-value { font-size: 16px; font-weight: 800; color: var(--navy); }
        .status-pill { display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .status-success { background: rgba(86,124,141,0.12); color: #205166; }
        .status-pending { background: rgba(200,217,230,0.34); color: #2f4156; }
        .status-cancel { background: rgba(220,38,38,0.12); color: #991b1b; }

        .card { background: var(--white); border-radius: 24px; border: 1.5px solid var(--sky-blue); padding: 35px; margin-bottom: 25px; box-shadow: 0 20px 40px rgba(47, 65, 86, 0.03); }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 16px 24px; border-radius: 14px; border: none; font-size: 15px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .btn-dark { background: var(--navy); color: var(--white); }
        .btn-dark:hover { opacity: 0.95; }

        @media (max-width: 720px) {
            .order-card-body { grid-template-columns: 1fr; }
            nav { width: calc(100% - 24px); padding: 14px 18px; gap: 12px; flex-wrap: wrap; }
            .nav-links { order: 3; width: 100%; justify-content: center; gap: 18px; }
        }
    </style>
</head>
<body>
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
    <div class="container">
        <div class="status-tabs" aria-label="Filter status pesanan">
            <a href="track_order.php?tab=belum_selesai" class="<?= $tabAktif === 'belum_selesai' ? 'active' : ''; ?>">Belum Selesai</a>
            <a href="track_order.php?tab=selesai" class="<?= $tabAktif === 'selesai' ? 'active' : ''; ?>">Selesai</a>
        </div>
        <?php if(isset($_GET['invalid']) && $_GET['invalid'] === 'detail'): ?>
            <div class="notice">Pesanan tersebut belum memiliki detail parfum, sehingga tidak bisa diproses oleh peracik.</div>
        <?php endif; ?>

        <?php if(count($pesananBelumSelesai) > 0 || count($pesananSelesai) > 0): ?>
            <div class="order-list">
                <?php if(count($pesananAktif) > 0): ?>
                    <?php render_order_cards($pesananAktif); ?>
                <?php else: ?>
                    <div class="empty-section"><?= $tabAktif === 'selesai' ? 'Belum ada pesanan yang selesai.' : 'Tidak ada pesanan yang belum selesai.'; ?></div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card" style="text-align:center;">
                <h2 style="margin-bottom:12px; color: var(--navy);">Belum ada pesanan yang dibayar</h2>
                <p style="color: var(--teal);">Anda belum memiliki pesanan yang lunas. Lakukan pemesanan dan pembayaran untuk melihat pesanan di sini.</p>
                <a href="shop.php" class="btn btn-dark" style="margin-top:20px; display:inline-block;">Belanja Sekarang</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
require_once '../config/cek_akses.php';
require_once '../config/koneksi.php';

// =========================================================================
// 1. STATISTIK KARTU ATAS
// =========================================================================
$qMenunggu = mysqli_query($conn, "SELECT COUNT(id) as total FROM detail_pesanan WHERE status_racik = 'menunggu'");
$jmlMenunggu = mysqli_fetch_assoc($qMenunggu)['total'];

$qDiracik = mysqli_query($conn, "SELECT COUNT(id) as total FROM detail_pesanan WHERE status_racik = 'diracik'");
$jmlDiracik = mysqli_fetch_assoc($qDiracik)['total'];

$qSelesai = mysqli_query($conn, "SELECT COUNT(id) as total FROM detail_pesanan WHERE status_racik = 'selesai'");
$jmlSelesai = mysqli_fetch_assoc($qSelesai)['total'];

$qSelesaiHariIni = mysqli_query($conn, "
    SELECT COUNT(dp.id) as total 
    FROM detail_pesanan dp
    JOIN pesanan p ON dp.pesanan_id = p.id
    WHERE dp.status_racik = 'selesai' AND DATE(p.created_at) = CURDATE()
");
$jmlSelesaiHariIni = mysqli_fetch_assoc($qSelesaiHariIni)['total'] ?? 0;

// =========================================================================
// 2. LOGIKA FILTER & PAGINATION DAFTAR PESANAN
// =========================================================================
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'semua';

$where = "1=1";
// Pengecekan filter: pisahkan yang merujuk ke tabel detail_pesanan (dp) dan pesanan (p)
if (in_array($filter_status, ['menunggu', 'diracik', 'selesai'])) {
    $where .= " AND dp.status_racik = '$filter_status'";
} elseif ($filter_status === 'belum_diambil') {
    $where .= " AND p.status_pengambilan = 'belum_diambil'";
} elseif ($filter_status === 'diambil') {
    $where .= " AND p.status_pengambilan = 'diambil'";
}

$limit = 5;
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($halaman - 1) * $limit;

// Menambahkan JOIN pada query count agar filter dari tabel pesanan (p) dapat terbaca
$qCountData = mysqli_query($conn, "
    SELECT COUNT(dp.id) as total 
    FROM detail_pesanan dp 
    JOIN pesanan p ON dp.pesanan_id = p.id 
    WHERE $where
");
$total_data = mysqli_fetch_assoc($qCountData)['total'];
$total_halaman = ceil($total_data / $limit);

// Mengubah ORDER BY menjadi DESC agar data terbaru muncul duluan
$queryDaftarRacik = mysqli_query($conn, "
    SELECT 
        dp.*, 
        p.kode_pesanan, p.nama_pemesan, p.created_at, p.status_pengambilan,
        mk.nama as nama_ketahanan, mk.durasi,
        mu.nama as nama_ukuran, mu.ml
    FROM detail_pesanan dp
    JOIN pesanan p ON dp.pesanan_id = p.id
    LEFT JOIN mst_ketahanan mk ON dp.id_ketahanan = mk.id
    LEFT JOIN mst_ukuran mu ON dp.id_ukuran = mu.id
    WHERE $where
    ORDER BY p.created_at DESC 
    LIMIT $limit OFFSET $offset
");

$url_params = "&status=" . urlencode($filter_status);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peracik - PERSCENTS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; }

        /* LAYOUT UTAMA */
        .app-layout { display: flex; min-height: 100vh; }

        /* SIDEBAR KIRI */
        .sidebar { width: 260px; background-color: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; padding: 25px 20px; position: sticky; top: 0; height: 100vh; }
        
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 40px; padding-left: 5px; }
        .brand-logo { width: 40px; height: 40px; background-color: #5A738E; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .brand-logo img { width: 100%; height: 100%; object-fit: cover; }
        .brand-text h1 { font-size: 20px; color: #1e293b; font-weight: 800; letter-spacing: 0.5px; }
        .brand-text p { font-size: 11px; color: #64748b; margin-top: 2px; }

        .sidebar-nav { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: #64748b; font-size: 14px; font-weight: 600; transition: all 0.2s; }
        .sidebar-nav a:hover { background-color: #f1f5f9; color: #5A738E; }
        .sidebar-nav a.active { background-color: #5A738E; color: white; box-shadow: 0 4px 6px rgba(90, 115, 142, 0.25); }
        .sidebar-nav a.active svg { stroke: white; }
        
        .sidebar-nav .logout { margin-top: auto; color: #ef4444; }
        .sidebar-nav .logout:hover { background-color: #fee2e2; color: #dc2626; }
        .sidebar-nav .logout svg { stroke: currentColor; }

        /* KONTEN KANAN */
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        /* PAGE TITLE */
        .page-title { margin-bottom: 30px; }
        .page-title h2 { font-size: 24px; color: #1e293b; margin-bottom: 5px; font-weight: 700; }
        .page-title p { font-size: 14px; color: #64748b; }

        /* STAT CARDS */
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .stat-info span { display: block; font-size: 13px; color: #64748b; margin-top: 5px; font-weight: 500; }
        .stat-info h3 { font-size: 32px; color: #1e293b; font-weight: bold; }
        .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; justify-content: center; align-items: center; font-size: 22px; font-weight: bold; }
        .bg-blue { background-color: #eff6ff; color: #3b82f6; }
        .bg-yellow { background-color: #fefce8; color: #eab308; }
        .bg-green { background-color: #f0fdf4; color: #22c55e; }
        .bg-gray { background-color: #f1f5f9; color: #64748b; }

        /* FILTER TABS */
        .filter-container { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px 20px; display: flex; align-items: center; gap: 20px; margin-bottom: 25px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
        .filter-label { font-size: 14px; color: #475569; display: flex; align-items: center; gap: 8px; font-weight: 600; }
        .filter-tabs { display: flex; flex-wrap: wrap; gap: 10px; }
        .btn-filter { text-decoration: none; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #64748b; background-color: #f1f5f9; transition: 0.2s; border: 1px solid transparent; }
        .btn-filter:hover { background-color: #e2e8f0; }
        .btn-filter.active { background-color: #5A738E; color: white; border-color: #5A738E; box-shadow: 0 2px 4px rgba(90, 115, 142, 0.2); }

        /* ORDER CARDS LIST */
        .order-list { display: flex; flex-direction: column; gap: 15px; }
        .order-card { background: white; border: 1px solid #e2e8f0; border-radius: 10px; text-decoration: none; color: inherit; display: block; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.02); overflow: hidden; }
        .order-card:hover { border-color: #cbd5e1; box-shadow: 0 6px 12px rgba(0,0,0,0.05); transform: translateY(-2px); }
        
        .order-head { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
        .order-id { font-size: 16px; font-weight: bold; color: #1e293b; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
        .badge-masuk { background-color: #eff6ff; color: #2563eb; }
        .badge-diracik { background-color: #fefce8; color: #ca8a04; }
        .badge-selesai { background-color: #f0fdf4; color: #16a34a; }
        .badge-custom { background-color: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .badge-katalog { background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

        .order-body { padding: 20px; }
        .customer-info { margin-bottom: 20px; }
        .customer-name { font-size: 16px; font-weight: 700; color: #1e293b; }
        .order-date { font-size: 13px; color: #64748b; margin-top: 4px; }

        .specs-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .spec-item p { font-size: 12px; color: #64748b; margin-bottom: 6px; font-weight: 500; }
        .spec-item h4 { font-size: 15px; color: #1e293b; font-weight: 600; }
        
        .order-foot { padding: 16px 20px; background-color: #f8fafc; border-top: 1px solid #f1f5f9; font-size: 14px; color: #475569; }

        /* Pagination */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; font-size: 13px; margin-top: 40px; }
        .pagination a { text-decoration: none; padding: 8px 14px; border-radius: 6px; border: 1px solid #cbd5e1; color: #475569; transition: 0.2s; font-weight: 600; background: white; }
        .pagination a:hover { background-color: #f1f5f9; }
        .pagination a.active { background-color: #5A738E; color: white; border-color: #5A738E; }
    </style>
</head>
<body>

    <div class="app-layout">
        <aside class="sidebar">
            <div classx="brand">
                <div class="brand-logo">
                    <img src="<?= $base_url; ?>/assets/Logo_Perscents.png" alt="Logo">
                </div>
                <div class="brand-text">
                    <h1>PERSCENTS</h1>
                    <p>Peracik Dashboard</p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="index.php" class="active">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
                    Dashboard
                </a>
                <a href="panduan.php">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                    Buku Panduan
                </a>

                <a href="logout_peracik.php" class="logout">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Keluar
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="container">
                
                <div class="page-title">
                    <h2>Dashboard Pesanan</h2>
                    <p>Kelola dan proses pesanan parfum masuk</p>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?= $jmlMenunggu; ?></h3>
                            <span>Pesanan Masuk</span>
                        </div>
                        <div class="stat-icon bg-blue">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?= $jmlDiracik; ?></h3>
                            <span>Sedang Diracik</span>
                        </div>
                        <div class="stat-icon bg-yellow">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="2" x2="12" y2="6"></line>
                                <line x1="12" y1="18" x2="12" y2="22"></line>
                                <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                                <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                                <line x1="2" y1="12" x2="6" y2="12"></line>
                                <line x1="18" y1="12" x2="22" y2="12"></line>
                                <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                                <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?= $jmlSelesai; ?></h3>
                            <span>Total Selesai</span>
                        </div>
                        <div class="stat-icon bg-green">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?= $jmlSelesaiHariIni; ?></h3>
                            <span>Selesai Hari Ini</span>
                        </div>
                        <div class="stat-icon bg-gray">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                <path d="M9 16l2 2 4-4"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="filter-container">
                    <div class="filter-label">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                        Filter Status:
                    </div>
                    <div class="filter-tabs">
                        <a href="?status=semua" class="btn-filter <?= ($filter_status == 'semua') ? 'active' : ''; ?>">Semua</a>
                        <a href="?status=menunggu" class="btn-filter <?= ($filter_status == 'menunggu') ? 'active' : ''; ?>">Pesanan Masuk</a>
                        <a href="?status=diracik" class="btn-filter <?= ($filter_status == 'diracik') ? 'active' : ''; ?>">Sedang Diracik</a>
                        <a href="?status=selesai" class="btn-filter <?= ($filter_status == 'selesai') ? 'active' : ''; ?>">Selesai</a>
                        <a href="?status=belum_diambil" class="btn-filter <?= ($filter_status == 'belum_diambil') ? 'active' : ''; ?>">Belum Diambil</a>
                        <a href="?status=diambil" class="btn-filter <?= ($filter_status == 'diambil') ? 'active' : ''; ?>">Telah Diambil</a>
                    </div>
                </div>

                <div class="order-list">
                    <?php 
                    if(mysqli_num_rows($queryDaftarRacik) > 0) {
                        while($item = mysqli_fetch_assoc($queryDaftarRacik)) { 
                            $badge_status = "badge-masuk";
                            $text_status = "Pesanan Masuk";
                            if($item['status_racik'] == 'diracik') { $badge_status = "badge-diracik"; $text_status = "Sedang Diracik"; }
                            if($item['status_racik'] == 'selesai') { $badge_status = "badge-selesai"; $text_status = "Selesai"; }
                            
                            $badge_tipe = ($item['tipe'] == 'custom') ? "badge-custom" : "badge-katalog";
                    ?>
                    
                    <a href="detail_pesanan.php?id=<?= $item['pesanan_id']; ?>" class="order-card">
                        <div class="order-head">
                            <span class="order-id">#<?= htmlspecialchars($item['kode_pesanan']); ?></span>
                            <span class="badge <?= $badge_status; ?>"><?= $text_status; ?></span>
                            <span class="badge <?= $badge_tipe; ?>"><?= ucfirst($item['tipe']); ?></span>
                        </div>
                        
                        <div class="order-body">
                            <div class="customer-info">
                                <div class="customer-name"><?= htmlspecialchars($item['nama_pemesan']); ?></div>
                                <div class="order-date">Dipesan: <?= date('d M Y, H:i', strtotime($item['created_at'])); ?></div>
                            </div>
                            
                            <div class="specs-grid">
                                <div class="spec-item">
                                    <p>Produk / Varian Utama</p>
                                    <h4><?= htmlspecialchars($item['nama_parfum']); ?></h4>
                                </div>
                                <div class="spec-item">
                                    <p>Ukuran Botol</p>
                                    <h4><?= htmlspecialchars($item['nama_ukuran'] ?? '-'); ?> (<?= $item['ml'] ?? '0'; ?>ml)</h4>
                                </div>
                                <div class="spec-item">
                                    <p>Ketahanan (Durasi)</p>
                                    <h4><?= htmlspecialchars($item['nama_ketahanan'] ?? '-'); ?> (<?= htmlspecialchars($item['durasi'] ?? '-'); ?>)</h4>
                                </div>
                            </div>
                        </div>
                        
                        <?php if($item['tipe'] == 'custom' && !empty($item['notes_dipilih'])): ?>
                        <div class="order-foot">
                            <b>Bahan Campuran / Notes:</b> <?= htmlspecialchars($item['notes_dipilih']); ?>
                        </div>
                        <?php endif; ?>
                    </a>

                    <?php 
                        }
                    } else {
                        echo "<div style='text-align:center; padding:50px; background:white; border-radius:10px; border:1px solid #e2e8f0; color:#64748b;'>Tidak ada antrean pesanan di status ini.</div>";
                    }
                    ?>
                </div>

                <?php if ($total_halaman > 1): ?>
                <div class="pagination">
                    <?php if($halaman > 1): ?>
                        <a href="?halaman=<?= $halaman - 1; ?><?= $url_params; ?>">Prev</a>
                    <?php endif; ?>

                    <?php for($i = 1; $i <= $total_halaman; $i++): ?>
                        <a href="?halaman=<?= $i; ?><?= $url_params; ?>" class="<?= ($halaman == $i) ? 'active' : ''; ?>"><?= $i; ?></a>
                    <?php endfor; ?>

                    <?php if($halaman < $total_halaman): ?>
                        <a href="?halaman=<?= $halaman + 1; ?><?= $url_params; ?>">Next</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

</body>
</html>
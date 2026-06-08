<?php
require_once '../config/cek_akses.php';
require_once '../config/koneksi.php';

$queryTotalTrx = mysqli_query($conn, "SELECT COUNT(id) as total FROM pesanan");
$totalTrxAll = mysqli_fetch_assoc($queryTotalTrx)['total'];

$queryTrxSukses = mysqli_query($conn, "SELECT COUNT(id) as total, SUM(total_harga) as pendapatan FROM pesanan WHERE status = 'lunas'");
$dataSukses = mysqli_fetch_assoc($queryTrxSukses);
$trxBerhasil = $dataSukses['total'] ?? 0;
$saldoTersedia = $dataSukses['pendapatan'] ?? 0;

$queryTrxGagal = mysqli_query($conn, "SELECT COUNT(id) as total FROM pesanan WHERE status != 'lunas' AND status != 'pending'");
$trxGagal = mysqli_fetch_assoc($queryTrxGagal)['total'] ?? 0;

$queryAktivitas = mysqli_query($conn, "SELECT kode_pesanan, total_harga, status, created_at FROM pesanan ORDER BY created_at DESC LIMIT 6");

$search_id = isset($_GET['cari_id']) ? mysqli_real_escape_string($conn, $_GET['cari_id']) : '';
$status_trx = isset($_GET['status_trx']) ? mysqli_real_escape_string($conn, $_GET['status_trx']) : '';
$tgl_mulai = isset($_GET['tgl_mulai']) ? mysqli_real_escape_string($conn, $_GET['tgl_mulai']) : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? mysqli_real_escape_string($conn, $_GET['tgl_akhir']) : '';

$where_clause = "WHERE 1=1"; 
if (!empty($search_id)) { $where_clause .= " AND kode_pesanan LIKE '%$search_id%'"; }
if (!empty($status_trx) && $status_trx != 'Semua Status') { $where_clause .= " AND status = '$status_trx'"; }
if (!empty($tgl_mulai) && !empty($tgl_akhir)) { $where_clause .= " AND created_at BETWEEN '$tgl_mulai 00:00:00' AND '$tgl_akhir 23:59:59'"; }

$limit = 10; 
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($halaman - 1) * $limit;

$queryCount = mysqli_query($conn, "SELECT COUNT(id) as total, SUM(total_harga) as total_uang FROM pesanan $where_clause");
$dataCount = mysqli_fetch_assoc($queryCount);
$total_data = $dataCount['total'];
$total_uang_filter = $dataCount['total_uang'] ?? 0;
$total_halaman = ceil($total_data / $limit);

$queryMainTable = mysqli_query($conn, "SELECT * FROM pesanan $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset"); 
$url_params = "&cari_id=" . urlencode($search_id) . "&status_trx=" . urlencode($status_trx) . "&tgl_mulai=" . urlencode($tgl_mulai) . "&tgl_akhir=" . urlencode($tgl_akhir);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendapatan - PERSCENTS</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #F4F7FE; color: #2B3674; display: flex; font-size: 14px; }
        
        /* --- SIDEBAR --- */
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
        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .admin-profile { display: flex; align-items: center; gap: 12px; background: #ffffff; padding: 8px 16px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .admin-profile img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
        .admin-profile span { font-weight: 600; font-size: 14px; color: #2B3674; }

        .card { background: #ffffff; border-radius: 20px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); margin-bottom: 24px; border: none; transition: transform 0.2s ease; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.04); }
        .card h3 { font-size: 15px; color: #A3AED0; font-weight: 500; margin-bottom: 12px; }
        .card h2 { font-size: 18px; color: #2B3674; font-weight: 700; margin-bottom: 20px; }
        
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px; }
        .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px; }
        
        .stat-value { font-size: 32px; font-weight: 700; color: #2B3674; margin-bottom: 4px; }
        .stat-subtext { font-size: 13px; color: #05CD99; font-weight: 500; }

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
        
        .btn-icon { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; transition: 0.2s; text-decoration: none; margin: 0 4px; border: none; cursor: pointer; }
        .btn-edit { background: #E9EDF7; color: #5A738E; }
        .btn-edit:hover { background: #5A738E; color: white; }
        .btn-hapus { background: #FEECEE; color: #EE5D50; }
        .btn-hapus:hover { background: #EE5D50; color: white; }

        .search-input, .select-filter, .form-control { padding: 12px 16px; border: 2px solid #E9EDF7; border-radius: 12px; font-size: 14px; color: #2B3674; outline: none; transition: 0.3s; background: white; }
        .search-input:focus, .select-filter:focus, .form-control:focus { border-color: #5A738E; }
        
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; }
        .pagination a span { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; border: 1px solid #E9EDF7; color: #A3AED0; font-weight: 500; transition: 0.2s; }
        .pagination a { text-decoration: none; }
        .pagination a span:hover { background: #F4F7FE; color: #5A738E; }
        .pagination a span.active { background: #5A738E; color: white; border-color: #5A738E; }

        .print-header { display: none; text-align: center; margin-bottom: 20px; }

        @media print {
            body { background: white !important; }
            .sidebar, .topbar, form, .pagination, .side-aktivitas, .btn-icon { display: none !important; }
            .main-wrapper { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            
            .data-table th:last-child, .data-table td:last-child { display: none !important; }
            
            .card { box-shadow: none !important; border: 1px solid #E9EDF7 !important; padding: 15px !important; margin-bottom: 15px !important; }
            .grid-2 { display: block !important; }
            
            .print-header { display: block !important; }
            .print-header h2 { font-size: 24px; color: #2B3674; margin-bottom: 5px; }
            .print-header p { font-size: 14px; color: #A3AED0; }
            
            .grid-3 { grid-template-columns: repeat(3, 1fr) !important; gap: 10px !important; }
            
            .card[style*="linear-gradient"] { background: white !important; border: 2px solid #5A738E !important; }
            .card[style*="linear-gradient"] h3, .card[style*="linear-gradient"] .stat-value, .card[style*="linear-gradient"] .stat-subtext { color: #2B3674 !important; }
        }
    </style>

</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="<?= $base_url; ?>/assets/Logo_Perscents.png" alt="Logo">
            <h2>Perscents</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="produk.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'produk.php') ? 'active' : ''; ?>"><i class="fa-solid fa-box-open"></i> Produk & Stok</a></li>
            <li><a href="variant_management.php" class="<?= (strpos(basename($_SERVER['PHP_SELF']), 'variant') !== false || strpos(basename($_SERVER['PHP_SELF']), 'ukuran') !== false || strpos(basename($_SERVER['PHP_SELF']), 'ketahanan') !== false) ? 'active' : ''; ?>"><i class="fa-solid fa-layer-group"></i> Varian Custom</a></li>
            <li><a href="pendapatan.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'pendapatan.php' || basename($_SERVER['PHP_SELF']) == 'detail_pesanan.php') ? 'active' : ''; ?>"><i class="fa-solid fa-wallet"></i> Pendapatan</a></li>
            <li style="margin-top: auto;"><a href="logout_admin.php" style="color: #EE5D50;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a></li>
        </ul>
    </aside>


    <main class="main-wrapper">
        <header class="topbar">
            <h1 class="page-title">Keuangan & Transaksi</h1>
            <div class="topbar-right">
                <div class="admin-profile">
                    <img src="<?= $base_url; ?>/assets/Logo_Perscents.png" alt="Admin">
                    <span>Admin</span>
                </div>
            </div>
        </header>

        <div class="print-header">
            <h2>Laporan Pendapatan & Transaksi PERSCENTS</h2>
            <p>Tanggal Cetak: <?= date('d F Y, H:i') ?> WIB</p>
        </div>

        <div class="grid-3">
            <div class="card">
                <h3>Transaksi Berhasil</h3>
                <div class="stat-value text-success"><?= $trxBerhasil; ?></div>
                <div class="stat-subtext" style="color:#A3AED0;">Dari <?= $totalTrxAll; ?> total order</div>
            </div>
            <div class="card">
                <h3>Transaksi Batal / Gagal</h3>
                <div class="stat-value text-danger" style="color:#EE5D50;"><?= $trxGagal; ?></div>
                <div class="stat-subtext" style="color:#A3AED0;">Perlu dievaluasi</div>
            </div>
            <div class="card" style="background: linear-gradient(135deg, #5A738E, #2B3674); color: white;">
                <h3 style="color: rgba(255,255,255,0.8);">Saldo Tersedia</h3>
                <div class="stat-value" style="color: white;">Rp <?= number_format($saldoTersedia, 0, ',', '.'); ?></div>
                <div class="stat-subtext" style="color: rgba(255,255,255,0.8);">Akumulasi sukses</div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card" style="width: 100%;">
                <h2>Riwayat Transaksi</h2>
                <form method="GET" action="pendapatan.php" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px;">
                    <input type="text" name="cari_id" class="search-input" placeholder="ID Order..." value="<?= htmlspecialchars($search_id); ?>" style="flex: 1; min-width: 120px;">
                    <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tgl_mulai); ?>" style="width:130px;">
                    <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tgl_akhir); ?>" style="width:130px;">
                    <select name="status_trx" class="select-filter" onchange="this.form.submit()">
                        <option value="Semua Status">Semua Status</option>
                        <option value="lunas" <?= ($status_trx == 'lunas') ? 'selected' : ''; ?>>Lunas</option>
                        <option value="pending" <?= ($status_trx == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="batal" <?= ($status_trx == 'batal') ? 'selected' : ''; ?>>Batal</option>
                    </select>
                    <button type="submit" class="btn-primary" style="padding: 0 16px;"><i class="fa-solid fa-filter"></i></button>
                    <a href="pendapatan.php" class="btn-primary" style="background:#EE5D50; padding: 0 16px;"><i class="fa-solid fa-rotate-right"></i></a>
                    <a href="cetak_laporan.php?status_trx=<?= urlencode($status_trx); ?>&tgl_mulai=<?= urlencode($tgl_mulai); ?>&tgl_akhir=<?= urlencode($tgl_akhir); ?>" target="_blank" class="btn-primary" style="background:#05CD99; padding: 10px 20px; text-decoration:none;">
                        <i class="fa-solid fa-file-pdf"></i> Download PDF
                    </a>
                </form>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if(mysqli_num_rows($queryMainTable) > 0) {
                                while($row = mysqli_fetch_assoc($queryMainTable)) { 
                                    $badge_class = "badge-warning";
                                    if($row['status'] == 'lunas') $badge_class = "badge-success";
                                    if($row['status'] == 'batal' || $row['status'] == 'dibatalkan') $badge_class = "badge-danger";
                            ?>
                            <tr>
                                <td style="font-weight:700; color:#5A738E;">#<?= htmlspecialchars($row['kode_pesanan']); ?></td>
                                <td><?= htmlspecialchars($row['nama_pemesan']); ?></td>
                                <td><div style="font-size:12px; color:#A3AED0;"><?= date('d M Y', strtotime($row['created_at'])); ?></div><div style="font-size:11px;"><?= date('H:i', strtotime($row['created_at'])); ?></div></td>
                                <td style="font-weight:600;">Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                <td><span class="badge <?= $badge_class; ?>"><?= ucfirst($row['status']); ?></span></td>
                                <td style="text-align:center;">
                                    <a href="detail_pesanan.php?id=<?= $row['id']; ?>" class="btn-icon btn-edit" title="Lihat Invoice"><i class="fa-solid fa-eye"></i></a>
                                </td>
                            </tr>
                            <?php } } else { echo "<tr><td colspan='6' style='text-align:center; padding: 30px;'>Transaksi tidak ditemukan.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
                    <div style="font-size:13px; color:#A3AED0;">
                        Menampilkan <?= mysqli_num_rows($queryMainTable); ?> dari <?= $total_data; ?> Trx <br>
                        <b>Total Nilai: Rp <?= number_format($total_uang_filter, 0, ',', '.'); ?></b>
                    </div>
                    <?php if ($total_halaman > 1): ?>
                    <div class="pagination" style="margin-top:0;">
                        <?php for($i = 1; $i <= $total_halaman; $i++): ?>
                            <a href="?halaman=<?= $i; ?><?= $url_params; ?>"><span class="<?= ($halaman == $i) ? 'active' : ''; ?>"><?= $i; ?></span></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card side-aktivitas" style="height: fit-content;">
                <h2>Aktivitas Terbaru</h2>
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <?php if(mysqli_num_rows($queryAktivitas) > 0) {
                        while($act = mysqli_fetch_assoc($queryAktivitas)) { 
                            $badge_class = "badge-warning";
                            if($act['status'] == 'lunas') $badge_class = "badge-success";
                            if($act['status'] == 'batal' || $act['status'] == 'dibatalkan') $badge_class = "badge-danger";
                    ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:12px; border-bottom:1px solid #E9EDF7;">
                            <div>
                                <div style="font-weight:600; color:#2B3674;">#<?= htmlspecialchars($act['kode_pesanan']); ?></div>
                                <div style="font-size:12px; color:#A3AED0;"><?= date('d M Y, H:i', strtotime($act['created_at'])); ?></div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:700; color:#5A738E; font-size:13px;">Rp <?= number_format($act['total_harga'], 0, ',', '.'); ?></div>
                                <span class="badge <?= $badge_class; ?>" style="font-size:10px; padding: 2px 8px; margin-top:4px;"><?= ucfirst($act['status']); ?></span>
                            </div>
                        </div>
                    <?php } } else { echo "<div style='color:#A3AED0; font-size:13px;'>Belum ada aktivitas.</div>"; } ?>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
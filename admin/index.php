<?php
require_once '../config/cek_akses.php';
require_once '../config/koneksi.php'; 
require_once '../config/dashboard.php';

$stokSedikit = getStokSedikit($conn);
$produkTerlaris = getProdukTerlaris($conn);
$transaksiTerbaru = getTransaksiTerbaru($conn);

$totalTransaksiQuery = mysqli_query($conn, "SELECT id FROM pesanan WHERE status = 'lunas'");
$totalTransaksi = mysqli_num_rows($totalTransaksiQuery);

$pendapatanQuery = mysqli_query($conn, "SELECT SUM(total_harga) AS total FROM pesanan WHERE status='lunas'");
$pendapatan = mysqli_fetch_assoc($pendapatanQuery);

$querySales = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%b') as bulan, SUM(total_harga) as total 
    FROM pesanan 
    WHERE status='lunas' 
    GROUP BY MONTH(created_at), DATE_FORMAT(created_at, '%b')
    ORDER BY MONTH(created_at) ASC 
    LIMIT 6
");

$salesLabels = [];
$salesData = [];
if($querySales) {
    while($row = mysqli_fetch_assoc($querySales)) {
        $salesLabels[] = $row['bulan'];
        $salesData[] = $row['total'];
    }
}

$queryCategory = mysqli_query($conn, "
    SELECT kategori, COUNT(*) as jumlah 
    FROM produk_collection 
    GROUP BY kategori
");
$catLabels = [];
$catData = [];
if($queryCategory) {
    while($row = mysqli_fetch_assoc($queryCategory)) {
        $catLabels[] = $row['kategori'] ?? 'Lainnya';
        $catData[] = $row['jumlah'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PERSCENTS</title>

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
        .toolbar { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .toolbar form { display: flex; gap: 12px; width: 100%; align-items: center; }

        .prod-info { display: flex; align-items: center; gap: 12px; }
        .prod-thumb { width: 44px; height: 44px; border-radius: 10px; object-fit: cover; background: #F4F7FE; padding: 2px; }

        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; }
        .pagination a span { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; border: 1px solid #E9EDF7; color: #A3AED0; font-weight: 500; transition: 0.2s; }
        .pagination a { text-decoration: none; }
        .pagination a span:hover { background: #F4F7FE; color: #5A738E; }
        .pagination a span.active { background: #5A738E; color: white; border-color: #5A738E; }
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
            <h1 class="page-title">Dashboard Overview</h1>
            <div class="topbar-right">
                <a href="tambah_produk_collection.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah Produk</a>
                <div class="admin-profile">
                    <img src="<?= $base_url; ?>/assets/Logo_Perscents.png" alt="Admin">
                    <span>Admin</span>
                </div>
            </div>
        </header>

        <div class="grid-3">
            <div class="card">
                <h3>Total Transaksi</h3>
                <div class="stat-value"><?= $totalTransaksi; ?></div>
                <div class="stat-subtext"><i class="fa-solid fa-circle-check"></i> Transaksi Berhasil</div>
            </div>
            <div class="card">
                <h3>Total Pendapatan</h3>
                <div class="stat-value">Rp <?= number_format($pendapatan['total'] ?? 0,0,',','.'); ?></div>
                <div class="stat-subtext"><i class="fa-solid fa-wallet"></i> Saldo Tersedia</div>
            </div>
            <div class="card">
                <h3>Stok Hampir Habis</h3>
                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                    <?php 
                    if (mysqli_num_rows($stokSedikit) > 0) {
                        while($stok = mysqli_fetch_assoc($stokSedikit)) { 
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #FEECEE; border-radius: 8px;">
                            <span style="font-weight: 500; color: #EE5D50; font-size: 13px;"><?= htmlspecialchars($stok['nama']); ?></span>
                            <span class="badge badge-danger">Sisa <?= $stok['stok']; ?></span>
                        </div>
                    <?php 
                        } 
                    } else {
                        echo "<div style='padding: 12px; background: #E5F8ED; border-radius: 8px; color: #05CD99; font-weight: 500; text-align: center;'><i class='fa-solid fa-shield-check'></i> Stok Aman Semua</div>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card" style="margin-bottom: 0;">
                <h2>Tren Penjualan</h2>
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <div class="card" style="margin-bottom: 0;">
                <h2>Komposisi Kategori</h2>
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <h2>Top 5 Produk Terlaris</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Produk</th>
                                <th>Terjual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($produkTerlaris) > 0) {
                                while($produk = mysqli_fetch_assoc($produkTerlaris)) { 
                                    $path_foto = (!empty($produk['foto'])) ? $produk['foto'] : 'assets/Logo_Perscents.png';
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <div class="prod-info">
                                        <img src="<?= $base_url; ?>/<?= htmlspecialchars($path_foto); ?>" class="prod-thumb">
                                        <span><?= htmlspecialchars($produk['nama']); ?></span>
                                    </div>
                                </td>
                                <td><b><?= $produk['total_terjual']; ?></b> pcs</td>
                            </tr>
                            <?php 
                                } 
                            } else {
                                echo "<tr><td colspan='3' style='text-align:center;'>Belum ada data penjualan</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h2>Transaksi Terbaru</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID TRX</th>
                                <th>Nominal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if(mysqli_num_rows($transaksiTerbaru) > 0) {
                                while($trx = mysqli_fetch_assoc($transaksiTerbaru)) { 
                            ?>
                            <tr>
                                <td style="font-weight: 700;">#<?= htmlspecialchars($trx['kode_pesanan']); ?></td>
                                <td>Rp <?= number_format($trx['total_harga'],0,',','.'); ?></td>
                                <td>
                                    <?php if(strtolower($trx['status']) == 'lunas') { ?>
                                        <span class="badge badge-success">Lunas</span>
                                    <?php } elseif(strtolower($trx['status']) == 'batal') { ?>
                                        <span class="badge badge-danger">Batal</span>
                                    <?php } else { ?>
                                        <span class="badge badge-warning"><?= htmlspecialchars(ucfirst($trx['status'])); ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php 
                                } 
                            } else {
                                echo "<tr><td colspan='3' style='text-align:center;'>Belum ada transaksi</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const salesLabels = <?= json_encode($salesLabels); ?>;
        const salesData = <?= json_encode($salesData); ?>;
        const catLabels = <?= json_encode($catLabels); ?>;
        const catData = <?= json_encode($catData); ?>;

        const ctxSales = document.getElementById('salesChart').getContext('2d');
        new Chart(ctxSales, {
            type: 'line',
            data: {
                labels: salesLabels.length > 0 ? salesLabels : ['Belum ada data'],
                datasets: [{
                    label: 'Penjualan (Rp)',
                    data: salesData.length > 0 ? salesData : [0],
                    borderColor: '#5A738E',
                    backgroundColor: 'rgba(90, 115, 142, 0.2)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#2B3674',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#E9EDF7' } },
                    x: { grid: { display: false } }
                }
            }
        });

        const ctxCategory = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctxCategory, {
            type: 'doughnut',
            data: {
                labels: catLabels.length > 0 ? catLabels : ['Belum ada data'],
                datasets: [{
                    data: catData.length > 0 ? catData : [1],
                    backgroundColor: ['#5A738E', '#8E7A5D', '#E2E8F0', '#A3AED0'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                }
            }
        });
    </script>
</body>
</html>
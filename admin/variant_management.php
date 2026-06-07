<?php
require_once '../config/cek_akses.php';
require_once '../config/koneksi.php';

// KETAHANAN LOGIC
$search_ketahanan = isset($_GET['cari_ketahanan']) ? mysqli_real_escape_string($conn, $_GET['cari_ketahanan']) : '';
$where_ketahanan = "WHERE 1=1"; 
if (!empty($search_ketahanan)) { $where_ketahanan .= " AND nama LIKE '%$search_ketahanan%'"; }
$limit_ketahanan = 5; 
$halaman_ketahanan = isset($_GET['halaman_ketahanan']) ? (int)$_GET['halaman_ketahanan'] : 1;
$offset_ketahanan = ($halaman_ketahanan - 1) * $limit_ketahanan;

$queryCountKetahanan = mysqli_query($conn, "SELECT COUNT(id) as total FROM mst_ketahanan $where_ketahanan");
$total_halaman_ketahanan = ceil(mysqli_fetch_assoc($queryCountKetahanan)['total'] / $limit_ketahanan);
$queryKetahanan = mysqli_query($conn, "SELECT * FROM mst_ketahanan $where_ketahanan ORDER BY id DESC LIMIT $limit_ketahanan OFFSET $offset_ketahanan");

// UKURAN LOGIC
$search_ukuran = isset($_GET['cari_ukuran']) ? mysqli_real_escape_string($conn, $_GET['cari_ukuran']) : '';
$where_ukuran = "WHERE 1=1"; 
if (!empty($search_ukuran)) { $where_ukuran .= " AND nama LIKE '%$search_ukuran%'"; }
$limit_ukuran = 5; 
$halaman_ukuran = isset($_GET['halaman_ukuran']) ? (int)$_GET['halaman_ukuran'] : 1;
$offset_ukuran = ($halaman_ukuran - 1) * $limit_ukuran;

$queryCountUkuran = mysqli_query($conn, "SELECT COUNT(id) as total FROM mst_ukuran $where_ukuran");
$total_halaman_ukuran = ceil(mysqli_fetch_assoc($queryCountUkuran)['total'] / $limit_ukuran);
$queryUkuran = mysqli_query($conn, "SELECT * FROM mst_ukuran $where_ukuran ORDER BY id DESC LIMIT $limit_ukuran OFFSET $offset_ukuran"); 

$params_ukuran_only = "&halaman_ukuran=$halaman_ukuran&cari_ukuran=" . urlencode($search_ukuran);
$params_ketahanan_only = "&halaman_ketahanan=$halaman_ketahanan&cari_ketahanan=" . urlencode($search_ketahanan);

// SIDEBAR SUMMARIES
$querySemuaKetahanan = mysqli_query($conn, "SELECT nama, durasi FROM mst_ketahanan ORDER BY id ASC LIMIT 5");
$querySemuaUkuran = mysqli_query($conn, "SELECT nama, ml FROM mst_ukuran ORDER BY ml ASC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Varian Custom - PERSCENTS</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- GLOBAL MODERN UI --- */
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

        /* --- MAIN CONTENT --- */
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); min-height: 100vh; padding: 30px 40px; display: flex; flex-direction: column; }
        
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .topbar .page-title { font-size: 26px; font-weight: 700; color: #2B3674; }
        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .admin-profile { display: flex; align-items: center; gap: 12px; background: #ffffff; padding: 8px 16px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .admin-profile img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
        .admin-profile span { font-weight: 600; font-size: 14px; color: #2B3674; }

        /* --- CARDS & GRIDS --- */
        .card { background: #ffffff; border-radius: 20px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); margin-bottom: 24px; border: none; transition: transform 0.2s ease; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.04); }
        .card h3 { font-size: 15px; color: #A3AED0; font-weight: 500; margin-bottom: 12px; }
        .card h2 { font-size: 18px; color: #2B3674; font-weight: 700; margin-bottom: 20px; }
        
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px; }
        .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px; }

        /* --- TABLES --- */
        .table-responsive { width: 100%; overflow-x: auto; }
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .data-table th { color: #A3AED0; font-weight: 500; font-size: 13px; text-transform: uppercase; padding: 16px; border-bottom: 1px solid #E9EDF7; text-align: left; }
        .data-table td { padding: 16px; font-size: 14px; color: #2B3674; font-weight: 500; border-bottom: 1px solid #F4F7FE; vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #F8FAFC; }

        /* --- BADGES --- */
        .badge { padding: 6px 12px; border-radius: 30px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; text-transform: capitalize; }
        .badge-success { background: #E5F8ED; color: #05CD99; }
        .badge-warning { background: #FFF4E5; color: #FFB547; }
        .badge-danger { background: #FEECEE; color: #EE5D50; }
        .badge-blue { background: #E0F2FE; color: #0284C7; }

        /* --- BUTTONS & INPUTS --- */
        .btn-primary { background: #5A738E; color: white; padding: 10px 20px; border-radius: 12px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; transition: 0.3s; border: none; cursor: pointer; }
        .btn-primary:hover { background: #495e75; box-shadow: 0 4px 12px rgba(90, 115, 142, 0.3); transform: translateY(-2px); }
        
        .btn-icon { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; transition: 0.2s; text-decoration: none; margin: 0 4px; border: none; cursor: pointer; }
        .btn-edit { background: #E9EDF7; color: #5A738E; }
        .btn-edit:hover { background: #5A738E; color: white; }
        .btn-hapus { background: #FEECEE; color: #EE5D50; }
        .btn-hapus:hover { background: #EE5D50; color: white; }

        .search-input, .select-filter, .form-control { padding: 12px 16px; border: 2px solid #E9EDF7; border-radius: 12px; font-size: 14px; color: #2B3674; outline: none; transition: 0.3s; background: white; width: 100%; }
        .search-input:focus, .select-filter:focus, .form-control:focus { border-color: #5A738E; }
        .toolbar { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }

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

    <!-- SIDEBAR -->
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
            <h1 class="page-title">Varian Custom</h1>
            <div class="topbar-right">
                <div class="admin-profile">
                    <img src="<?= $base_url; ?>/assets/Logo_Perscents.png" alt="Admin">
                    <span>Admin</span>
                </div>
            </div>
        </header>

        <!-- TOP WIDGETS -->
        <div class="grid-3">
            <div class="card" style="padding: 20px;">
                <h3 style="color:#05CD99; font-weight:600;"><i class="fa-solid fa-clock"></i> Ringkasan Ketahanan</h3>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php if(mysqli_num_rows($querySemuaKetahanan) > 0) {
                        while($kat = mysqli_fetch_assoc($querySemuaKetahanan)) { ?>
                        <div style="display:flex; justify-content:space-between; font-size:13px; padding: 6px 0; border-bottom:1px dashed #E9EDF7;">
                            <span style="color:#2B3674;"><?= htmlspecialchars($kat['nama']); ?></span>
                            <span class="badge badge-success"><?= htmlspecialchars($kat['durasi']); ?></span>
                        </div>
                    <?php } } else { echo "<div class='badge badge-warning'>Belum ada data</div>"; } ?>
                </div>
            </div>
            
            <div class="card" style="padding: 20px;">
                <h3 style="color:#0284C7; font-weight:600;"><i class="fa-solid fa-bottle-droplet"></i> Ringkasan Ukuran</h3>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php if(mysqli_num_rows($querySemuaUkuran) > 0) {
                        while($uk = mysqli_fetch_assoc($querySemuaUkuran)) { ?>
                        <div style="display:flex; justify-content:space-between; font-size:13px; padding: 6px 0; border-bottom:1px dashed #E9EDF7;">
                            <span style="color:#2B3674;"><?= htmlspecialchars($uk['nama']); ?></span>
                            <span class="badge badge-blue"><?= $uk['ml']; ?> ml</span>
                        </div>
                    <?php } } else { echo "<div class='badge badge-warning'>Belum ada data</div>"; } ?>
                </div>
            </div>
            
            <div class="card" style="padding: 20px; background: #5A738E; color: white;">
                <h3 style="color:white; font-weight:600;"><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
                <div style="display:flex; flex-direction:column; gap:12px; margin-top: 15px;">
                    <a href="tambah_ketahanan.php" class="btn-primary" style="background:white; color:#5A738E;"><i class="fa-solid fa-plus"></i> Tambah Ketahanan</a>
                    <a href="tambah_ukuran.php" class="btn-primary" style="background:rgba(255,255,255,0.2); color:white; border: 1px solid rgba(255,255,255,0.4);"><i class="fa-solid fa-plus"></i> Tambah Ukuran</a>
                </div>
            </div>
        </div>

        <!-- TABEL KETAHANAN -->
        <section class="card">
            <h2>Master Data: Ketahanan Aroma</h2>
            <form method="GET" action="variant_management.php">
                <div class="toolbar">
                    <input type="hidden" name="halaman_ukuran" value="<?= $halaman_ukuran; ?>">
                    <input type="hidden" name="cari_ukuran" value="<?= htmlspecialchars($search_ukuran); ?>">

                    <div style="display:flex; flex-grow: 1; gap:12px;">
                        <input type="text" name="cari_ketahanan" class="search-input" placeholder="Cari tipe ketahanan (contoh: EDP)..." value="<?= htmlspecialchars($search_ketahanan); ?>" style="flex-grow:1; width:auto;">
                        <button type="submit" class="btn-primary" style="padding: 0 24px;">Cari</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tipe Ketahanan</th>
                            <th>Estimasi Durasi</th>
                            <th>Tambahan Harga</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no_ket = $offset_ketahanan + 1; 
                        if(mysqli_num_rows($queryKetahanan) > 0) {
                            while($ketahanan = mysqli_fetch_assoc($queryKetahanan)) { 
                        ?>
                        <tr>
                            <td><?= $no_ket++; ?></td>
                            <td>
                                <div class="prod-info">
                                    <div class="prod-thumb" style="display:flex; align-items:center; justify-content:center; background:#E5F8ED; color:#05CD99;"><i class="fa-solid fa-clock"></i></div>
                                    <span style="font-weight: 600;"><?= htmlspecialchars($ketahanan['nama']); ?></span>
                                </div>
                            </td>
                            <td><span class="badge badge-success"><?= htmlspecialchars($ketahanan['durasi']); ?></span></td>
                            <td>Rp <?= number_format($ketahanan['tambahan_harga'], 0, ',', '.'); ?></td>
                            <td style="text-align: center;">
                                <a href="edit_ketahanan.php?id=<?= $ketahanan['id']; ?>" class="btn-icon btn-edit"><i class="fa-solid fa-pen"></i></a>
                                <a href="hapus_ketahanan.php?id=<?= $ketahanan['id']; ?>" class="btn-icon btn-hapus" onclick="return confirm('Hapus ketahanan ini?');"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php } } else { echo "<tr><td colspan='5' style='text-align:center; padding: 30px;'>Data ketahanan tidak ditemukan</td></tr>"; } ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_halaman_ketahanan > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $total_halaman_ketahanan; $i++): ?>
                    <a href="?halaman_ketahanan=<?= $i; ?>&cari_ketahanan=<?= urlencode($search_ketahanan); ?><?= $params_ukuran_only; ?>">
                        <span class="<?= ($halaman_ketahanan == $i) ? 'active' : ''; ?>"><?= $i; ?></span>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- TABEL UKURAN -->
        <section class="card">
            <h2>Master Data: Ukuran Botol</h2>
            <form method="GET" action="variant_management.php">
                <div class="toolbar">
                    <input type="hidden" name="halaman_ketahanan" value="<?= $halaman_ketahanan; ?>">
                    <input type="hidden" name="cari_ketahanan" value="<?= htmlspecialchars($search_ketahanan); ?>">

                    <div style="display:flex; flex-grow: 1; gap:12px;">
                        <input type="text" name="cari_ukuran" class="search-input" placeholder="Cari ukuran botol..." value="<?= htmlspecialchars($search_ukuran); ?>" style="flex-grow:1; width:auto;">
                        <button type="submit" class="btn-primary" style="padding: 0 24px;">Cari</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Ukuran</th>
                            <th>Volume (ml)</th>
                            <th>Tambahan Harga</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no_ukuran = $offset_ukuran + 1;
                        if(mysqli_num_rows($queryUkuran) > 0) {
                            while($ukuran = mysqli_fetch_assoc($queryUkuran)) { 
                        ?>
                        <tr>
                            <td><?= $no_ukuran++; ?></td>
                            <td>
                                <div class="prod-info">
                                    <div class="prod-thumb" style="display:flex; align-items:center; justify-content:center; background:#E0F2FE; color:#0284C7;"><i class="fa-solid fa-bottle-droplet"></i></div>
                                    <span style="font-weight: 600;"><?= htmlspecialchars($ukuran['nama']); ?></span>
                                </div>
                            </td>
                            <td><span class="badge badge-blue"><?= $ukuran['ml']; ?> ml</span></td>
                            <td>Rp <?= number_format($ukuran['tambahan_harga'], 0, ',', '.'); ?></td>
                            <td style="text-align: center;">
                                <a href="edit_ukuran.php?id=<?= $ukuran['id']; ?>" class="btn-icon btn-edit"><i class="fa-solid fa-pen"></i></a>
                                <a href="hapus_ukuran.php?id=<?= $ukuran['id']; ?>" class="btn-icon btn-hapus" onclick="return confirm('Hapus ukuran ini?');"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php } } else { echo "<tr><td colspan='5' style='text-align:center; padding: 30px;'>Data ukuran tidak ditemukan.</td></tr>"; } ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_halaman_ukuran > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $total_halaman_ukuran; $i++): ?>
                    <a href="?halaman_ukuran=<?= $i; ?>&cari_ukuran=<?= urlencode($search_ukuran); ?><?= $params_ketahanan_only; ?>">
                        <span class="<?= ($halaman_ukuran == $i) ? 'active' : ''; ?>"><?= $i; ?></span>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </section>

    </main>
</body>
</html>
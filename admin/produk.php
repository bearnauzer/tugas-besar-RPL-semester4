<?php
require_once '../config/cek_akses.php';
require_once '../config/koneksi.php';

// 1. Query Top Cards
$queryStokSedikit = mysqli_query($conn, "SELECT nama, stok FROM produk_collection WHERE stok <= 10 ORDER BY stok ASC LIMIT 4");
$queryStokBanyak = mysqli_query($conn, "SELECT nama, stok FROM produk_collection ORDER BY stok DESC LIMIT 4");

// =========================================================================
// LOGIKA FILTER, SEARCH, & PAGINATION
// =========================================================================
$search_produk = isset($_GET['cari_produk']) ? mysqli_real_escape_string($conn, $_GET['cari_produk']) : '';
$kategori_produk = isset($_GET['kategori_produk']) ? mysqli_real_escape_string($conn, $_GET['kategori_produk']) : '';

$where_produk = "WHERE 1=1"; 
if (!empty($search_produk)) {
    $where_produk .= " AND nama LIKE '%$search_produk%'";
}
if (!empty($kategori_produk) && $kategori_produk != 'Semua Kategori') {
    $where_produk .= " AND kategori = '$kategori_produk'";
}

$limit_produk = 5; 
$halaman_produk = isset($_GET['halaman_produk']) ? (int)$_GET['halaman_produk'] : 1;
$offset_produk = ($halaman_produk - 1) * $limit_produk;

$queryCountProduk = mysqli_query($conn, "SELECT COUNT(id) as total FROM produk_collection $where_produk");
$total_data_produk = mysqli_fetch_assoc($queryCountProduk)['total'];
$total_halaman_produk = ceil($total_data_produk / $limit_produk);

$queryProduk = mysqli_query($conn, "SELECT * FROM produk_collection $where_produk ORDER BY id DESC LIMIT $limit_produk OFFSET $offset_produk");

// BAHAN BAKU
$search_bahan = isset($_GET['cari_bahan']) ? mysqli_real_escape_string($conn, $_GET['cari_bahan']) : '';
$kategori_bahan = isset($_GET['kategori_bahan']) ? mysqli_real_escape_string($conn, $_GET['kategori_bahan']) : '';

$where_bahan = "WHERE 1=1"; 
if (!empty($search_bahan)) { $where_bahan .= " AND nama LIKE '%$search_bahan%'"; }
if (!empty($kategori_bahan) && $kategori_bahan != 'Semua Kategori') { $where_bahan .= " AND kategori = '$kategori_bahan'"; }

$limit_bahan = 5; 
$halaman_bahan = isset($_GET['halaman_bahan']) ? (int)$_GET['halaman_bahan'] : 1;
$offset_bahan = ($halaman_bahan - 1) * $limit_bahan;

$queryCountBahan = mysqli_query($conn, "SELECT COUNT(id) as total FROM notes_aroma_custom $where_bahan");
$total_halaman_bahan = ceil(mysqli_fetch_assoc($queryCountBahan)['total'] / $limit_bahan);
$queryBahan = mysqli_query($conn, "SELECT * FROM notes_aroma_custom $where_bahan ORDER BY id DESC LIMIT $limit_bahan OFFSET $offset_bahan"); 

$params_bahan_only = "&halaman_bahan=$halaman_bahan&cari_bahan=" . urlencode($search_bahan) . "&kategori_bahan=" . urlencode($kategori_bahan);
$params_produk_only = "&halaman_produk=$halaman_produk&cari_produk=" . urlencode($search_produk) . "&kategori_produk=" . urlencode($kategori_produk);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk & Stok - PERSCENTS</title>

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
        
        .stat-value { font-size: 32px; font-weight: 700; color: #2B3674; margin-bottom: 4px; }
        .stat-subtext { font-size: 13px; color: #05CD99; font-weight: 500; }

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
            <h1 class="page-title">Katalog & Inventaris</h1>
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
                <h3 style="color:#EE5D50; font-weight:600;"><i class="fa-solid fa-triangle-exclamation"></i> Stok Hampir Habis</h3>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php if(mysqli_num_rows($queryStokSedikit) > 0) {
                        while($stokAlert = mysqli_fetch_assoc($queryStokSedikit)) { ?>
                        <div style="display:flex; justify-content:space-between; font-size:13px; padding: 6px 0; border-bottom:1px dashed #E9EDF7;">
                            <span style="color:#2B3674;"><?= htmlspecialchars($stokAlert['nama']); ?></span>
                            <span class="badge badge-danger"><?= $stokAlert['stok']; ?></span>
                        </div>
                    <?php } } else { echo "<div class='badge badge-success'>Stok Aman</div>"; } ?>
                </div>
            </div>
            <div class="card" style="padding: 20px;">
                <h3 style="color:#05CD99; font-weight:600;"><i class="fa-solid fa-arrow-trend-up"></i> Stok Terbanyak</h3>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php if(mysqli_num_rows($queryStokBanyak) > 0) {
                        while($stokTop = mysqli_fetch_assoc($queryStokBanyak)) { ?>
                        <div style="display:flex; justify-content:space-between; font-size:13px; padding: 6px 0; border-bottom:1px dashed #E9EDF7;">
                            <span style="color:#2B3674;"><?= htmlspecialchars($stokTop['nama']); ?></span>
                            <span class="badge badge-success"><?= $stokTop['stok']; ?></span>
                        </div>
                    <?php } } ?>
                </div>
            </div>
            <div class="card" style="padding: 20px; background: #5A738E; color: white;">
                <h3 style="color:white; font-weight:600;"><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
                <div style="display:flex; flex-direction:column; gap:12px; margin-top: 15px;">
                    <a href="tambah_produk_collection.php" class="btn-primary" style="background:white; color:#5A738E;"><i class="fa-solid fa-plus"></i> Tambah Produk Baru</a>
                    <a href="tambah_notes.php" class="btn-primary" style="background:rgba(255,255,255,0.2); color:white; border: 1px solid rgba(255,255,255,0.4);"><i class="fa-solid fa-flask"></i> Tambah Bahan Baku</a>
                </div>
            </div>
        </div>

        <!-- TABEL PRODUK COLLECTION -->
        <section class="card">
            <h2>Daftar Produk Collection</h2>
            <form method="GET" action="produk.php">
                <div class="toolbar">
                    <input type="hidden" name="halaman_bahan" value="<?= $halaman_bahan; ?>">
                    <input type="hidden" name="cari_bahan" value="<?= htmlspecialchars($search_bahan); ?>">
                    <input type="hidden" name="kategori_bahan" value="<?= htmlspecialchars($kategori_bahan); ?>">

                    <div style="display:flex; flex-grow: 1; gap:12px;">
                        <input type="text" name="cari_produk" class="search-input" placeholder="Cari nama varian..." value="<?= htmlspecialchars($search_produk); ?>" style="flex-grow:1;">
                        <select name="kategori_produk" class="select-filter" onchange="this.form.submit()">
                            <option value="Semua Kategori" <?= ($kategori_produk == 'Semua Kategori' || empty($kategori_produk)) ? 'selected' : ''; ?>>Semua Kategori</option>
                            <option value="pria" <?= ($kategori_produk == 'pria') ? 'selected' : ''; ?>>Pria</option>
                            <option value="wanita" <?= ($kategori_produk == 'wanita') ? 'selected' : ''; ?>>Wanita</option>
                            <option value="anak" <?= ($kategori_produk == 'anak') ? 'selected' : ''; ?>>Anak-Anak</option>
                        </select>
                        <button type="submit" class="btn-primary" style="padding: 0 24px;">Cari</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th style="text-align: center;">Stok</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no_produk = $offset_produk + 1; 
                        if(mysqli_num_rows($queryProduk) > 0) {
                            while($produk = mysqli_fetch_assoc($queryProduk)) { 
                                $badge_class = "badge-success"; $badge_text = "Aman";
                                if($produk['stok'] == 0) { $badge_class = "badge-danger"; $badge_text = "Habis"; } 
                                elseif($produk['stok'] <= 10) { $badge_class = "badge-warning"; $badge_text = "Sedikit"; }
                                $path_foto = (!empty($produk['foto'])) ? $produk['foto'] : ((!empty($produk['gambar'])) ? $produk['gambar'] : 'assets/Logo_Perscents.png');
                        ?>
                        <tr>
                            <td><?= $no_produk++; ?></td>
                            <td>
                                <div class="prod-info">
                                    <img src="<?= $base_url; ?>/<?= htmlspecialchars($path_foto); ?>" class="prod-thumb">
                                    <span style="font-weight: 600;"><?= htmlspecialchars($produk['nama']); ?></span>
                                </div>
                            </td>
                            <td><span class="badge badge-blue" style="background:#F4F7FE; color:#5A738E;"><?= htmlspecialchars(ucfirst($produk['kategori'])); ?></span></td>
                            <td>Rp <?= number_format($produk['harga_dasar'], 0, ',', '.'); ?></td>
                            <td style="text-align: center; font-weight: 700; font-size: 16px;"><?= $produk['stok']; ?></td>
                            <td style="text-align: center;"><span class="badge <?= $badge_class; ?>"><?= $badge_text; ?></span></td>
                            <td style="text-align: center;">
                                <a href="edit_produk_collection.php?id=<?= $produk['id']; ?>" class="btn-icon btn-edit" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <a href="hapus_produk_collection.php?id=<?= $produk['id']; ?>" class="btn-icon btn-hapus" onclick="return confirm('Hapus produk ini?');" title="Hapus"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php } } else { echo "<tr><td colspan='7' style='text-align:center; padding: 30px;'>Produk tidak ditemukan</td></tr>"; } ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_halaman_produk > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $total_halaman_produk; $i++): ?>
                    <a href="?halaman_produk=<?= $i; ?>&cari_produk=<?= urlencode($search_produk); ?>&kategori_produk=<?= urlencode($kategori_produk); ?><?= $params_bahan_only; ?>">
                        <span class="<?= ($halaman_produk == $i) ? 'active' : ''; ?>"><?= $i; ?></span>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- TABEL BAHAN BAKU -->
        <section class="card">
            <h2>Daftar Bahan Baku Custom (Notes)</h2>
            <form method="GET" action="produk.php">
                <div class="toolbar">
                    <input type="hidden" name="halaman_produk" value="<?= $halaman_produk; ?>">
                    <input type="hidden" name="cari_produk" value="<?= htmlspecialchars($search_produk); ?>">
                    <input type="hidden" name="kategori_produk" value="<?= htmlspecialchars($kategori_produk); ?>">
                    
                    <div style="display:flex; flex-grow: 1; gap:12px;">
                        <input type="text" name="cari_bahan" class="search-input" placeholder="Cari nama notes aroma..." value="<?= htmlspecialchars($search_bahan); ?>" style="flex-grow:1;">
                        <select name="kategori_bahan" class="select-filter" onchange="this.form.submit()">
                            <option value="Semua Kategori" <?= ($kategori_bahan == 'Semua Kategori' || empty($kategori_bahan)) ? 'selected' : ''; ?>>Semua Kategori</option>
                            <option value="pria" <?= ($kategori_bahan == 'pria') ? 'selected' : ''; ?>>Pria</option>
                            <option value="wanita" <?= ($kategori_bahan == 'wanita') ? 'selected' : ''; ?>>Wanita</option>
                            <option value="unisex" <?= ($kategori_bahan == 'unisex') ? 'selected' : ''; ?>>Unisex</option>
                        </select>
                        <button type="submit" class="btn-primary" style="padding: 0 24px;">Cari</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Notes</th>
                            <th>Kategori</th>
                            <th>Harga Custom</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no_bahan = $offset_bahan + 1;
                        if($queryBahan && mysqli_num_rows($queryBahan) > 0) {
                            while($bahan = mysqli_fetch_assoc($queryBahan)) { 
                        ?>
                        <tr>
                            <td><?= $no_bahan++; ?></td>
                            <td>
                                <div class="prod-info">
                                    <div class="prod-thumb" style="display:flex; align-items:center; justify-content:center; background:#E9EDF7; color:#5A738E;"><i class="fa-solid fa-flask"></i></div>
                                    <span style="font-weight: 600;"><?= htmlspecialchars($bahan['nama']); ?></span>
                                </div>
                            </td>
                            <td><span class="badge badge-blue" style="background:#F4F7FE; color:#5A738E;"><?= htmlspecialchars(ucfirst($bahan['kategori'])); ?></span></td>
                            <td>Rp <?= number_format($bahan['harga'], 0, ',', '.'); ?></td>
                            <td style="text-align: center;"><span class="badge badge-success">Tersedia</span></td>
                            <td style="text-align: center;">
                                <a href="edit_notes.php?id=<?= $bahan['id']; ?>" class="btn-icon btn-edit"><i class="fa-solid fa-pen"></i></a>
                                <a href="hapus_notes.php?id=<?= $bahan['id']; ?>" class="btn-icon btn-hapus" onclick="return confirm('Hapus notes aroma ini?');"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php } } else { echo "<tr><td colspan='6' style='text-align:center; padding: 30px;'>Notes aroma tidak ditemukan.</td></tr>"; } ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_halaman_bahan > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $total_halaman_bahan; $i++): ?>
                    <a href="?halaman_bahan=<?= $i; ?>&cari_bahan=<?= urlencode($search_bahan); ?>&kategori_bahan=<?= urlencode($kategori_bahan); ?><?= $params_produk_only; ?>">
                        <span class="<?= ($halaman_bahan == $i) ? 'active' : ''; ?>"><?= $i; ?></span>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </section>
    </main>

</body>
</html>
<?php
require_once '../config/cek_akses.php';
require_once '../config/koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query = mysqli_query($conn, "SELECT * FROM mst_ketahanan WHERE id = $id");
$data = mysqli_fetch_assoc($query);

if (!$data) { echo "<script>alert('Data tidak ditemukan!'); window.location.href='variant_management.php';</script>"; exit; }

if (isset($_POST['submit'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $durasi = mysqli_real_escape_string($conn, $_POST['durasi']);
    $tambahan_harga = (int)$_POST['tambahan_harga'];

    $query_update = "UPDATE mst_ketahanan SET nama = '$nama', durasi = '$durasi', tambahan_harga = '$tambahan_harga' WHERE id = $id";
    if (mysqli_query($conn, $query_update)) { echo "<script>alert('Data ketahanan berhasil diperbarui!'); window.location.href = 'variant_management.php';</script>"; } 
    else { echo "<script>alert('Gagal update data: " . mysqli_error($conn) . "');</script>"; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ketahanan Aroma - PERSCENTS</title>

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

    <style>
        .form-card { max-width: 800px; margin: 0 auto; background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #2B3674; }
        textarea.form-control { resize: vertical; min-height: 120px; }
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
            <h1 class="page-title">Edit Ketahanan Aroma</h1>
            <a href="variant_management.php" class="btn-primary" style="background:#E9EDF7; color:#5A738E;"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </header>
        <div class="form-card">
            <form action="" method="POST" enctype="multipart/form-data">
                
<div class="form-group"><label>Nama Tipe Ketahanan</label><input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data['nama']); ?>" required></div>
<div class="form-group"><label>Estimasi Durasi</label><input type="text" name="durasi" class="form-control" value="<?= htmlspecialchars($data['durasi']); ?>" placeholder="Contoh: 8 - 12 Jam" required></div>
<div class="form-group"><label>Tambahan Harga (Rp)</label><input type="number" name="tambahan_harga" class="form-control" value="<?= $data['tambahan_harga']; ?>" required></div>

                <button type="submit" name="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 16px; margin-top: 10px;"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
            </form>
        </div>
    </main>
</body>
</html>
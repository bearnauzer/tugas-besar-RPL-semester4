<?php
require_once '../config/cek_akses.php';
require_once '../config/koneksi.php';

// Tangkap ID dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    echo "<script>alert('Data tidak valid!'); window.location.href='produk.php';</script>";
    exit;
}

// Jika tombol "Yakin, Hapus" ditekan (mengirim parameter confirm=yes)
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    // Eksekusi penghapusan data
    $query_delete = "DELETE FROM produk_collection WHERE id = $id";
    
    if (mysqli_query($conn, $query_delete)) {
        echo "<script>alert('Produk berhasil dihapus secara permanen!'); window.location.href='produk.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data: " . mysqli_error($conn) . "'); window.location.href='produk.php';</script>";
    }
    exit;
}

// Ambil nama produk untuk ditampilkan di pesan konfirmasi
$query_nama = mysqli_query($conn, "SELECT nama FROM produk_collection WHERE id = $id");
$data = mysqli_fetch_assoc($query_nama);

// Jika ID tidak ditemukan di database, langsung tendang balik
if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location.href='produk.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Hapus - PERSCENTS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; }
        
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; max-width: 420px; width: 100%; border: 1px solid #e2e8f0; }
        
        .icon-danger { width: 60px; height: 60px; background-color: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        /* Icon Tempat Sampah SVG */
        .icon-danger svg { width: 30px; height: 30px; stroke: currentColor; stroke-width: 2; fill: none; stroke-linecap: round; stroke-linejoin: round; }
        
        h2 { color: #1e293b; font-size: 20px; margin-bottom: 10px; }
        p { color: #64748b; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
        .highlight-name { font-weight: bold; color: #ef4444; background: #fee2e2; padding: 2px 6px; border-radius: 4px; }
        
        .btn-group { display: flex; gap: 15px; justify-content: center; }
        .btn { padding: 12px 24px; border-radius: 6px; font-weight: bold; font-size: 14px; text-decoration: none; cursor: pointer; transition: 0.2s; width: 100%; display: inline-block; }
        
        .btn-batal { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .btn-batal:hover { background-color: #e2e8f0; }
        
        .btn-yakin { background-color: #ef4444; color: white; border: 1px solid #ef4444; }
        .btn-yakin:hover { background-color: #dc2626; }
    </style>
</head>
<body>

    <div class="card">
        <div class="icon-danger">
            <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
        </div>
        <h2>Konfirmasi Penghapusan</h2>
        <p>Apakah kamu yakin ingin menghapus produk <br> <span class="highlight-name">"<?= htmlspecialchars($data['nama']); ?>"</span>?<br>Data yang dihapus tidak dapat dikembalikan.</p>
        
        <div class="btn-group">
            <a href="javascript:history.back()" class="btn btn-batal">Batal</a>
            <a href="hapus_produk_collection.php?id=<?= $id; ?>&confirm=yes" class="btn btn-yakin">Yakin, Hapus</a>
        </div>
    </div>

</body>
</html>
<?php
require_once '../config/cek_akses.php';
require_once '../config/koneksi.php';

$id_pesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// =========================================================================
// PASTIKAN KOLOM status_pengambilan ADA DI DATABASE (Mencegah Error)
// =========================================================================
$check = mysqli_query($conn, "SHOW COLUMNS FROM pesanan LIKE 'status_pengambilan'");
if($check && mysqli_num_rows($check) === 0) {
    mysqli_query($conn, "ALTER TABLE pesanan ADD status_pengambilan ENUM('belum_diambil','diambil') NOT NULL DEFAULT 'belum_diambil' AFTER status");
}

// Proses Update Status Racik
if (isset($_POST['update_status_racik'])) {
    $id_detail = (int)$_POST['id_detail'];
    $status_racik_baru = mysqli_real_escape_string($conn, $_POST['status_racik']);
    mysqli_query($conn, "UPDATE detail_pesanan SET status_racik = '$status_racik_baru' WHERE id = $id_detail");

    $qStatusPesanan = mysqli_query($conn, "
        SELECT
            COUNT(*) AS total_item,
            SUM(status_racik = 'selesai') AS total_selesai,
            SUM(status_racik = 'diracik') AS total_diracik
        FROM detail_pesanan
        WHERE pesanan_id = $id_pesanan
    ");
    $statusPesanan = mysqli_fetch_assoc($qStatusPesanan);
    $total_item = (int)($statusPesanan['total_item'] ?? 0);
    $total_selesai = (int)($statusPesanan['total_selesai'] ?? 0);
    $total_diracik = (int)($statusPesanan['total_diracik'] ?? 0);

    if ($total_item > 0 && $total_diracik === 0 && $total_selesai === 0) {
        mysqli_query($conn, "UPDATE pesanan SET status = 'lunas' WHERE id = $id_pesanan AND status NOT IN ('pending', 'dibatalkan')");
    }

    echo "<script>window.location.href='detail_pesanan.php?id=$id_pesanan';</script>";
    exit;
}

// Proses Tandai Sudah Diambil
if (isset($_POST['update_pengambilan'])) {
    mysqli_query($conn, "UPDATE pesanan SET status_pengambilan = 'diambil' WHERE id = $id_pesanan");
    echo "<script>window.location.href='detail_pesanan.php?id=$id_pesanan';</script>";
    exit;
}

$queryPesanan = mysqli_query($conn, "SELECT * FROM pesanan WHERE id = $id_pesanan");
$pesanan = mysqli_fetch_assoc($queryPesanan);

if (!$pesanan) {
    echo "<script>alert('Data pesanan tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

$queryDetail = mysqli_query($conn, "
    SELECT 
        dp.*, 
        pc.foto as foto_collection,
        mk.nama as nama_ketahanan, mk.durasi,
        mu.nama as nama_ukuran, mu.ml
    FROM detail_pesanan dp 
    LEFT JOIN produk_collection pc ON dp.produk_id = pc.id 
    LEFT JOIN mst_ketahanan mk ON dp.id_ketahanan = mk.id
    LEFT JOIN mst_ukuran mu ON dp.id_ukuran = mu.id
    WHERE dp.pesanan_id = $id_pesanan
");

// =========================================================================
// LOGIKA AUTO-CHECKLIST SOP & PROGRESS BAR
// =========================================================================
$items = [];
$semua_selesai = true; 
$ada_yang_diracik = false;
$jumlah_selesai = 0;

if($queryDetail && mysqli_num_rows($queryDetail) > 0) {
    while($row = mysqli_fetch_assoc($queryDetail)) {
        $items[] = $row;
        
        if($row['status_racik'] !== 'selesai') {
            $semua_selesai = false;
        } else {
            $jumlah_selesai++;
        }

        if($row['status_racik'] == 'diracik') {
            $ada_yang_diracik = true;
        }
    }
} else {
    $semua_selesai = false;
}
$jumlah_item = count($items);

// Logika Visual Stepper (Progress Bar) -> DIBUAT 4 TAHAP
$step1_active = 'active'; 
$step2_active = ($ada_yang_diracik || $jumlah_selesai > 0) ? 'active' : '';
$step3_active = ($semua_selesai && $jumlah_item > 0) ? 'active' : '';
$step4_active = ($pesanan['status_pengambilan'] == 'diambil') ? 'active' : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order: #<?= htmlspecialchars($pesanan['kode_pesanan']); ?> - PERSCENTS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; }
        .app-layout { display: flex; min-height: 100vh; }
        
        /* SIDEBAR */
        .sidebar { width: 260px; background-color: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; padding: 25px 20px; position: sticky; top: 0; height: 100vh; z-index: 10; }
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

        /* KONTEN UTAMA */
        .main-content { flex: 1; padding: 30px 40px; overflow-y: auto; }
        .header-action { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-back { display: flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .btn-back:hover { color: #1e293b; }

        /* TRACKER PROGRESS BAR 4 TAHAP */
        .order-tracker { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .tracker-title { font-size: 16px; color: #1e293b; font-weight: bold; margin-bottom: 25px; }
        .stepper { display: flex; justify-content: space-between; position: relative; max-width: 700px; margin: 0 auto; }
        /* Garis penghubung */
        .stepper::before { content: ''; position: absolute; top: 20px; left: 12%; right: 12%; height: 2px; background: #e2e8f0; z-index: 1; }
        
        .step { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 10px; width: 25%; }
        .step-circle { width: 42px; height: 42px; border-radius: 50%; background: white; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #94a3b8; font-size: 16px; transition: 0.3s; }
        .step-label { font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; text-align: center; }
        
        /* State Aktif (Warna Biru) */
        .step.active .step-circle { background: #3b82f6; border-color: #3b82f6; color: white; box-shadow: 0 0 0 4px #eff6ff; }
        .step.active .step-label { color: #1e293b; }
        
        /* State Selesai Semua (Warna Hijau) */
        .stepper.all-done .step.active .step-circle { background: #16a34a; border-color: #16a34a; box-shadow: 0 0 0 4px #dcfce7; }

        /* GRID LAYOUT (Kiri Resep, Kanan Checklist) */
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; }

        .recipe-list { display: flex; flex-direction: column; gap: 20px; }
        .recipe-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.02); padding: 25px; display: flex; flex-direction: column; gap: 20px; position: relative; overflow: hidden; }
        .recipe-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 6px; background-color: #3b82f6; }
        .recipe-card.is-custom::before { background-color: #9333ea; }
        .recipe-card.is-selesai::before { background-color: #16a34a; }
        .recipe-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; }
        .recipe-main-info { display: flex; gap: 20px; align-items: center; }
        .recipe-thumb { width: 80px; height: 80px; border-radius: 10px; object-fit: cover; border: 1px solid #e2e8f0; padding: 2px; background: #f8fafc; }
        .recipe-title h3 { font-size: 22px; color: #1e293b; margin-bottom: 5px; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .badge-blue { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .badge-purple { background-color: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        
        .recipe-qty { text-align: center; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 20px; border-radius: 10px; }
        .recipe-qty span { display: block; font-size: 12px; color: #64748b; font-weight: bold; text-transform: uppercase; }
        .recipe-qty strong { font-size: 28px; color: #1e293b; line-height: 1; }
        
        .recipe-specs { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .spec-box p { font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 4px; text-transform: uppercase; }
        .spec-box h4 { font-size: 16px; color: #1e293b; }
        
        /* Aksen Aroma Styling */
        .recipe-notes { background: #faf5ff; border: 1px dashed #d8b4fe; padding: 15px; border-radius: 8px; margin-top: 5px; }
        .recipe-notes p { font-size: 12px; color: #7e22ce; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; }
        .aksen-container { display: flex; flex-wrap: wrap; gap: 8px; }
        .aksen-badge { background: #e0e7ff; color: #4338ca; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        
        .recipe-action { display: flex; justify-content: flex-end; align-items: center; gap: 15px; padding-top: 10px; border-top: 1px solid #f1f5f9; }
        .recipe-action label { font-size: 14px; font-weight: 600; color: #475569; }
        .select-status { padding: 10px 15px; border: 2px solid #cbd5e1; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; min-width: 200px; transition: 0.2s; }
        
        /* PANEL SOP CHECKLIST */
        .sop-panel { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; position: sticky; top: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .sop-header { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; }
        .sop-header h3 { font-size: 18px; color: #1e293b; }
        .checklist { display: flex; flex-direction: column; gap: 12px; }
        .check-item { display: flex; align-items: flex-start; gap: 12px; cursor: pointer; }
        .check-item input[type="checkbox"] { width: 18px; height: 18px; margin-top: 2px; cursor: pointer; accent-color: #5A738E; }
        .check-item label { font-size: 14px; color: #475569; cursor: pointer; line-height: 1.4; transition: 0.2s; }
        .check-item input[type="checkbox"]:checked + label { text-decoration: line-through; color: #94a3b8; }
        
        /* INFO PELANGGAN CARD */
        .customer-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 25px; display: flex; gap: 40px; align-items: center; }
        .cust-info { display: flex; align-items: center; gap: 12px; }
        .cust-icon { width: 40px; height: 40px; background: #f1f5f9; color: #3b82f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .cust-text p { font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 2px; }
        .cust-text h4 { font-size: 15px; color: #1e293b; }

        .btn-print { margin-top: 25px; width: 100%; padding: 12px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-print:hover { background: #e2e8f0; }

        .btn-success { background-color: #16a34a; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; font-size: 14px; transition: 0.2s; }
        .btn-success:hover { background-color: #15803d; }

        @media print { .sidebar, .btn-back, .recipe-action, .btn-print, .order-tracker, .btn-success { display: none !important; } .main-content { padding: 0 !important; } .content-grid { grid-template-columns: 1fr; } .recipe-card { break-inside: avoid; border: 1px solid #000; } .sop-panel { break-before: page; } }
    </style>
</head>
<body>

    <div class="app-layout">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-logo"><img src="<?= $base_url; ?>/assets/Logo_Perscents.png" alt="Logo"></div>
                <div class="brand-text">
                    <h1>PERSCENTS</h1>
                    <p>Peracik Dashboard</p>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="active"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg> Dashboard</a>
                <a href="panduan.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg> Buku Panduan</a>
                <a href="logout_peracik.php" class="logout"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> Keluar</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header-action">
                <a href="index.php" class="btn-back">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Kembali ke Antrean
                </a>
                <div style="text-align: right;">
                    <h2 style="font-size: 20px; color: #1e293b;">Work Order: #<?= htmlspecialchars($pesanan['kode_pesanan']); ?></h2>
                    <p style="font-size: 13px; color: #64748b;">Waktu Masuk: <?= date('d M Y, H:i', strtotime($pesanan['created_at'])); ?> WIB</p>
                </div>
            </div>

            <div class="order-tracker">
                <h3 class="tracker-title">Status Penyelesaian Pesanan</h3>
                <div class="stepper <?= $step4_active ? 'all-done' : ''; ?>">
                    <div class="step <?= $step1_active; ?>">
                        <div class="step-circle">1</div>
                        <div class="step-label">Menunggu<br>Antrean</div>
                    </div>
                    <div class="step <?= $step2_active; ?>">
                        <div class="step-circle">2</div>
                        <div class="step-label">Sedang<br>Diracik</div>
                    </div>
                    <div class="step <?= $step3_active; ?>">
                        <div class="step-circle">3</div>
                        <div class="step-label">Siap<br>Diambil</div>
                    </div>
                    <div class="step <?= $step4_active; ?>">
                        <div class="step-circle">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                        <div class="step-label">Sudah<br>Diambil</div>
                    </div>
                </div>
            </div>

            <div class="customer-card">
                <div class="cust-info" style="flex: 1;">
                    <div class="cust-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <div class="cust-text">
                        <p>Nama Pelanggan</p>
                        <h4><?= htmlspecialchars($pesanan['nama_pemesan']); ?></h4>
                    </div>
                </div>

                <div class="cust-info" style="flex: 1;">
                    <div class="cust-icon" style="background: #dcfce7; color: #16a34a;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    </div>
                    <div class="cust-text">
                        <p>Nomor HP</p>
                        <h4><?= htmlspecialchars($pesanan['no_hp'] ?? '-'); ?></h4>
                    </div>
                </div>
                
                <div style="flex: 1.5; display: flex; justify-content: flex-end;">
                    <?php if($pesanan['status_pengambilan'] == 'diambil'): ?>
                        <div style="background: #dcfce7; color: #16a34a; padding: 12px 20px; border-radius: 8px; font-weight: bold; font-size: 14px; border: 1px solid #bbf7d0;">
                            Pesanan Telah Diambil Pelanggan
                        </div>
                    <?php elseif($semua_selesai): ?>
                        <form method="POST" style="margin: 0; width: 100%;">
                            <input type="hidden" name="update_pengambilan" value="1">
                            <button type="submit" class="btn-success" style="width: 100%;" onclick="return confirm('Apakah Anda yakin pesanan ini sudah diambil oleh pelanggan?');">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
                                Tandai Pesanan Sudah Diambil
                            </button>
                        </form>
                    <?php else: ?>
                        <div style="background: #f1f5f9; color: #94a3b8; padding: 12px 20px; border-radius: 8px; font-weight: bold; font-size: 13px; text-align: center; border: 1px dashed #cbd5e1;">
                            Selesaikan racikan untuk membuka tombol
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content-grid">
                <div class="recipe-list">
                    <?php 
                    if($jumlah_item > 0) {
                        foreach($items as $item) { 
                            $path_foto = 'assets/Logo_Perscents.png'; 
                            if($item['tipe'] == 'collection' && !empty($item['foto_collection'])) {
                                $path_foto = $item['foto_collection'];
                            }
                            
                            $kelas_pita = '';
                            $badge_tipe = "<span class='badge badge-blue'>Katalog</span>";
                            if($item['tipe'] == 'custom') {
                                $kelas_pita = 'is-custom';
                                $badge_tipe = "<span class='badge badge-purple'>Custom</span>";
                            }
                            if($item['status_racik'] == 'selesai') {
                                $kelas_pita = 'is-selesai';
                            }
                    ?>
                    
                    <div class="recipe-card <?= $kelas_pita; ?>">
                        <div class="recipe-header">
                            <div class="recipe-main-info">
                                <img src="<?= $base_url; ?>/<?= htmlspecialchars($path_foto); ?>" class="recipe-thumb" alt="thumb">
                                <div class="recipe-title">
                                    <h3><?= htmlspecialchars($item['nama_parfum']); ?></h3>
                                    <?= $badge_tipe; ?>
                                </div>
                            </div>
                            <div class="recipe-qty">
                                <span>Jumlah</span>
                                <strong><?= $item['jumlah']; ?>x</strong>
                            </div>
                        </div>

                        <div class="recipe-specs">
                            <div class="spec-box">
                                <p>Ukuran Botol</p>
                                <h4><?= htmlspecialchars($item['nama_ukuran'] ?? '-'); ?> (<?= $item['ml'] ?? '0'; ?> ml)</h4>
                            </div>
                            <div class="spec-box">
                                <p>Tingkat Ketahanan</p>
                                <h4><?= htmlspecialchars($item['nama_ketahanan'] ?? '-'); ?> (<?= htmlspecialchars($item['durasi'] ?? '-'); ?>)</h4>
                            </div>
                        </div>

                        <?php if($item['tipe'] == 'custom' && !empty($item['notes_dipilih'])): ?>
                        <div class="recipe-notes">
                            <p>Aksen Aroma (Notes Terpilih):</p>
                            <div class="aksen-container">
                                <?php 
                                $notes_array = explode(',', $item['notes_dipilih']);
                                foreach($notes_array as $n) {
                                    echo "<span class='aksen-badge'>" . trim(htmlspecialchars($n)) . "</span>";
                                }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="recipe-action">
                            <label>Ubah Status Item Ini:</label>
                            <form method="POST">
                                <input type="hidden" name="id_detail" value="<?= $item['id']; ?>">
                                <input type="hidden" name="update_status_racik" value="1">
                                <select name="status_racik" class="select-status" data-old-value="<?= $item['status_racik']; ?>" onchange="checkSOP(this)" 
                                    style="color: <?= ($item['status_racik'] == 'selesai') ? '#16a34a' : (($item['status_racik'] == 'diracik') ? '#ca8a04' : '#475569'); ?>; border-color: <?= ($item['status_racik'] == 'selesai') ? '#bbf7d0' : (($item['status_racik'] == 'diracik') ? '#fef08a' : '#cbd5e1'); ?>;">
                                    <option value="menunggu" <?= ($item['status_racik'] == 'menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                    <option value="diracik" <?= ($item['status_racik'] == 'diracik') ? 'selected' : ''; ?>>Sedang Diracik</option>
                                    <option value="selesai" <?= ($item['status_racik'] == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                </select>
                            </form>
                        </div>
                    </div>

                    <?php 
                        }
                    } else {
                        echo "<p>Detail item tidak ditemukan.</p>";
                    }
                    ?>
                </div>

                <div>
                    <div class="sop-panel">
                        <div class="sop-header">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5A738E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            <h3>SOP Peracikan</h3>
                        </div>
                        
                        <div class="checklist">
                            <div class="check-item">
                                <input type="checkbox" id="sop1" class="sop-checkbox" <?= $semua_selesai ? 'checked' : ''; ?>>
                                <label for="sop1">Siapkan dan sterilkan botol sesuai ukuran pesanan.</label>
                            </div>
                            <div class="check-item">
                                <input type="checkbox" id="sop2" class="sop-checkbox" <?= $semua_selesai ? 'checked' : ''; ?>>
                                <label for="sop2">Takar *base* campuran sesuai tingkat ketahanan.</label>
                            </div>
                            <div class="check-item">
                                <input type="checkbox" id="sop3" class="sop-checkbox" <?= $semua_selesai ? 'checked' : ''; ?>>
                                <label for="sop3">Masukkan notes/bibit parfum sesuai formula pesanan.</label>
                            </div>
                            <div class="check-item">
                                <input type="checkbox" id="sop4" class="sop-checkbox" <?= $semua_selesai ? 'checked' : ''; ?>>
                                <label for="sop4">Aduk/kocok campuran hingga menyatu sempurna.</label>
                            </div>
                            <div class="check-item">
                                <input type="checkbox" id="sop5" class="sop-checkbox" <?= $semua_selesai ? 'checked' : ''; ?>>
                                <label for="sop5">Quality Control (Cek aroma & kemasan tidak bocor).</label>
                            </div>
                        </div>

                        <button onclick="window.print()" class="btn-print">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                            Cetak Resep
                        </button>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        function checkSOP(selectElement) {
            if (selectElement.value === 'selesai') {
                let checkboxes = document.querySelectorAll('.sop-checkbox');
                let allChecked = true;
                
                checkboxes.forEach(function(cb) {
                    if (!cb.checked) {
                        allChecked = false;
                    }
                });

                if (!allChecked) {
                    alert('⚠️ EITS TUNGGU DULU! \nKamu harus menyelesaikan dan mencentang semua SOP Peracikan di panel kanan sebelum bisa mengubah status menjadi Selesai.');
                    selectElement.value = selectElement.getAttribute('data-old-value');
                    return false;
                }
            }
            selectElement.form.submit();
        }
    </script>

</body>
</html>
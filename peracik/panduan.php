<?php
require_once '../config/cek_akses.php';
require_once '../config/koneksi.php';

// Ambil semua data Master untuk ditampilkan di Panduan
$qProduk = mysqli_query($conn, "SELECT * FROM produk_collection ORDER BY tipe, nama ASC");
$qNotes = mysqli_query($conn, "SELECT * FROM notes_aroma_custom ORDER BY nama ASC");
$qUkuran = mysqli_query($conn, "SELECT * FROM mst_ukuran ORDER BY ml ASC");
$qKetahanan = mysqli_query($conn, "SELECT * FROM mst_ketahanan ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Panduan Peracik - PERSCENTS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; }
        .app-layout { display: flex; min-height: 100vh; }

        /* SIDEBAR (Konsisten) */
        .sidebar { width: 260px; background-color: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; padding: 25px 20px; position: sticky; top: 0; height: 100vh; }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 40px; padding-left: 5px; }
        .brand-logo { width: 40px; height: 40px; background-color: #5A738E; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .brand-logo img { width: 100%; height: 100%; object-fit: cover; }
        .brand-text h1 { font-size: 20px; color: #1e293b; font-weight: 800; }
        .brand-text p { font-size: 11px; color: #64748b; margin-top: 2px; }
        .sidebar-nav { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: #64748b; font-size: 14px; font-weight: 600; transition: 0.2s; }
        .sidebar-nav a:hover { background-color: #f1f5f9; color: #5A738E; }
        .sidebar-nav a.active { background-color: #5A738E; color: white; box-shadow: 0 4px 6px rgba(90, 115, 142, 0.25); }
        .sidebar-nav a.active svg { stroke: white; }
        .sidebar-nav .logout { margin-top: auto; color: #ef4444; }

        /* KONTEN UTAMA */
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .page-header { margin-bottom: 30px; }
        .page-header h2 { font-size: 28px; color: #1e293b; font-weight: 800; margin-bottom: 5px; }
        .page-header p { color: #64748b; font-size: 15px; }

        /* TABS NAVIGATION */
        .tabs { display: flex; gap: 10px; border-bottom: 2px solid #e2e8f0; margin-bottom: 30px; }
        .tab-btn { background: none; border: none; padding: 12px 24px; font-size: 15px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: 0.2s; }
        .tab-btn:hover { color: #5A738E; }
        .tab-btn.active { color: #5A738E; border-bottom-color: #5A738E; }

        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* KARTU SOP */
        .sop-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .sop-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .sop-card h3 { font-size: 20px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #cbd5e1; display: flex; align-items: center; gap: 10px; }
        .sop-card h3.katalog { color: #0369a1; }
        .sop-card h3.custom { color: #7e22ce; }
        .step-list { display: flex; flex-direction: column; gap: 15px; margin-top: 20px; }
        .step-item { display: flex; gap: 15px; align-items: flex-start; }
        .step-num { width: 28px; height: 28px; background: #f1f5f9; color: #475569; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; font-size: 13px; }
        .step-text h4 { font-size: 15px; color: #1e293b; margin-bottom: 4px; }
        .step-text p { font-size: 13.5px; color: #64748b; line-height: 1.5; }

        /* GRID KATALOG & NOTES */
        .katalog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .katalog-card { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: 0.2s; }
        .katalog-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.05); border-color: #cbd5e1; }
        .katalog-card img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-bottom: 15px; background: #f8fafc; padding: 2px; border: 1px solid #e2e8f0; }
        .katalog-card h4 { font-size: 15px; color: #1e293b; margin-bottom: 5px; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .badge-blue { background: #e0f2fe; color: #0369a1; }
        .badge-purple { background: #f3e8ff; color: #7e22ce; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        /* TABEL TAKARAN */
        .tabel-box { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 30px; }
        .tabel-box h3 { padding: 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 16px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { font-size: 12px; text-transform: uppercase; color: #64748b; background: white; }
        td { font-size: 14px; color: #333; }
        tr:last-child td { border-bottom: none; }

        /* MODAL POPUP */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; }
        .modal-overlay.active { display: flex; }
        .modal-card { background: white; width: 100%; max-width: 500px; border-radius: 12px; overflow: hidden; transform: scale(0.95); transition: 0.2s; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .modal-overlay.active .modal-card { transform: scale(1); }
        .modal-header { padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { font-size: 18px; color: #1e293b; }
        .close-btn { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; line-height: 1; }
        .close-btn:hover { color: #ef4444; }
        .modal-body { padding: 30px 20px; text-align: center; }
        .modal-body img { width: 120px; height: 120px; object-fit: cover; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .modal-body h2 { font-size: 24px; color: #1e293b; margin-bottom: 10px; }
        .modal-desc { color: #64748b; font-size: 14px; line-height: 1.6; margin-top: 15px; padding: 15px; background: #f8fafc; border-radius: 8px; text-align: left; }
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
                <a href="index.php">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
                    Dashboard
                </a>
                <a href="panduan.php" class="active">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                    Buku Panduan
                </a>
                <a href="logout_peracik.php" class="logout"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> Keluar</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h2>Buku Panduan & SOP Peracikan</h2>
                <p>Pusat referensi katalog, takaran, dan standar operasional tim produksi.</p>
            </div>

            <div class="tabs">
                <button class="tab-btn active" onclick="openTab(event, 'tab-sop')">SOP Peracikan</button>
                <button class="tab-btn" onclick="openTab(event, 'tab-takaran')">Standar Takaran</button>
                <button class="tab-btn" onclick="openTab(event, 'tab-katalog')">Katalog Produk</button>
                <button class="tab-btn" onclick="openTab(event, 'tab-notes')">Ensiklopedia Notes</button>
            </div>

            <div id="tab-sop" class="tab-content active">
                <div class="sop-grid">
                    <div class="sop-card">
                        <h3 class="katalog">SOP Racikan Katalog (Collection)</h3>
                        <div class="step-list">
                            <div class="step-item">
                                <div class="step-num">1</div>
                                <div class="step-text">
                                    <h4>Siapkan Botol</h4>
                                    <p>Pilih botol parfum sesuai ukuran pesanan. Pastikan botol dalam kondisi bersih dan siap digunakan sebelum proses peracikan dimulai.</p>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-num">2</div>
                                <div class="step-text">
                                    <h4>Ambil Formula Varian</h4>
                                    <p>Periksa nama varian yang dipesan, misalnya MILO - Kucing Baik. Ambil campuran bibit parfum yang sesuai dari rak penyimpanan koleksi.</p>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-num">3</div>
                                <div class="step-text">
                                    <h4>Racik Parfum</h4>
                                    <p>Campurkan bibit parfum dengan alkohol (base) sesuai tingkat ketahanan yang dipilih pelanggan. Gunakan takaran yang telah ditetapkan pada Standar Takaran.</p>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-num">4</div>
                                <div class="step-text">
                                    <h4>Penyelesaian</h4>
                                    <p>Setelah tercampur rata, tutup dan segel botol dengan rapi. Tempelkan label varian yang sesuai, lalu ubah status pesanan di sistem menjadi Selesai.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sop-card">
                        <h3 class="custom">SOP Racikan Custom (Notes)</h3>
                        <div class="step-list">
                            <div class="step-item">
                                <div class="step-num">1</div>
                                <div class="step-text">
                                    <h4>Cek Detail Pesanan</h4>
                                    <p>Buka pesanan pelanggan dan perhatikan kombinasi notes yang dipilih, mulai dari top notes, middle notes, hingga base notes.</p>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-num">2</div>
                                <div class="step-text">
                                    <h4>Siapkan Takaran</h4>
                                    <p>Tentukan komposisi masing-masing notes sesuai resep yang dipesan. Pastikan perpaduan aroma tetap seimbang dan harmonis saat digunakan.</p>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-num">3</div>
                                <div class="step-text">
                                    <h4>Proses Peracikan</h4>
                                    <p>Campurkan setiap notes secara bertahap hingga menyatu dengan baik. Setelah itu, tambahkan base sesuai tingkat ketahanan yang dipilih pelanggan.</p>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-num">4</div>
                                <div class="step-text">
                                    <h4>Pemeriksaan Akhir</h4>
                                    <p>Diamkan racikan sejenak agar aroma lebih menyatu. Lakukan uji semprot untuk memastikan hasil sesuai formulasi, lalu segel botol dan pasang label Custom.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tab-takaran" class="tab-content">
                <div class="tabel-box">
                    <h3>Standar Ukuran Botol (Volume)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Ukuran</th>
                                <th>Volume Cairan (ml)</th>
                                <th>Peruntukan Botol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($uk = mysqli_fetch_assoc($qUkuran)){ ?>
                            <tr>
                                <td><b><?= htmlspecialchars($uk['nama']); ?></b></td>
                                <td><?= htmlspecialchars($uk['ml']); ?> ml</td>
                                <td>Botol kaca spray <?= htmlspecialchars($uk['ml']); ?> ml tipe standar.</td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div class="tabel-box">
                    <h3>Standar Tingkat Ketahanan (Longevity)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Tipe Ketahanan</th>
                                <th>Estimasi Durasi Aroma</th>
                                <th>Instruksi Rasio Campuran (Bibit : Pelarut)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($ket = mysqli_fetch_assoc($qKetahanan)){ ?>
                            <tr>
                                <td><span class="badge badge-purple"><?= htmlspecialchars($ket['nama']); ?></span></td>
                                <td><b><?= htmlspecialchars($ket['durasi']); ?></b></td>
                                <td><i>Silakan sesuaikan persentase konsentrat (fragrance oil) berdasarkan buku panduan rasio lab untuk mencapai durasi ini.</i></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-katalog" class="tab-content">
                <div class="katalog-grid">
                    <?php 
                    mysqli_data_seek($qProduk, 0); // Reset pointer
                    while($prod = mysqli_fetch_assoc($qProduk)){ 
                        $foto = !empty($prod['foto']) ? $prod['foto'] : 'assets/Logo_Perscents.png';
                        // Escaping data untuk JavaScript
                        $n = htmlspecialchars($prod['nama'], ENT_QUOTES);
                        $d = htmlspecialchars($prod['deskripsi'], ENT_QUOTES);
                        $k = htmlspecialchars($prod['kategori'], ENT_QUOTES);
                    ?>
                    <div class="katalog-card" onclick="bukaModal('<?= $base_url.'/'.$foto; ?>', '<?= $n; ?>', '<?= $k; ?>', 'Collection', '<?= $d; ?>')">
                        <img src="<?= $base_url; ?>/<?= $foto; ?>" alt="Gambar">
                        <h4><?= $n; ?></h4>
                        <span class="badge badge-blue"><?= ucfirst($prod['tipe']); ?></span>
                    </div>
                    <?php } ?>
                </div>
            </div>

            <div id="tab-notes" class="tab-content">
                <div class="katalog-grid">
                    <?php 
                    mysqli_data_seek($qNotes, 0); // Reset pointer
                    while($note = mysqli_fetch_assoc($qNotes)){ 
                        $n = htmlspecialchars($note['nama'], ENT_QUOTES);
                        $d = htmlspecialchars($note['deskripsi'], ENT_QUOTES);
                        $k = htmlspecialchars($note['kategori'], ENT_QUOTES);
                    ?>
                    <div class="katalog-card" onclick="bukaModal('<?= $base_url; ?>/assets/Logo_Perscents.png', '<?= $n; ?>', '<?= $k; ?>', 'Notes Aroma', '<?= $d; ?>')">
                        <div style="font-size: 40px; margin-bottom: 10px;">🧪</div>
                        <h4><?= $n; ?></h4>
                        <span class="badge badge-purple">Bahan Baku</span>
                    </div>
                    <?php } ?>
                </div>
            </div>

        </main>
    </div>

    <div class="modal-overlay" id="modalDetail">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Detail Informasi</h3>
                <button class="close-btn" onclick="tutupModal()">&times;</button>
            </div>
            <div class="modal-body">
                <img id="mImg" src="" alt="Foto Detail">
                <h2 id="mTitle">Nama Parfum</h2>
                <div>
                    <span id="mKategori" class="badge badge-gray">Kategori</span>
                    <span id="mTipe" class="badge badge-blue">Tipe</span>
                </div>
                <div class="modal-desc" id="mDesc">
                    Deskripsi akan muncul di sini...
                </div>
            </div>
        </div>
    </div>

    <script>
        // Logika Untuk Pindah Tab Menu
        function openTab(evt, tabId) {
            // Sembunyikan semua tab content
            var contents = document.getElementsByClassName("tab-content");
            for (var i = 0; i < contents.length; i++) {
                contents[i].classList.remove("active");
            }
            
            // Hapus class active dari semua tombol
            var btns = document.getElementsByClassName("tab-btn");
            for (var i = 0; i < btns.length; i++) {
                btns[i].classList.remove("active");
            }
            
            // Tampilkan tab yang diklik dan aktifkan tombolnya
            document.getElementById(tabId).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        // Logika Untuk Buka Modal Pop-up (Menyuntikkan Data)
        function bukaModal(imgSrc, nama, kategori, tipe, deskripsi) {
            document.getElementById("mImg").src = imgSrc;
            document.getElementById("mTitle").innerText = nama;
            document.getElementById("mKategori").innerText = "Gender: " + kategori.toUpperCase();
            document.getElementById("mTipe").innerText = tipe;
            
            // Jika deskripsi kosong di database
            if(deskripsi.trim() === '') {
                document.getElementById("mDesc").innerText = "Tidak ada deskripsi karakter/catatan tambahan untuk item ini.";
            } else {
                document.getElementById("mDesc").innerText = deskripsi;
            }
            
            // Tampilkan Overlay Modal
            document.getElementById("modalDetail").classList.add("active");
        }

        // Logika Untuk Tutup Modal Pop-up
        function tutupModal() {
            document.getElementById("modalDetail").classList.remove("active");
        }

        // Fitur klik area luar modal untuk menutup
        window.onclick = function(event) {
            var modal = document.getElementById("modalDetail");
            if (event.target == modal) {
                tutupModal();
            }
        }
    </script>

</body>
</html> 
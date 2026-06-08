<?php
session_start();
require_once '../config/koneksi.php';

$queryNotes = mysqli_query($conn, "SELECT * FROM notes_aroma_custom ORDER BY nama ASC");
$notes_data = [];
while($row = mysqli_fetch_assoc($queryNotes)) $notes_data[] = $row;

$queryUkuran = mysqli_query($conn, "SELECT * FROM mst_ukuran ORDER BY ml ASC");
$ukuran_data = [];
while($row = mysqli_fetch_assoc($queryUkuran)) $ukuran_data[] = $row;

$queryKetahanan = mysqli_query($conn, "SELECT * FROM mst_ketahanan ORDER BY tambahan_harga ASC");
$ketahanan_data = [];
while($row = mysqli_fetch_assoc($queryKetahanan)) $ketahanan_data[] = $row;

if(isset($_POST['add_custom_cart'])) {
    if(!isset($_SESSION['pelanggan_id'])) {
        echo "<script>alert('Silakan login terlebih dahulu!'); window.location.href='login_pelanggan.php';</script>";
        exit;
    }

    $id_pelanggan = $_SESSION['pelanggan_id'];
    $nama_custom = mysqli_real_escape_string($conn, $_POST['nama_custom']);
    $id_ukuran = (int)$_POST['id_ukuran'];
    $id_ketahanan = (int)$_POST['id_ketahanan'];
    
    $selected_notes = isset($_POST['notes']) ? $_POST['notes'] : [];

    if(count($selected_notes) < 1 || count($selected_notes) > 3 || !$id_ukuran || !$id_ketahanan || empty($nama_custom)) {
        echo "<script>alert('Harap lengkapi semua pilihan (Nama, 1-3 Notes, Ukuran, Ketahanan).');</script>";
    } else {
        $base_price = 100000;
        
        $qUk = mysqli_query($conn, "SELECT tambahan_harga FROM mst_ukuran WHERE id = $id_ukuran");
        $tambahan_ukuran = ($qUk && mysqli_num_rows($qUk) > 0) ? mysqli_fetch_assoc($qUk)['tambahan_harga'] : 0;
        
        $qKet = mysqli_query($conn, "SELECT tambahan_harga FROM mst_ketahanan WHERE id = $id_ketahanan");
        $tambahan_ketahanan = ($qKet && mysqli_num_rows($qKet) > 0) ? mysqli_fetch_assoc($qKet)['tambahan_harga'] : 0;

        $subtotal = $base_price + $tambahan_ukuran + $tambahan_ketahanan;

        $id_top = isset($selected_notes[0]) ? (int)$selected_notes[0] : 'NULL';
        $id_mid = isset($selected_notes[1]) ? (int)$selected_notes[1] : 'NULL';
        $id_base = isset($selected_notes[2]) ? (int)$selected_notes[2] : 'NULL';

        $queryInsert = "INSERT INTO keranjang (id_pelanggan, tipe, nama_custom, id_ukuran, id_ketahanan, id_notes_top, id_notes_middle, id_notes_base, jumlah, subtotal) 
                        VALUES ($id_pelanggan, 'custom', '$nama_custom', $id_ukuran, $id_ketahanan, $id_top, $id_mid, $id_base, 1, $subtotal)";
        
        if(mysqli_query($conn, $queryInsert)) {
            echo "<script>alert('Racikan berhasil ditambahkan ke keranjang!'); window.location.href='keranjang.php';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan ke keranjang.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Custom Lab</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --navy: #2F4156;
            --teal: #567C8D;
            --sky-blue: #C8D9E6;
            --beige: #F5EFEB;
            --white: #FFFFFF;
            --light-bg: #F8FAF9;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.5);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--light-bg); color: var(--navy); overflow-x: hidden; }
        
        .blob { position: absolute; border-radius: 50%; filter: blur(60px); z-index: -1; opacity: 0.6; }
        .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: var(--sky-blue); }
        .blob-2 { bottom: 20%; right: -5%; width: 400px; height: 400px; background: var(--teal); }

        .container { max-width: 1200px; margin: 50px auto 100px; padding: 0 20px; display: flex; gap: 40px; align-items: flex-start; }
        
        .form-section { flex: 2; background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); padding: 40px; border-radius: 32px; box-shadow: 0 20px 50px rgba(47, 65, 86, 0.05); }
        .form-title { font-size: 32px; font-weight: 800; color: var(--navy); margin-bottom: 10px; letter-spacing: -0.5px; }
        .form-subtitle { font-size: 15px; color: var(--teal); font-weight: 500; margin-bottom: 40px; line-height: 1.6; }

        .form-group { margin-bottom: 40px; }
        .section-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 15px; }
        .section-label { font-size: 16px; font-weight: 700; color: var(--navy); }
        .section-note { font-size: 13px; color: var(--teal); font-weight: 600; background: var(--sky-blue); padding: 4px 12px; border-radius: 100px; }

        .input-text { width: 100%; padding: 18px 20px; border: 1.5px solid var(--sky-blue); border-radius: 16px; font-family: inherit; font-size: 15px; color: var(--navy); background: var(--white); transition: 0.3s; }
        .input-text:focus { outline: none; border-color: var(--navy); box-shadow: 0 0 0 4px rgba(200, 217, 230, 0.4); }

        .filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 5px; }
        .filter-btn { padding: 8px 20px; background: var(--white); border: 1px solid var(--sky-blue); color: var(--teal); border-radius: 100px; font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.3s; white-space: nowrap; }
        .filter-btn.active { background: var(--navy); color: var(--white); border-color: var(--navy); }

        .grid-options { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; min-height: 380px; align-content: start; }
        .pagination-container { display: flex; justify-content: center; gap: 8px; margin-top: 10px; }
        .page-btn { width: 36px; height: 36px; border-radius: 50%; border: 1px solid var(--sky-blue); background: var(--white); color: var(--teal); font-weight: 700; cursor: pointer; transition: 0.3s; display: flex; justify-content: center; align-items: center; }
        .page-btn.active { background: var(--teal); color: var(--white); border-color: var(--teal); }
        .page-btn:hover:not(.active) { background: var(--sky-blue); }
        
        .opt-card { position: relative; cursor: pointer; }
        .opt-card input { position: absolute; opacity: 0; cursor: pointer; }
        .opt-box { padding: 16px 20px; border: 1.5px solid var(--sky-blue); border-radius: 16px; background: var(--white); transition: all 0.3s ease; height: 100%; display: flex; flex-direction: column; }
        
        .opt-card input:checked ~ .opt-box { border-color: var(--navy); background: rgba(200, 217, 230, 0.2); box-shadow: 0 0 0 1px var(--navy); }
        .opt-card input:disabled ~ .opt-box { opacity: 0.5; cursor: not-allowed; background: #f0f0f0; filter: grayscale(100%); }
        
        .opt-badge { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 3px 8px; border-radius: 6px; display: inline-block; margin-bottom: 8px; width: fit-content; }
        .badge-pria { background: #e0f2fe; color: #0284c7; }
        .badge-wanita { background: #fce7f3; color: #db2777; }
        .badge-unisex { background: #f3f4f6; color: #4b5563; }

        .opt-title { font-size: 15px; font-weight: 800; color: var(--navy); margin-bottom: 6px; }
        .opt-sub { font-size: 12px; color: var(--teal); font-weight: 500; line-height: 1.5; flex: 1; }
        .opt-price { font-size: 12px; font-weight: 800; color: var(--teal); margin-top: 10px; }

        .summary-section { flex: 1; position: sticky; top: 40px; background: var(--navy); color: var(--white); padding: 35px; border-radius: 32px; box-shadow: 0 20px 40px rgba(47, 65, 86, 0.15); }
        .sum-title { font-size: 20px; font-weight: 800; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
        
        .sum-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px; color: var(--sky-blue); }
        .sum-row span:last-child { font-weight: 700; color: var(--white); }
        
        .sum-total { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 18px; font-weight: 800; }
        .sum-total span:last-child { font-size: 24px; color: var(--beige); }

        .btn-submit { width: 100%; padding: 18px; background: var(--teal); color: var(--white); border: none; border-radius: 16px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 30px; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .btn-submit:hover:not(:disabled) { background: var(--beige); color: var(--navy); transform: translateY(-3px); }
        .btn-submit:disabled { background: #4a5c6d; color: #8fa0b0; cursor: not-allowed; }

        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: var(--teal); font-weight: 700; font-size: 15px; margin: 30px 20px; text-decoration: none; transition: 0.3s; }
        .btn-back:hover { color: var(--navy); transform: translateX(-5px); }

        @media (max-width: 900px) {
            .container { flex-direction: column; }
            .summary-section { width: 100%; position: static; }
        }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <a href="shop.php" class="btn-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Kembali ke Shop
    </a>

    <div class="container">
        <div class="form-section">
            <h1 class="form-title">Custom Lab</h1>
            <p class="form-subtitle">Jadilah peracik. Jelajahi koleksi notes kami dan gabungkan hingga 3 aroma untuk menciptakan *signature scent* milikmu sendiri.</p>

            <form action="" method="POST" id="customForm">
                <div id="hiddenNotesContainer"></div>
                
                <div class="form-group">
                    <div class="section-label" style="margin-bottom: 15px;">Beri Nama Mahakarya Anda</div>
                    <input type="text" name="nama_custom" id="namaCustom" class="input-text" placeholder="Contoh: Midnight Elegance" required>
                </div>

                <div class="form-group">
                    <div class="section-header">
                        <div class="section-label">Pilih Notes Aroma</div>
                        <div class="section-note" id="notesCounter">0 / 3 Dipilih</div>
                    </div>
                    
                    <div class="filter-tabs">
                        <button type="button" class="filter-btn active" onclick="setCategory('semua')">Semua Aroma</button>
                        <button type="button" class="filter-btn" onclick="setCategory('pria')">Pria</button>
                        <button type="button" class="filter-btn" onclick="setCategory('wanita')">Wanita</button>
                        <button type="button" class="filter-btn" onclick="setCategory('unisex')">Unisex</button>
                    </div>

                    <div class="grid-options" id="notesGrid"></div>

                    <div class="pagination-container" id="paginationContainer"></div>
                </div>

                <div class="form-group">
                    <div class="section-label" style="margin-bottom: 15px;">Pilih Ukuran Botol</div>
                    <div class="grid-options" style="min-height: auto; margin-bottom: 0;">
                        <?php foreach($ukuran_data as $uk): ?>
                        <label class="opt-card">
                            <input type="radio" name="id_ukuran" value="<?= $uk['id']; ?>" data-harga="<?= $uk['tambahan_harga']; ?>" onchange="calculateTotal()">
                            <div class="opt-box" style="align-items: center; text-align: center;">
                                <div class="opt-title"><?= htmlspecialchars($uk['nama']); ?></div>
                                <div class="opt-sub"><?= htmlspecialchars($uk['ml']); ?>ml</div>
                                <div class="opt-price">+ Rp <?= number_format($uk['tambahan_harga'], 0, ',', '.'); ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <div class="section-label" style="margin-bottom: 15px;">Pilih Tingkat Ketahanan</div>
                    <div class="grid-options" style="grid-template-columns: 1fr; min-height: auto; margin-bottom: 0;">
                        <?php foreach($ketahanan_data as $ket): ?>
                        <label class="opt-card">
                            <input type="radio" name="id_ketahanan" value="<?= $ket['id']; ?>" data-harga="<?= $ket['tambahan_harga']; ?>" onchange="calculateTotal()">
                            <div class="opt-box" style="flex-direction: row; justify-content: space-between; align-items: center;">
                                <div>
                                    <div class="opt-title"><?= htmlspecialchars($ket['nama']); ?></div>
                                    <div class="opt-sub"><?= htmlspecialchars($ket['durasi']); ?></div>
                                </div>
                                <div class="opt-price">+ Rp <?= number_format($ket['tambahan_harga'], 0, ',', '.'); ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" name="add_custom_cart" id="hiddenSubmitBtn" style="display: none;"></button>
            </form>
        </div>

        <div class="summary-section">
            <div class="sum-title">Rincian Custom</div>
            
            <div class="sum-row">
                <span>Base Price (1-3 Notes)</span>
                <span>Rp 100.000</span>
            </div>
            <div class="sum-row">
                <span>Biaya Ukuran</span>
                <span id="displayHargaUkuran">Rp 0</span>
            </div>
            <div class="sum-row">
                <span>Biaya Ketahanan</span>
                <span id="displayHargaKetahanan">Rp 0</span>
            </div>

            <div class="sum-total">
                <span>Total Harga</span>
                <span id="displayTotal">Rp 100.000</span>
            </div>

            <button type="button" class="btn-submit" id="mainSubmitBtn" onclick="document.getElementById('hiddenSubmitBtn').click();" disabled>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                Tambahkan Racikan
            </button>
        </div>
    </div>

    <script>
        const allNotes = <?= json_encode($notes_data); ?>;
        
        let selectedNotes = []; 
        let currentCategory = 'semua';
        let currentPage = 1;
        const limitPerPage = 9;

        const notesGrid = document.getElementById('notesGrid');
        const paginationContainer = document.getElementById('paginationContainer');
        const counterText = document.getElementById('notesCounter');
        const hiddenNotesContainer = document.getElementById('hiddenNotesContainer');
        const mainSubmitBtn = document.getElementById('mainSubmitBtn');

        function renderNotes() {
            let filtered = allNotes;
            if(currentCategory !== 'semua') {
                filtered = allNotes.filter(n => n.kategori && n.kategori.toLowerCase() === currentCategory);
            }

            const totalPages = Math.ceil(filtered.length / limitPerPage) || 1;
            if(currentPage > totalPages) currentPage = totalPages;

            const startIndex = (currentPage - 1) * limitPerPage;
            const notesToShow = filtered.slice(startIndex, startIndex + limitPerPage);

            notesGrid.innerHTML = '';
            
            notesToShow.forEach(note => {
                const isChecked = selectedNotes.includes(note.id);
                const isMaxedOut = selectedNotes.length >= 3 && !isChecked;
                
                let badgeClass = 'badge-unisex';
                if(note.kategori.toLowerCase() === 'pria') badgeClass = 'badge-pria';
                if(note.kategori.toLowerCase() === 'wanita') badgeClass = 'badge-wanita';

                notesGrid.innerHTML += `
                    <label class="opt-card">
                        <input type="checkbox" value="${note.id}" 
                               ${isChecked ? 'checked' : ''} 
                               ${isMaxedOut ? 'disabled' : ''} 
                               onchange="toggleNote(this, '${note.id}')">
                        <div class="opt-box">
                            <span class="opt-badge ${badgeClass}">${note.kategori}</span>
                            <div class="opt-title">${note.nama}</div>
                            <div class="opt-sub">${note.deskripsi || ''}</div>
                        </div>
                    </label>
                `;
            });

            renderPagination(totalPages);
            updateHiddenInputs();
            checkCheckoutStatus();
        }

        function toggleNote(checkbox, noteId) {
            if(checkbox.checked) {
                if(selectedNotes.length < 3) selectedNotes.push(noteId);
            } else {
                selectedNotes = selectedNotes.filter(id => id !== noteId);
            }
            
            renderNotes(); 
        }

        function setCategory(cat) {
            currentCategory = cat;
            currentPage = 1; 
            
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            renderNotes();
        }

        function renderPagination(totalPages) {
            paginationContainer.innerHTML = '';
            if(totalPages <= 1) return; 

            for(let i = 1; i <= totalPages; i++) {
                paginationContainer.innerHTML += `
                    <button type="button" class="page-btn ${i === currentPage ? 'active' : ''}" 
                            onclick="currentPage = ${i}; renderNotes();">${i}</button>
                `;
            }
        }

        function updateHiddenInputs() {
            hiddenNotesContainer.innerHTML = '';
            selectedNotes.forEach(id => {
                hiddenNotesContainer.innerHTML += `<input type="hidden" name="notes[]" value="${id}">`;
            });
            counterText.innerText = `${selectedNotes.length} / 3 Dipilih`;
        }

        const formatRp = (angka) => new Intl.NumberFormat('id-ID').format(angka);

        function calculateTotal() {
            checkCheckoutStatus();
        }

        function checkCheckoutStatus() {
            let total = 100000; // Base Price
            let hargaUk = 0;
            let hargaKet = 0;

            let selectedUkuran = document.querySelector('input[name="id_ukuran"]:checked');
            if(selectedUkuran) {
                hargaUk = parseInt(selectedUkuran.dataset.harga);
                total += hargaUk;
            }

            let selectedKetahanan = document.querySelector('input[name="id_ketahanan"]:checked');
            if(selectedKetahanan) {
                hargaKet = parseInt(selectedKetahanan.dataset.harga);
                total += hargaKet;
            }

            document.getElementById('displayHargaUkuran').innerText = 'Rp ' + formatRp(hargaUk);
            document.getElementById('displayHargaKetahanan').innerText = 'Rp ' + formatRp(hargaKet);
            document.getElementById('displayTotal').innerText = 'Rp ' + formatRp(total);

            const namaInput = document.getElementById('namaCustom').value.trim();
            if(selectedNotes.length > 0 && selectedUkuran && selectedKetahanan && namaInput !== '') {
                mainSubmitBtn.disabled = false;
                mainSubmitBtn.innerText = "Tambahkan Racikan";
            } else {
                mainSubmitBtn.disabled = true;
                mainSubmitBtn.innerText = "Lengkapi Pilihan";
            }
        }

        document.getElementById('namaCustom').addEventListener('input', checkCheckoutStatus);

        window.onload = renderNotes;
    </script>
</body>
</html>
<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --navy: #2F4156;
            --teal: #567C8D;
            --sky-blue: #C8D9E6;
            --beige: #F5EFEB;
            --white: #FFFFFF;
            --glass-bg: rgba(255, 255, 255, 0.4);
            --glass-border: rgba(255, 255, 255, 0.5);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--beige); color: var(--navy); overflow-x: hidden; min-height: 100vh; display: flex; flex-direction: column; }
        a { text-decoration: none; color: inherit; }

        .blob { position: absolute; border-radius: 50%; filter: blur(60px); z-index: -1; opacity: 0.6; }
        .blob-1 { top: 10%; left: -10%; width: 500px; height: 500px; background: var(--sky-blue); }
        .blob-2 { top: 40%; right: -5%; width: 400px; height: 400px; background: var(--teal); }
        .blob-3 { bottom: -10%; left: 20%; width: 600px; height: 600px; background: var(--sky-blue); }

        nav { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 1200px; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; z-index: 1000; background: var(--glass-bg); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); border-radius: 100px; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); }
        .logo { font-size: 20px; font-weight: 800; color: var(--navy); letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { font-size: 14px; font-weight: 600; color: var(--navy); transition: 0.3s; }
        .nav-links a:hover { color: var(--teal); }
        .btn-nav-login { padding: 10px 24px; background: var(--navy); color: var(--white); border-radius: 100px; font-weight: 700; font-size: 14px; transition: 0.3s; border: none; cursor: pointer; }
        .btn-nav-login:hover { background: var(--teal); transform: translateY(-2px); }

        .nav-actions { display: flex; gap: 10px; align-items: center; }
        .btn-profile { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-profile svg { width: 24px; height: 24px; color: currentColor; }

        .btn-cart { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-cart svg { width: 24px; height: 24px; color: currentColor; }

        .btn-logout { width: 44px; height: 44px; padding: 0; border: none; background: transparent; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-logout svg { width: 24px; height: 24px; color: currentColor; }

        .wizard-container { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 120px 20px 60px; position: relative; z-index: 2; }
        
        .step-section { text-align: center; width: 100%; max-width: 900px; transition: all 0.5s ease; }
        .hidden { display: none !important; opacity: 0; }
        .fade-in { animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .badge-cat { display: inline-block; background: var(--navy); color: var(--white); font-size: 12px; font-weight: 800; padding: 8px 20px; border-radius: 100px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(47, 65, 86, 0.15); }
        
        .step-section h1 { font-size: 48px; font-weight: 800; color: var(--navy); margin-bottom: 15px; letter-spacing: -1.5px; line-height: 1.1; }
        .step-section p { font-size: 18px; color: var(--teal); font-weight: 500; margin-bottom: 60px; }

        .card-grid { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; }
        
        .option-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); border: 1px solid var(--sky-blue); border-radius: 32px; padding: 50px 30px; width: 280px; cursor: pointer; transition: all 0.4s cubic-bezier(0.5, 0, 0, 1); display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 10px 30px rgba(47, 65, 86, 0.05); }
        .option-card:hover { background: var(--white); border-color: var(--teal); transform: translateY(-12px); box-shadow: 0 20px 40px rgba(47, 65, 86, 0.1); }

        .icon-box { width: 80px; height: 80px; background: var(--beige); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 30px; color: var(--navy); transition: 0.4s; border: 1px solid var(--sky-blue); }
        .option-card:hover .icon-box { background: var(--sky-blue); transform: scale(1.1) rotate(5deg); border-color: var(--teal); }

        .option-card h3 { font-size: 24px; font-weight: 800; color: var(--navy); margin-bottom: 12px; }
        .option-card p { font-size: 14px; color: var(--teal); font-weight: 500; line-height: 1.6; }

        .btn-back { display: inline-flex; align-items: center; gap: 10px; color: var(--teal); font-weight: 700; font-size: 15px; margin-top: 60px; cursor: pointer; transition: 0.3s; border: none; background: var(--white); padding: 12px 24px; border-radius: 100px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--sky-blue); }
        .btn-back:hover { color: var(--white); background: var(--navy); border-color: var(--navy); transform: translateX(-5px); }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <nav>
        <div class="logo">PERSCENTS.</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="track_order.php">Track Order</a>
        </div>
        <?php if(isset($_SESSION['pelanggan_id'])) : ?>
            <div class="nav-actions">
                <button class="btn-cart" onclick="window.location.href='keranjang.php'" aria-label="Keranjang">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </button>

                <button class="btn-profile" onclick="window.location.href='profil.php'" aria-label="Profilku">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </button>

                <button class="btn-logout" onclick="window.location.href='logout.php'" aria-label="Logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </button>
            </div>
        <?php else : ?>
            <button class="btn-nav-login" onclick="window.location.href='login_pelanggan.php'">Masuk</button>
        <?php endif; ?>
    </nav>

    <div class="wizard-container">

        <div id="step1" class="step-section fade-in">
            <h1>Choose Your Category</h1>
            <p>Untuk siapa mahakarya wewangian ini diracik?</p>

            <div class="card-grid">
                <div class="option-card" onclick="chooseCategory('Men')">
                    <div class="icon-box">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <h3>Men</h3>
                    <p>Aroma maskulin yang berani dan elegan.</p>
                </div>

                <div class="option-card" onclick="chooseCategory('Women')">
                    <div class="icon-box">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h3>Women</h3>
                    <p>Sentuhan feminin yang memikat dan inspiratif.</p>
                </div>

                <div class="option-card" onclick="chooseCategory('Kids')">
                    <div class="icon-box">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                    </div>
                    <h3>Kids</h3>
                    <p>Wangi lembut dan menyenangkan untuk si kecil.</p>
                </div>
            </div>
        </div>

        <div id="step2" class="step-section hidden">
            <div class="badge-cat" id="badgeCategory">Category: Men</div>
            <h1>Choose Product Type</h1>
            <p>Bagaimana Anda ingin menikmati wewangian ini?</p>

            <div class="card-grid">
                <div class="option-card" onclick="goToCollection()">
                    <div class="icon-box">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </div>
                    <h3>Collections</h3>
                    <p>Mahakarya parfum siap pakai yang diracik berdasarkan kepribadian.</p>
                </div>

                <div class="option-card" onclick="window.location.href='notes.php'">
                    <div class="icon-box">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>
                    </div>
                    <h3>Custom Lab</h3>
                    <p>Jadilah sang peracik. Buat kombinasi wangi unik milik Anda sendiri.</p>
                </div>
            </div>

            <button class="btn-back" onclick="goBackToStep1()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Kembali ke Kategori
            </button>
        </div>

    </div>

<script>
        let kategoriPilihan = '';

        function chooseCategory(categoryName) {
            if (categoryName === 'Kids') {
                window.location.href = 'katalog_kategori.php?kategori=anak';
            } else {
                kategoriPilihan = (categoryName === 'Men') ? 'pria' : 'wanita';
                
                document.getElementById('badgeCategory').innerText = 'Category: ' + categoryName;
                
                document.getElementById('step1').classList.add('hidden');
                const step2 = document.getElementById('step2');
                step2.classList.remove('hidden');
                void step2.offsetWidth; 
                step2.classList.add('fade-in');
            }
        }

        function goToCollection() {
            window.location.href = 'katalog_kategori.php?kategori=' + kategoriPilihan;
        }

        function goBackToStep1() {
            document.getElementById('step2').classList.add('hidden');
            const step1 = document.getElementById('step1');
            step1.classList.remove('hidden');
            void step1.offsetWidth; 
            step1.classList.add('fade-in');
        }
    </script>
</body>
</html>

<?php
session_start();

if (isset($_GET['konfirmasi']) && $_GET['konfirmasi'] == 'ya') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['pelanggan_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Konfirmasi Logout</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --navy: #2F4156;
            --teal: #567C8D;
            --sky-blue: #C8D9E6;
            --beige: #F5EFEB;
            --white: #FFFFFF;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.5);
            --danger: #e63946; 
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--beige); color: var(--navy); display: flex; align-items: center; justify-content: center; min-height: 100vh; overflow: hidden; position: relative; }

        .blob { position: absolute; border-radius: 50%; filter: blur(60px); z-index: -1; opacity: 0.6; }
        .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: var(--sky-blue); }
        .blob-2 { bottom: -10%; right: -5%; width: 400px; height: 400px; background: var(--teal); }

        .modal-card { width: 450px; max-width: 90%; background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 32px; padding: 50px 40px; text-align: center; box-shadow: 0 20px 50px rgba(47, 65, 86, 0.1); transform: translateY(20px); animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        
        @keyframes slideUp {
            to { transform: translateY(0); }
        }

        .icon-box { width: 80px; height: 80px; background: var(--white); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; color: var(--navy); border: 2px solid var(--sky-blue); box-shadow: 0 10px 20px rgba(47, 65, 86, 0.05); }

        .modal-card h2 { font-size: 28px; font-weight: 800; color: var(--navy); margin-bottom: 10px; letter-spacing: -0.5px; }
        .modal-card p { font-size: 15px; color: var(--teal); font-weight: 500; margin-bottom: 35px; line-height: 1.6; }

        .btn-group { display: flex; flex-direction: column; gap: 12px; }
        
        .btn { width: 100%; padding: 16px; border-radius: 100px; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; }
        
        .btn-cancel { background: var(--navy); color: var(--white); border: 2px solid var(--navy); }
        .btn-cancel:hover { background: var(--teal); border-color: var(--teal); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(47, 65, 86, 0.15); }
        
        .btn-logout { background: transparent; color: var(--danger); border: 2px solid var(--danger); }
        .btn-logout:hover { background: var(--danger); color: var(--white); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(230, 57, 70, 0.2); }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="modal-card">
        <div class="icon-box">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </div>
        
        <h2>Yakin Mau Keluar?</h2>
        <p>Sesi belanja dan racikan Custom Blend Anda akan diakhiri. Pastikan Anda sudah menyimpan produk favorit di keranjang!</p>
        
        <div class="btn-group">
            <a href="javascript:history.back()" class="btn btn-cancel">Batal, Tetap di Sini</a>
            
            <a href="logout.php?konfirmasi=ya" class="btn btn-logout">Ya, Keluar Akun</a>
        </div>
    </div>

</body>
</html>
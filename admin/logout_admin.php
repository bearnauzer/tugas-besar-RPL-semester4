<?php
session_start();

if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    session_unset();
    
    session_destroy();
    
    echo "<script>
            alert('Anda berhasil logout! Sampai jumpa lagi.');
            window.location.href = 'login_admin.php';
        </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Logout - PERSCENTS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { 
            background-color: #f4f6f9; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
        }
        
        .card { 
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            text-align: center; 
            max-width: 400px; 
            width: 100%; 
            border: 1px solid #e2e8f0; 
        }
        
        .icon-warning { 
            width: 60px; 
            height: 60px; 
            background-color: #fee2e2; 
            color: #ef4444; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 30px; 
            margin: 0 auto 20px; 
            font-weight: bold; 
        }
        
        h2 { color: #1e293b; font-size: 20px; margin-bottom: 10px; }
        p { color: #64748b; font-size: 14px; margin-bottom: 30px; line-height: 1.5; }
        
        .btn-group { display: flex; gap: 15px; justify-content: center; }
        
        .btn { 
            padding: 12px 24px; 
            border-radius: 6px; 
            font-weight: bold; 
            font-size: 14px; 
            text-decoration: none; 
            cursor: pointer; 
            transition: 0.2s; 
            width: 100%; 
            display: inline-block;
        }
        
        .btn-batal { 
            background-color: #f1f5f9; 
            color: #475569; 
            border: 1px solid #cbd5e1; 
        }
        
        .btn-batal:hover { background-color: #e2e8f0; }
        
        .btn-yakin { 
            background-color: #ef4444; 
            color: white; 
            border: 1px solid #ef4444;
        }
        
        .btn-yakin:hover { background-color: #dc2626; }
    </style>
</head>
<body>

    <div class="card">
        <div class="icon-warning">!</div>
        <h2>Konfirmasi Logout</h2>
        <p>Apakah kamu yakin ingin keluar dari halaman Admin PERSCENTS? Sesi kamu akan diakhiri.</p>
        
        <div class="btn-group">
            <!-- Fungsi history.back() akan mengembalikan admin ke halaman sebelumnya (Dashboard/Produk dll) -->
            <a href="javascript:history.back()" class="btn btn-batal">Batal</a>
            
            <!-- Jika Yakin, arahkan ke file ini lagi tapi bawa "kunci" confirm=yes untuk dieksekusi PHP di atas -->
            <a href="logout_admin.php?confirm=yes" class="btn btn-yakin">Yakin, Logout</a>
        </div>
    </div>

</body>
</html>
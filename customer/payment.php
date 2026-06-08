<?php
session_start();
require_once '../config/koneksi.php';

if(!isset($_SESSION['pelanggan_id'])) {
    header("Location: login_pelanggan.php");
    exit;
}

$id_pelanggan = $_SESSION['pelanggan_id'];
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if(!$user_id) {
    $pelangganData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email FROM pelanggan WHERE id = $id_pelanggan"));
    if($pelangganData) {
        $email_pelanggan = mysqli_real_escape_string($conn, $pelangganData['email']);
        $queryUser = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_pelanggan' LIMIT 1");
        if(mysqli_num_rows($queryUser) > 0) {
            $userData = mysqli_fetch_assoc($queryUser);
            $user_id = $userData['id'];
            $_SESSION['user_id'] = $user_id;
        }
    }
}

$pesanan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = mysqli_query($conn, "SELECT * FROM pesanan WHERE id = $pesanan_id AND user_id = $user_id");
if(mysqli_num_rows($query) == 0) {
    die("Pesanan tidak ditemukan atau akses ditolak.");
}
$pesanan = mysqli_fetch_assoc($query);

if(isset($_POST['upload_bukti']) && isset($_FILES['file_bukti'])) {
    $file = $_FILES['file_bukti'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'bukti_' . $pesanan_id . '_' . time() . '.' . $ext;
    
    $target_dir = '../assets/pembayaran/';
    if (!file_exists($target_dir)) {
        @mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . $filename;
    
    if(move_uploaded_file($file['tmp_name'], $target_file)) {
        $db_path = 'assets/pembayaran/' . $filename;
        
        $qUpdate = "UPDATE pesanan SET bukti_pembayaran = '$db_path', tenggat_selesai = DATE_ADD(NOW(), INTERVAL 2 DAY) WHERE id = $pesanan_id";
        
        if(mysqli_query($conn, $qUpdate)){
            header("Location: payment.php?id=$pesanan_id");
            exit;
        }
    } else {
        echo "<script>alert('Gagal mengupload bukti pembayaran.');</script>";
    }
}
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    header('Content-Type: application/json');

    if($action === 'init_qr') {
        $token = bin2hex(random_bytes(8));
        if(!isset($_SESSION['qris'])) $_SESSION['qris'] = [];
        $_SESSION['qris'][$pesanan_id] = ['token' => $token, 'expires' => time() + 60];
        echo json_encode(['ok' => true, 'token' => $token, 'expires' => $_SESSION['qris'][$pesanan_id]['expires']]);
        exit;
    }

    if($action === 'mark_failed') {
        $q = "UPDATE pesanan SET status = 'failed' WHERE id = $pesanan_id";
        mysqli_query($conn, $q);
        if(isset($_SESSION['qris'][$pesanan_id])) unset($_SESSION['qris'][$pesanan_id]);
        echo json_encode(['ok' => true]);
        exit;
    }

}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Pembayaran</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --navy: #2F4156; --teal: #567C8D; --sky-blue: #C8D9E6; --beige: #F5EFEB; --white: #FFFFFF; --light-bg: #F8FAF9; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--light-bg); color: var(--navy); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 40px 20px; }
        
        .blob { position: absolute; border-radius: 50%; filter: blur(60px); z-index: -1; opacity: 0.6; }
        .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: var(--sky-blue); }
        .blob-2 { bottom: 20%; right: -5%; width: 400px; height: 400px; background: var(--teal); }

        .payment-card { background: var(--white); width: 100%; max-width: 550px; border-radius: 32px; padding: 50px; box-shadow: 0 20px 40px rgba(47, 65, 86, 0.08); text-align: center; border: 1px solid var(--sky-blue); position: relative; z-index: 2; }
        .kode-order { font-size: 14px; font-weight: 800; color: var(--teal); letter-spacing: 1px; margin-bottom: 5px; }
        .amount { font-size: 42px; font-weight: 800; color: var(--navy); margin-bottom: 30px; }
        
        .qr-box { background: var(--light-bg); border: 2px dashed var(--sky-blue); border-radius: 20px; padding: 30px; margin-bottom: 30px; }
        .qr-box img { width: 200px; height: 200px; object-fit: contain; margin-bottom: 15px; }
        .va-number { font-size: 28px; font-weight: 800; letter-spacing: 2px; color: var(--navy); }
        
        .form-upload { background: var(--beige); border-radius: 16px; padding: 20px; margin-top: 20px; }
        .btn-upload { width: 100%; padding: 16px; background: var(--navy); color: var(--white); border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 15px; }
        .btn-upload:hover { background: var(--teal); }

        .struk-header { background: #e0f2fe; color: #0284c7; padding: 10px 20px; border-radius: 100px; font-size: 13px; font-weight: 800; display: inline-block; margin-bottom: 20px; }
        .struk-estimasi { background: var(--navy); color: var(--white); padding: 25px; border-radius: 16px; margin: 30px 0; }
        .struk-estimasi span { display: block; font-size: 14px; color: var(--sky-blue); margin-bottom: 8px; }
        .struk-estimasi strong { font-size: 22px; font-weight: 800; color: var(--beige); }
        .btn-track { display: inline-block; padding: 18px 30px; background: var(--teal); color: var(--white); text-decoration: none; border-radius: 100px; font-weight: 700; transition: 0.3s; width: 100%; }
        .btn-track:hover { background: var(--navy); transform: translateY(-3px); }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="payment-card">
        <div class="kode-order">KODE PESANAN: <?= htmlspecialchars($pesanan['kode_pesanan']); ?></div>
        
        <?php if(empty($pesanan['bukti_pembayaran'])): ?>
            <h2 style="font-size: 18px; font-weight: 600; color: var(--teal); margin-top: 20px;">Total Pembayaran</h2>
            <div class="amount">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.'); ?></div>

            <div class="qr-box">
                        <?php if($pesanan['metode_bayar'] == 'QRIS'): ?>
                        <p style="font-weight: 700; margin-bottom: 10px;">Scan QRIS ini</p>
                        <img id="qrisImg" src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" alt="QRIS Dummy">
                        <div style="margin-top:8px; display:flex; justify-content:center; gap:10px; align-items:center;">
                            <div id="countdown" style="font-weight:700; color:var(--navy);">01:00</div>
                        </div>
                        <p id="qrisNote" style="font-size: 13px; color: var(--teal); margin-top:10px;">Menerima Gopay, OVO, Dana, ShopeePay</p>
                    <?php else: ?>
                    <p style="font-weight: 700; margin-bottom: 10px;">Transfer ke Virtual Account</p>
                    <div class="va-number">8801 2345 6789</div>
                    <p style="font-size: 13px; color: var(--teal); margin-top: 10px;">Bank BCA, Mandiri, BNI, BRI</p>
                <?php endif; ?>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" class="form-upload">
                <p style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">Sudah Transfer?</p>
                <input type="file" name="file_bukti" accept="image/*" required style="width: 100%; padding: 10px; background: var(--white); border-radius: 8px;">
                <button type="submit" name="upload_bukti" class="btn-upload">Upload Bukti & Verifikasi</button>
            </form>

        <?php else: ?>
            <div class="struk-header">Bukti Diterima - Menunggu Admin</div>
            <div class="amount">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.'); ?></div>
            
            <p style="font-size: 15px; color: var(--teal); line-height: 1.6;">
                Terima kasih! Bukti transfer Anda sudah kami terima dan sedang dalam antrean pengecekan oleh Admin.
            </p>

            <div class="struk-estimasi">
                <span>Estimasi Parfum Selesai:</span>
                <strong>
                    <?php 
                        $tanggal = strtotime($pesanan['tenggat_selesai']);
                        $bulan = array (1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
                        echo date('d', $tanggal) . ' ' . $bulan[(int)date('m', $tanggal)] . ' ' . date('Y', $tanggal);
                    ?>
                </strong>
            </div>

            <a href="track_order.php" class="btn-track">Lacak Pesanan Saya</a>
        <?php endif; ?>
    </div>

</body>
</html>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const metode = <?= json_encode($pesanan['metode_bayar']); ?>;
    if(metode === 'QRIS') {
        fetch('payment.php?id=' + <?= $pesanan_id; ?>, { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'action=init_qr' })
            .then(r => r.json()).then(data => {
                if(data.ok) {
                    const token = data.token;
                    const expiresAt = data.expires; 
                    const payload = 'pesanan:' + <?= $pesanan_id; ?> + '|kode:' + encodeURIComponent(<?= json_encode($pesanan['kode_pesanan']); ?>) + '|token:' + token;
                    const qrUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' + encodeURIComponent(payload);
                    document.getElementById('qrisImg').src = qrUrl;

                    const countdownEl = document.getElementById('countdown');
                    let remaining = expiresAt - Math.floor(Date.now()/1000);
                    function updateCountdown(){
                        if(remaining <= 0) {
                            countdownEl.innerText = '00:00';
                            fetch('payment.php?id=' + <?= $pesanan_id; ?>, { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'action=mark_failed' });
                            document.getElementById('qrisNote').innerText = 'Waktu habis. Pembayaran gagal.';
                            return;
                        }
                        const mm = String(Math.floor(remaining/60)).padStart(2,'0');
                        const ss = String(remaining%60).padStart(2,'0');
                        countdownEl.innerText = mm + ':' + ss;
                        remaining--; 
                        setTimeout(updateCountdown, 1000);
                    }
                    updateCountdown();
                }
            });
    }
});
</script>
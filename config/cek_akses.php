<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function proteksi_halaman($role_yang_diizinkan) {
    if ($role_yang_diizinkan == 'admin') {
        $login_page = "../admin/login_admin.php";
    } elseif ($role_yang_diizinkan == 'customer'){
        $login_page = "../user/login_customer.php";
    } else {
        $login_page = "../perfumer/login_peracik.php";
    }
    if (!isset($_SESSION['role'])) {
        header("Location: " . $login_page . "?pesan=belum_login");
        exit;
    }
    if ($_SESSION['role'] !== $role_yang_diizinkan) {
        echo "<script>
                alert('Akses Ditolak! Anda bukan $role_yang_diizinkan');
                window.location.href='$login_page';
            </script>";
        exit;
    }
}
?>
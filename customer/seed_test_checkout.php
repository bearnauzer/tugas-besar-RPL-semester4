<?php
session_start();
require_once '../config/koneksi.php';

// This script will:
// - Create (or find) a test pelanggan and users mapping
// - Ensure there's at least one produk_varian (create sample product/size/ketahanan if needed)
// - Insert one item into keranjang for the test pelanggan
// - Set session and redirect to checkout.php for end-to-end testing

// 1) Create or get test pelanggan
$email = 'test_e2e@example.com';
$cek = mysqli_query($conn, "SELECT * FROM pelanggan WHERE email = '" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
if(mysqli_num_rows($cek) > 0) {
    $pel = mysqli_fetch_assoc($cek);
    $pelanggan_id = $pel['id'];
} else {
    $nama = 'Tester E2E';
    $nohp = '081234567890';
    $password = password_hash('password', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO pelanggan (nama_lengkap, email, no_hp, password) VALUES ('" . mysqli_real_escape_string($conn, $nama) . "', '" . mysqli_real_escape_string($conn, $email) . "', '" . mysqli_real_escape_string($conn, $nohp) . "', '" . mysqli_real_escape_string($conn, $password) . "')");
    $pelanggan_id = mysqli_insert_id($conn);
}

// 2) Ensure users mapping exists (users table) and set $_SESSION['user_id']
$qUser = mysqli_query($conn, "SELECT id FROM users WHERE email = '" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
if(mysqli_num_rows($qUser) > 0) {
    $u = mysqli_fetch_assoc($qUser);
    $user_id = $u['id'];
} else {
    mysqli_query($conn, "INSERT INTO users (nama, email, password, no_hp, role) VALUES ('" . mysqli_real_escape_string($conn, 'Tester E2E') . "', '" . mysqli_real_escape_string($conn, $email) . "', '', '" . mysqli_real_escape_string($conn, '081234567890') . "', 'customer')");
    $user_id = mysqli_insert_id($conn);
}

// 3) Find or create produk_varian
$qVar = mysqli_query($conn, "SELECT pv.id FROM produk_varian pv LIMIT 1");
if(mysqli_num_rows($qVar) > 0) {
    $v = mysqli_fetch_assoc($qVar);
    $varian_id = $v['id'];
} else {
    // create mst_ukuran
    mysqli_query($conn, "INSERT INTO mst_ukuran (nama, ml) VALUES ('Pocket', 30)");
    $id_uk = mysqli_insert_id($conn);
    // create mst_ketahanan
    mysqli_query($conn, "INSERT INTO mst_ketahanan (nama, durasi) VALUES ('Regular', '2-4 jam')");
    $id_ket = mysqli_insert_id($conn);
    // create produk_collection
    mysqli_query($conn, "INSERT INTO produk_collection (nama, gambar) VALUES ('TEST PRODUCT E2E', 'assets/perscents_kotak.png')");
    $id_produk = mysqli_insert_id($conn);
    // create produk_varian
    mysqli_query($conn, "INSERT INTO produk_varian (id_produk, id_ukuran, id_ketahanan, harga_final) VALUES ($id_produk, $id_uk, $id_ket, 150000)");
    $varian_id = mysqli_insert_id($conn);
}

// 4) Insert into keranjang for this pelanggan
// First remove any existing test cart items for cleanliness
mysqli_query($conn, "DELETE FROM keranjang WHERE id_pelanggan = $pelanggan_id");

$subtotal = 150000;
mysqli_query($conn, "INSERT INTO keranjang (id_pelanggan, tipe, id_varian, jumlah, subtotal, created_at) VALUES ($pelanggan_id, 'katalog', $varian_id, 1, $subtotal, NOW())");

// 5) Set session and redirect to checkout
$_SESSION['pelanggan_id'] = $pelanggan_id;
$_SESSION['user_id'] = $user_id;

header('Location: checkout.php');
exit;

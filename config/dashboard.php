<?php
require_once'../config/cek_akses.php';
proteksi_halaman('admin');
require_once'../config/koneksi.php';
function getStokSedikit($conn) {
    $query = "SELECT id, nama, stok, gambar FROM produk_collection WHERE stok <= 10 ORDER BY stok ASC";
    return mysqli_query($conn, $query);
}

function getProdukTerlaris($conn) {
    $query = "SELECT 
            pc.id,
            pc.nama,
            pc.foto, 
            SUM(dp.jumlah) AS total_terjual
        FROM detail_pesanan dp
        JOIN produk_collection pc
            ON dp.produk_id = pc.id
        JOIN pesanan p
            ON dp.pesanan_id = p.id
        WHERE p.status = 'lunas'
        GROUP BY pc.id, pc.nama, pc.foto
        ORDER BY total_terjual DESC
        LIMIT 5
    ";
    return mysqli_query($conn, $query);
}

function getTransaksiTerbaru($conn) {
    $query = "SELECT 
            p.id,
            p.kode_pesanan,
            p.nama_pemesan,
            p.total_harga,
            p.status,
            p.created_at
        FROM pesanan p
        ORDER BY p.created_at DESC
        LIMIT 5
    ";

    return mysqli_query($conn, $query);
}
?> 
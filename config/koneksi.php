<?php

$host = "127.0.0.1";
$user = "root";       
$pass = "";           
$db   = "perscents"; 

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

date_default_timezone_set('Asia/Jakarta');

$base_url = "http://localhost/perscents";
?>


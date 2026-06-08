<?php
require_once '../config/cek_akses.php';
require_once '../config/koneksi.php';

// Tangkap filter dari URL
$status_trx = isset($_GET['status_trx']) ? mysqli_real_escape_string($conn, $_GET['status_trx']) : '';
$tgl_mulai = isset($_GET['tgl_mulai']) ? mysqli_real_escape_string($conn, $_GET['tgl_mulai']) : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? mysqli_real_escape_string($conn, $_GET['tgl_akhir']) : '';

$where_clause = "WHERE 1=1";
$teks_periode = "Semua Waktu";
$teks_status = "Semua Status";

if (!empty($status_trx) && $status_trx != 'Semua Status') { 
    $where_clause .= " AND status = '$status_trx'"; 
    $teks_status = ucfirst($status_trx);
}
if (!empty($tgl_mulai) && !empty($tgl_akhir)) { 
    $where_clause .= " AND created_at BETWEEN '$tgl_mulai 00:00:00' AND '$tgl_akhir 23:59:59'"; 
    $teks_periode = date('d M Y', strtotime($tgl_mulai)) . " s/d " . date('d M Y', strtotime($tgl_akhir));
}

// Ambil SEMUA data yang sesuai filter (TANPA LIMIT)
$queryLaporan = mysqli_query($conn, "SELECT * FROM pesanan $where_clause ORDER BY created_at DESC");

// Hitung total uang khusus untuk data yang ditarik ini
$total_pendapatan = 0;
$total_transaksi = mysqli_num_rows($queryLaporan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pendapatan PERSCENTS</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #000; font-size: 12px; line-height: 1.5; margin: 0; padding: 20px; }
        
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 15px; margin-bottom: 25px; display: flex; align-items: center; justify-content: center; position: relative; }
        .kop-surat img { position: absolute; left: 0; width: 80px; height: 80px; object-fit: contain; }
        .kop-teks { text-align: center; }
        .kop-teks h1 { margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 2px; }
        .kop-teks p { margin: 5px 0 0 0; font-size: 12px; }

        .judul-laporan { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; text-decoration: underline; }

        .info-filter { margin-bottom: 20px; font-size: 12px; }
        .info-filter table { width: 50%; }
        .info-filter td { padding: 3px 0; }

        .table-laporan { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 11px; }
        .table-laporan th, .table-laporan td { border: 1px solid #000; padding: 8px 10px; }
        .table-laporan th { background-color: #f0f0f0; text-align: center; font-weight: bold; }
        .table-laporan td { vertical-align: middle; }
        .col-angka { text-align: right; }
        .col-tengah { text-align: center; }

        .total-box { display: flex; justify-content: flex-end; margin-bottom: 40px; }
        .total-table { width: 300px; border-collapse: collapse; font-weight: bold; font-size: 12px; }
        .total-table td { border: 1px solid #000; padding: 10px; }

        .ttd-box { float: right; width: 250px; text-align: center; margin-top: 20px; }
        .ttd-box p { margin: 0 0 70px 0; }

        @media print {
            @page { size: A4 portrait; margin: 15mm; }
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="kop-surat">
        <img src="<?= $base_url ?? '..'; ?>/assets/Logo_Perscents.png" alt="Logo" onerror="this.style.display='none'">
        <div class="kop-teks">
            <h1>PERSCENTS INDONESIA</h1>
            <p>Laboratorium Parfum Custom & Katalog Signature</p>
            <p>Jl. PHH. Mustofa No. 23, Bandung, Jawa Barat | Email: admin@perscents.com</p>
        </div>
    </div>

    <div class="judul-laporan">LAPORAN TRANSAKSI & PENDAPATAN</div>

    <div class="info-filter">
        <table>
            <tr>
                <td width="100"><b>Periode</b></td>
                <td width="10">:</td>
                <td><?= $teks_periode; ?></td>
            </tr>
            <tr>
                <td><b>Status Transaksi</b></td>
                <td>:</td>
                <td><?= $teks_status; ?></td>
            </tr>
            <tr>
                <td><b>Tanggal Cetak</b></td>
                <td>:</td>
                <td><?= date('d F Y, H:i'); ?> WIB</td>
            </tr>
        </table>
    </div>

    <table class="table-laporan">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Kode Pesanan</th>
                <th width="20%">Tanggal</th>
                <th width="25%">Nama Pelanggan</th>
                <th width="15%">Status</th>
                <th width="20%">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if(mysqli_num_rows($queryLaporan) > 0) {
                while($row = mysqli_fetch_assoc($queryLaporan)) {
                    if(strtolower($row['status']) == 'lunas') {
                        $total_pendapatan += $row['total_harga'];
                    }
            ?>
            <tr>
                <td class="col-tengah"><?= $no++; ?></td>
                <td class="col-tengah">#<?= htmlspecialchars($row['kode_pesanan']); ?></td>
                <td><?= date('d M Y, H:i', strtotime($row['created_at'])); ?></td>
                <td><?= htmlspecialchars($row['nama_pemesan']); ?></td>
                <td class="col-tengah"><?= strtoupper($row['status']); ?></td>
                <td class="col-angka">Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
            </tr>
            <?php 
                } 
            } else {
                echo "<tr><td colspan='6' class='col-tengah' style='padding:20px;'>Tidak ada data transaksi pada periode dan status ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="total-box">
        <table class="total-table">
            <tr>
                <td>Total Transaksi</td>
                <td class="col-angka"><?= $total_transaksi; ?> Data</td>
            </tr>
            <tr>
                <td>Total Pendapatan (Lunas)</td>
                <td class="col-angka">Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></td>
            </tr>
        </table>
    </div>

    <div class="ttd-box">
        <p>Bandung, <?= date('d F Y'); ?><br>Dibuat Oleh,</p>
        <p style="text-decoration: underline; font-weight: bold; margin-bottom: 0;">Administrator PERSCENTS</p>
        <small>Divisi Keuangan</small>
    </div>

</body>
</html>
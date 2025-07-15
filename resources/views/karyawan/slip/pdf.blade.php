<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $user->name }} - {{ \Carbon\Carbon::parse($bulan . '-01')->translatedFormat('F Y') }}</title>
    <style>
        @page {
            margin: 100px 40px 100px 40px;
        }
        body {
            font-family: sans-serif;
            font-size: 13px;
            color: #000;
        }
        header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 80px;
            background: #003366;
            color: white;
        }
        footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 60px;
        }
        .logo {
            float: left;
            margin: 15px 0 0 30px;
            height: 50px;
        }
        .footer-bar {
            height: 30px;
            background: #003366;
            color: white;
            text-align: right;
            padding-right: 40px;
            font-size: 11px;
            line-height: 30px;
        }
        .footer-line {
            height: 5px;
            background: #d1b060;
            margin-bottom: 5px;
        }
        h2 {
            text-align: center;
            margin: 0 0 20px;
        }
        .info p {
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 7px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .right {
            text-align: right;
        }
        .summary {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<header>
    <img src="{{ public_path('logo.png') }}" class="logo">
</header>

<footer>
    <div class="footer-line"></div>
    <div class="footer-bar">GITAFUSION.ID</div>
</footer>

<main>
    <h2>Slip Gaji</h2>

    <div class="info">
        <p><strong>Nama:</strong> {{ $user->name }}</p>
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>Bulan:</strong> {{ \Carbon\Carbon::parse($bulan . '-01')->translatedFormat('F Y') }}</p>
    </div>

    @php
        $jumlahAlpa = $rekapDetail['alpa'] ?? 0;
        $jumlahTerlambat = $rekapDetail['terlambat'] ?? 0;
        $totalMinutes = ($rekapDetail['jam_lembur'] ?? 0) * 60;

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        $lemburFormatted = '';
        if ($hours > 0) {
            $lemburFormatted .= $hours . ' jam ';
        }
        if ($minutes > 0 || ($hours == 0 && $minutes == 0)) {
            $lemburFormatted .= $minutes . ' menit';
        }
        if ($hours == 0 && $minutes == 0) {
            $lemburFormatted = '0 jam 0 menit';
        }
    @endphp

    <table>
        <tr>
            <th>Komponen</th>
            <th class="right">Jumlah</th>
        </tr>
        <tr>
            <td>Gaji Pokok</td>
            <td class="right">Rp {{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan</td>
            <td class="right">Rp {{ number_format($gaji->tunjangan, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Bonus Lembur ({{ $lemburFormatted }})</td>
            <td class="right">Rp {{ number_format($bonusLembur, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Potongan Alpa ({{ $jumlahAlpa }} hari)</td>
            <td class="right">- Rp {{ number_format($potonganAlpa, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Potongan Terlambat ({{ $jumlahTerlambat }} kali)</td>
            <td class="right">- Rp {{ number_format($potonganTerlambat, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Total Gaji Diterima</th>
            <th class="right">Rp {{ number_format($totalGaji, 0, ',', '.') }}</th>
        </tr>
    </table>

    <div class="summary">
        <p><strong>Rekap:</strong></p>
        <p>Hadir: {{ $rekapDetail['jumlahHadir'] ?? '-' }} hari</p>
        <p>Alpa: {{ $jumlahAlpa }} hari</p>
        <p>Terlambat: {{ $jumlahTerlambat }} kali</p>
        <p>Izin: {{ $rekapDetail['izin'] ?? 0 }} hari</p>
        <p>Lembur: {{ $lemburFormatted }}</p>
    </div>

    <p class="right" style="margin-top: 30px;">
        Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
    </p>
</main>

</body>
</html>

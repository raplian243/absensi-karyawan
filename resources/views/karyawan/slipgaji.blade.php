@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Slip Gaji - {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}</h2>

    <form method="get" class="mb-3">
        <label>Pilih Bulan:</label>
        <input type="month" name="bulan" value="{{ $bulan }}" class="form-control" onchange="this.form.submit()">
    </form>

    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Nama:</strong> {{ $user->name }}</p>
            <p><strong>Gaji Pokok:</strong> Rp{{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</p>
            <p><strong>Tunjangan:</strong> Rp{{ number_format($gaji->tunjangan, 0, ',', '.') }}</p>
        </div>
    </div>

    <h5>Rekap Absensi:</h5>
    <ul>
        <li>Hadir: {{ $hadir }} hari</li>
        <li>Izin: {{ $izin }} hari</li>
        <li>Alpa: {{ $alpa }} hari (Potongan: Rp{{ number_format($potongan_alpa) }})</li>
        <li>Terlambat: {{ $terlambat }} kali (Potongan: Rp{{ number_format($potongan_terlambat) }})</li>
        <li>Bonus Lembur: Rp{{ number_format($bonus_lembur) }}</li>
    </ul>

    <h5>Total Gaji Bersih:</h5>
    <h3 class="text-success">Rp{{ number_format($gaji_bersih, 0, ',', '.') }}</h3>

    {{-- Cetak PDF (opsional) --}}
    {{-- <a href="{{ route('karyawan.slipgaji.cetak', ['bulan' => $bulan]) }}" class="btn btn-primary mt-3">Cetak PDF</a> --}}
</div>
@endsection

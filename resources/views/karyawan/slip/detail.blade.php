@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h4 class="mb-3">
        Detail Slip Gaji Bulan {{ \Carbon\Carbon::parse($bulan . '-01')->translatedFormat('F Y') }}
    </h4>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <a href="{{ route('karyawan.slip.cetak', $bulan) }}" class="btn btn-success mb-3" target="_blank">
        <i class="bi bi-download"></i> Download PDF
    </a>

    <div class="row">
        <div class="col-md-6">
            <table class="table table-bordered table-sm">
                <tbody>
                    <tr>
                        <th width="50%">Gaji Pokok</th>
                        <td>Rp {{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Tunjangan</th>
                        <td>Rp {{ number_format($gaji->tunjangan, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Bonus Lembur</th>
                        <td>
                            Rp {{ number_format($bonusLembur, 0, ',', '.') }}
                            @php
                                $totalMinutes = $totalLemburMenit ?? 0;
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
                            ({{ $lemburFormatted }}) {{-- Perbaikan di sini --}}
                        </td>
                    </tr>
                    <tr>
                        <th>Potongan Alpa</th>
                        <td>
                            - Rp {{ number_format($potonganAlpa, 0, ',', '.') }}
                            ({{ $jumlahAlpa }} hari)
                        </td>
                    </tr>
                    <tr>
                        <th>Potongan Terlambat</th>
                        <td>
                            - Rp {{ number_format($potonganTerlambat, 0, ',', '.') }}
                            ({{ $jumlahTerlambat }} kali)
                        </td>
                    </tr>
                    <tr class="table-success fw-bold">
                        <th>Total Gaji Bersih</th>
                        <td>Rp {{ number_format($totalGaji, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('karyawan.slip.index') }}" class="btn btn-secondary mt-3">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h4 class="mb-3">Rekap & Slip Gaji Bulanan</h4>

    <form method="GET" action="{{ route('karyawan.slip.index') }}" class="row g-2 align-items-end mb-3">
        <div class="col-md-4">
            <select name="bulan" id="bulan" class="form-select">
                <option value="">Semua Bulan</option>
                @foreach ($rekapans as $bulan => $data)
                    <option value="{{ $bulan }}" {{ request('bulan') == $bulan ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::parse($bulan . '-01')->translatedFormat('F Y') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Cari</button>
        </div>
        <div class="col-auto">
            <a href="{{ route('karyawan.slip.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(empty($rekapans))
        <div class="alert alert-info">Tidak ada data slip gaji yang tersedia.</div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-light">
    <tr>
        <th>Bulan</th>
        <th>Tepat Waktu</th>
        <th>Terlambat</th>
        <th>Izin / Cuti</th>
        <th>Alpa</th>
        <th>Total Waktu Lembur</th>
        <th>Aksi</th>
    </tr>
</thead>
<tbody>
@foreach($rekapans as $bulan => $data)
<tr>
    <td>{{ \Carbon\Carbon::parse($bulan . '-01')->translatedFormat('F Y') }}</td>
    <td>{{ $data['hadir'] - $data['terlambat'] }}</td> {{-- Tepat waktu = hadir - terlambat --}}
    <td>{{ $data['terlambat'] }}</td>
    <td>{{ $data['izin'] ?? 0 }}</td>
    <td>{{ $data['alpa'] }}</td>
    <td>
        @php
            $totalMinutes = $data['total_lembur_menit'] ?? 0;
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
        @endphp
        {{ $hours }} jam {{ $minutes }} menit
    </td>
    <td>
        <button class="btn btn-sm btn-info"
    data-bs-toggle="modal"
    data-bs-target="#modalSlipGaji"
    data-url="{{ route('karyawan.slip.modal', $bulan) }}">
    Lihat Detail
</button>

    </td>
</tr>
@endforeach
</tbody>

            </table>
<!-- Modal -->
<div class="modal fade" id="modalSlipGaji" tabindex="-1" aria-labelledby="modalSlipGajiLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            {{-- PENTING: id="modalSlipContent" ada di div modal-content --}}
            {{-- Konten modal (header, body, footer) akan dimuat ke dalam div ini --}}
            <div class="modal-content" id="modalSlipContent">
                {{-- Konten awal saat memuat (opsional, akan diganti) --}}
                <div class="text-center p-4">Memuat...</div>
      </div>
    </div>
  </div>
</div>


        </div>
    @endif
</div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalSlip = document.getElementById('modalSlipGaji');

    modalSlip.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const url = button.getAttribute('data-url');

        const modalBody = document.getElementById('modalSlipContent');
        modalBody.innerHTML = '<div class="text-center">Memuat...</div>';

        fetch(url)
            .then(response => response.text())
            .then(html => {
                modalBody.innerHTML = html;
            })
            .catch(error => {
                modalBody.innerHTML = '<p class="text-danger">Gagal memuat data.</p>';
            });
    });
});

$(document).ready(function() {
    $('.btn-detail-gaji').on('click', function() {
        var bulan = $(this).data('bulan');

        $.ajax({
            url: '/karyawan/slip-gaji/modal/' + bulan,
            type: 'GET',
            success: function(response) {
                // response adalah HTML view dari Blade
                $('#modalDetailGaji .modal-body').html(response); // langsung isi modal
                $('#modalDetailGaji').modal('show');
            },
            error: function(xhr) {
                alert('Data tidak tersedia.');
            }
        });
    });
});

</script>


@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h4>Rekap Slip Gaji Karyawan</h4>

    @if(session('error'))
        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
    @endif

    {{-- Filter Form --}}
    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-4">
            <input type="text" name="nama" value="{{ request('nama') }}" class="form-control" placeholder="Cari Nama Karyawan">
        </div>
        <div class="col-md-4">
            <select name="bulan" class="form-select">
                <option value="">-- Semua Bulan --</option>
                @foreach($bulanList as $bulan)
                    <option value="{{ $bulan }}" {{ request('bulan') == $bulan ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::parse($bulan . '-01')->translatedFormat('F Y') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="{{ route('admin.slip.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    @if($users->isEmpty())
        <div class="alert alert-warning">Tidak ada karyawan yang tersedia.</div>
    @else
        <div class="table-responsive">
            <table class="text-center table table-bordered">
                <thead class="text-center table-light">
                    <tr>
                        <th>Nama Karyawan</th>
                        <th class="text-center">Tepat Waktu</th>
                        <th class="text-center">Terlambat</th>
                        <th class="text-center">Izin / Cuti</th> 
                        <th class="text-center">Alpa</th>
                        <th class="text-center">Total Jam Lembur</th>
                        <th class="text-center">Bulan Rekap</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
    @foreach($bulanList as $bulan)
@if(!$bulanFilter || $bulan === $bulanFilter)
        @php
            $userRekap = $rekapData[$user->id][$bulan] ?? null;
        @endphp
        @if($userRekap)
        <tr>
            <td>{{ $user->name }}</td>
            <td class="text-center">{{ $userRekap['tepat_waktu'] ?? 0 }}</td>
            <td class="text-center">{{ $userRekap['terlambat'] ?? 0 }}</td>
            <td class="text-center">{{ $userRekap['izin'] ?? 0 }}</td> 
            <td class="text-center">{{ $userRekap['alpa'] ?? 0 }}</td>
            <td class="text-center">
                @php
                    $totalHoursFloat = $userRekap['jam_lembur'] ?? 0; 
                    $hours = floor($totalHoursFloat); 
                    $minutes = round(($totalHoursFloat - $hours) * 60); 
                    $lemburFormatted = '';
                    
                    if ($hours > 0) {
                        $lemburFormatted .= $hours . ' jam ';
                    }
                    if ($minutes > 0 || ($hours == 0 && $minutes == 0)) { 
                        $lemburFormatted .= $minutes . ' menit';
                    }
                    if (empty($lemburFormatted)) {
                        $lemburFormatted = '0 jam 0 menit';
                    }
                @endphp
                {{ $lemburFormatted }}
            </td>
            <td class="text-center">
                <a href="#"
                   class="view-slip-detail"
                   data-bs-toggle="modal"
                   data-bs-target="#detailSlipGajiModal"
                   data-user-id="{{ $user->id }}"
                   data-user-name="{{ $user->name }}"
                   data-bulan="{{ $bulan }}"
                   data-formatted-bulan="{{ \Carbon\Carbon::parse($bulan . '-01')->translatedFormat('F Y') }}">
                    {{ \Carbon\Carbon::parse($bulan . '-01')->translatedFormat('F Y') }}
                </a>
            </td>
        </tr>
@endif
        @endif
    @endforeach
@endforeach

                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Struktur Modal Bootstrap --}}
<div class="modal fade" id="detailSlipGajiModal" tabindex="-1" aria-labelledby="detailSlipGajiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="detailSlipGajiModalLabel">Detail Slip Gaji</h4> {{-- Judul awal, akan diubah JS --}}
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalSlipGajiBody">
                {{-- Konten detail slip gaji akan dimuat di sini --}}
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <p class="mt-2">Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer justify-content-start"> {{-- Tombol di sisi kiri --}}
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a id="downloadPdfButton" href="#" class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Event listener untuk saat modal akan ditampilkan
    document.getElementById('detailSlipGajiModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Tombol yang memicu modal
        const userId = button.getAttribute('data-user-id');
        const userName = button.getAttribute('data-user-name'); // Ambil nama user
        const bulan = button.getAttribute('data-bulan');
        const formattedBulan = button.getAttribute('data-formatted-bulan'); // Ambil bulan terformat
        
        const modalTitle = this.querySelector('#detailSlipGajiModalLabel');
        const modalBody = this.querySelector('#modalSlipGajiBody');
        const downloadPdfButton = this.querySelector('#downloadPdfButton');

        // Atur judul modal
        modalTitle.textContent = `Detail Slip Gaji - ${userName} - ${formattedBulan}`;

        // Reset modal body dan tampilkan loading spinner
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Memuat...</span>
                </div>
                <p class="mt-2">Memuat data...</p>
            </div>
        `;

        // Atur link tombol Download PDF
        downloadPdfButton.href = `/admin/slip/${userId}/${bulan}/cetak`; // Sesuaikan dengan route cetak Anda

        // Lakukan permintaan AJAX untuk memuat konten detail slip gaji
        fetch(`/admin/slip/${userId}/${bulan}/modal-content`)
            .then(response => {
                if (!response.ok) {
                    // Jika respons bukan OK (misal 404, 500), lemparkan error dengan status dan teks
                    return response.text().then(text => { 
                        // Coba parse pesan error dari server jika formatnya JSON
                        try {
                            const errorData = JSON.parse(text);
                            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                        } catch (e) {
                            throw new Error(`HTTP error! status: ${response.status}, message: ${text}`);
                        }
                    });
                }
                return response.text();
            })
            .then(html => {
                modalBody.innerHTML = html; // Masukkan HTML ke dalam modal body
            })
            .catch(error => {
                console.error('Error loading slip gaji detail:', error);
                // Tampilkan pesan error di modal.
                modalBody.innerHTML = `<p class="text-danger">Terjadi kesalahan: ${error.message}. Silakan coba lagi.</p>`;
            });
    });
</script>
@endpush

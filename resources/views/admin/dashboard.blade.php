@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="container mt-4">
<style>
    table th, table td {
        font-size: 10px;
        padding: 2px 3px;
    }

    .table th {
        white-space: nowrap;
    }

    .table-responsive {
        overflow-x: auto;
    }

    /* Custom header color for tables */
    .custom-dark-header {
        background-color: #343a40; /* Dark gray */
        color: #fff; /* White text */
    }
</style>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Data Absensi Karyawan</h2>

        <!-- Tombol Icon untuk Modal Izin + Notifikasi -->
        <button class="btn btn-outline-primary position-relative" data-bs-toggle="modal" data-bs-target="#modalIzinAdmin">
            <i class="bi bi-person-check"></i> Kelola Izin
            @if ($izinMenungguCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    {{ $izinMenungguCount }}
                    <span class="visually-hidden">izin menunggu</span>
                </span>
            @endif
        </button>
    </div>

    {{-- Filter --}}
    <form action="{{ route('admin.dashboard') }}" method="GET" class="row g-3 mb-2">
        <div class="col-md-2">
            <label for="search" class="form-label">Cari Nama Karyawan</label>
            <input type="text" id="search" name="search" class="form-control" placeholder="Misal: Budi" value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <label for="status" class="form-label">Filter Status</label>
            <select name="status" id="status" class="form-select">
                <option value="">-- Semua Status --</option>
                <option value="hadir" {{ request('status') == 'hadir' ? 'selected' : '' }}>Hadir</option>
                <option value="izin" {{ request('status') == 'izin' ? 'selected' : '' }}>Izin</option>
                <option value="alpa" {{ request('status') == 'alpa' ? 'selected' : '' }}>Alpa</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="start_date" class="form-label">Dari Tanggal</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="{{ request('start_date') }}">
        </div>
        <div class="col-md-2">
            <label for="end_date" class="form-label">Sampai Tanggal</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="{{ request('end_date') }}">
        </div>
        <div class="col-md-2">
            <label for="lembur" class="form-label">Filter Lembur</label>
            <select id="lembur" name="lembur" class="form-select">
                <option value="">-- Semua --</option>
                <option value="ya" {{ request('lembur') == 'ya' ? 'selected' : '' }}>Ya</option>
                <option value="tidak" {{ request('lembur') == 'tidak' ? 'selected' : '' }}>Tidak</option>
            </select>
        </div>
        <div class="col-md-2">
            <div class="row g-1">
                <div class="col">
                    <button type="submit" class="btn btn-primary w-100">Terapkan</button>
                </div>
                <div class="col">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary w-100">Reset</a>
                </div>
                <div class="col">
                    <button type="submit" formaction="{{ route('admin.exportExcel') }}" class="btn btn-outline-success w-100">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Tabel Absensi & Izin --}}
    <div class="table-responsive">
        <table class="table table-bordered table-sm small align-middle">
            <thead class="custom-dark-header text-center align-middle">
                <tr>
                    <th>No</th>
                    <th>Nama Karyawan</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th>Jam Masuk</th>
                    <th>Lokasi Masuk</th>
                    <th>Foto Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Lokasi Pulang</th>
                    <th>Foto Pulang</th>
                    <th>Lembur</th>
                    <th>Jam Lembur</th>
                    <th>Waktu Lembur Selesai</th>
                    <th>Durasi Lembur</th>
                    <th>Lampiran</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $combinedData = collect();

                    // Tambahkan data absensi
                    foreach ($absensis as $absen) {
                        $combinedData->push([
                            'type' => 'absensi',
                            'original_data' => $absen,
                            'tanggal_sort' => $absen->tanggal,
                            'tanggal_display' => \Carbon\Carbon::parse($absen->tanggal)->format('d/m/Y'),
                            'nama_karyawan' => $absen->user->name ?? '-',
                            'status_display' => $absen->status, // 'hadir', 'alpa'
                            'keterangan_display' => $absen->keterangan ?? '-',
                            'jam_masuk_display' => $absen->jam_masuk ?? '-',
                            'lokasi_masuk_display' => $absen->lokasi_masuk ?? '-',
                            'foto_masuk_display' => $absen->foto_masuk, // Path lengkap dari DB
                            'jam_pulang_display' => $absen->jam_pulang ?? '-',
                            'lokasi_pulang_display' => $absen->lokasi_pulang ?? '-',
                            'foto_pulang_display' => $absen->foto_pulang, // Path lengkap dari DB
                            'lembur_flag' => $absen->lembur,
                            'jam_lembur_display' => $absen->jam_lembur ?? '-',
                            'waktu_lembur_selesai_display' => $absen->waktu_lembur_selesai ?? '-',
                            'durasi_lembur_display' => $absen->durasi_lembur_formatted,
                            'bukti_display' => null, // Absensi tidak punya bukti izin
                        ]);
                    }

                    // Tambahkan data izin yang difilter (dan disetujui) DARI $izinListForMainTable
                    foreach ($izinListForMainTable as $izin) {
                        // Iterasi setiap hari dalam rentang izin
                        $startDate = \Carbon\Carbon::parse($izin->tanggal_mulai);
                        $endDate = \Carbon\Carbon::parse($izin->tanggal_selesai);

                        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                            $combinedData->push([
                                'type' => 'izin',
                                'original_data' => $izin,
                                'tanggal_sort' => $date->format('Y-m-d'), 
                                'tanggal_display' => $date->format('d/m/Y'),
                                'nama_karyawan' => $izin->user->name ?? '-',
                                'status_display' => 'izin', 
                                'keterangan_display' => $izin->alasan ?? '-',
                                'jam_masuk_display' => '-',
                                'lokasi_masuk_display' => '-',
                                'foto_masuk_display' => null, // Izin tidak punya foto masuk
                                'jam_pulang_display' => '-',
                                'lokasi_pulang_display' => '-',
                                'foto_pulang_display' => null, // Izin tidak punya foto pulang
                                'lembur_flag' => 0, 
                                'jam_lembur_display' => '-',
                                'waktu_lembur_selesai_display' => '-',
                                'durasi_lembur_display' => '-',
                                'bukti_display' => $izin->bukti, // Path bukti izin dari DB
                            ]);
                        }
                    }

                    $sortedCombinedData = $combinedData->sortByDesc('tanggal_sort')->values();
                @endphp

                @forelse ($sortedCombinedData as $index => $data)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $data['nama_karyawan'] }}</td>
                        <td>{{ $data['tanggal_display'] }}</td>
                        <td class="text-center">
                            @switch($data['status_display'])
                                @case('hadir')
                                    <span class="badge bg-success">Hadir</span>
                                    @break
                                @case('izin')
                                    <span class="badge bg-info text-dark">Izin</span>
                                    @break
                                @case('alpa')
                                    <span class="badge bg-danger">Alpa</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($data['status_display']) }}</span>
                            @endswitch
                        </td>
                        <td>{{ $data['keterangan_display'] }}</td>
                        <td class="text-center">{{ $data['jam_masuk_display'] }}</td>
                        <td>{{ $data['lokasi_masuk_display'] }}</td>
                        <td class="text-center">
                            @if ($data['foto_masuk_display'])
                                <a href="{{ asset('storage/' . $data['foto_masuk_display']) }}" target="_blank">Cek</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">{{ $data['jam_pulang_display'] }}</td>
                        <td>{{ $data['lokasi_pulang_display'] }}</td>
                        <td class="text-center">
                            @if ($data['foto_pulang_display'])
                                <a href="{{ asset('storage/' . $data['foto_pulang_display']) }}" target="_blank">Cek</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($data['lembur_flag'] == 1)
                                <span class="badge bg-success text-light">Ya</span>
                            @else
                                <span class="badge bg-secondary text-light">Tidak</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $data['jam_lembur_display'] }}</td>
                        <td class="text-center">{{ $data['waktu_lembur_selesai_display'] }}</td>
                        <td class="text-center">{{ $data['durasi_lembur_display'] }}</td>
                        <td class="text-center">
                            @if ($data['bukti_display'])
                                <a href="{{ asset('storage/' . $data['bukti_display']) }}" target="_blank">Cek</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center">Belum ada data absensi atau izin yang cocok dengan filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Tombol Export -->
    <a href="{{ route('admin.exportExcel') }}" class="btn btn-success mt-2">
        <i class="bi bi-download"></i> Export Semua
    </a>
</div>

{{-- Modal Kelola Izin --}}
<div class="modal fade" id="modalIzinAdmin" tabindex="-1" aria-labelledby="izinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-light">
                <h5 class="modal-title">Daftar Pengajuan Izin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered small">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Alasan</th>
                            <th>Bukti</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modalIzinList as $izin) {{-- PERBAIKAN: Menggunakan $modalIzinList --}}
                            <tr>
                                <td>{{ $izin->user->name ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($izin->tanggal_mulai)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($izin->tanggal_selesai)->format('d/m/Y') }}</td>
                                <td>{{ $izin->alasan ?? '-' }}</td>
                                <td>
                                    @if($izin->bukti)
                                        <a href="{{ asset('storage/' . $izin->bukti) }}" target="_blank">Cek</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $izin->status == 'Tunggu' ? 'secondary' : ($izin->status == 'Terima' ? 'success' : 'danger') }}">
                                        {{ ucfirst($izin->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($izin->status == 'Tunggu')
                                        <form action="{{ route('admin.izin.update', $izin->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button name="status" value="Terima" class="btn btn-sm btn-success">Setujui</button>
                                            <button name="status" value="Tolak" class="btn btn-sm btn-danger">Tolak</button>
                                        </form>
                                    @else
                                        <i class="text-muted">sudah diproses</i>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

<div class="modal fade" id="rekapAbsensiModal" tabindex="-1" aria-labelledby="rekapAbsensiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rekapAbsensiModalLabel">Rekap & Riwayat Absensi Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

            <div class="modal-body modal-body-scroll">
                <ul class="nav nav-tabs mb-3" id="rekapTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="riwayat-tab" data-bs-toggle="tab" data-bs-target="#riwayat" type="button" role="tab" aria-controls="riwayat" aria-selected="true">Riwayat Absensi</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="rekap-tab" data-bs-toggle="tab" data-bs-target="#rekap" type="button" role="tab" aria-controls="rekap" aria-selected="false">Rekap Absensi</button>
                    </li>
                </ul>

                <div class="tab-content" id="rekapTabContent">
                    {{-- === TAB RIWAYAT === --}}
                    <div class="tab-pane fade show active" id="riwayat" role="tabpanel" aria-labelledby="riwayat-tab">
                        <div class="d-flex flex-wrap gap-2 mb-3 align-items-end" id="formRiwayat">
                            <input type="text" id="searchNamaRiwayat" class="form-control" placeholder="Cari Nama..." style="max-width: 200px;" value="{{ $searchNamaAbsensi ?? '' }}">
                            <input type="date" id="searchTanggalRiwayat" class="form-control" style="max-width: 180px;" value="{{ $searchTanggalAbsensi ?? '' }}">
                            <button type="button" class="btn btn-primary" onclick="applyFiltersRiwayat()">Cari</button>
                            <button type="button" class="btn btn-secondary" onclick="resetRiwayat()">Reset</button>
                        </div>

                        <div class="position-relative">
<div id="scrollMainRiwayat" class="table-responsive" style="overflow-x: auto;">
<div id="scrollTopRiwayat" class="overflow-auto mb-1" style="max-width: 100%; overflow-y: hidden;">
                                <div style="width: max-content; height: 1px;"></div>
                            </div>
                            <button type="button" class="btn btn-sm btn-dark position-absolute top-50 start-0 translate-middle-y z-3" onclick="scrollRiwayat(-200)" style="opacity: 0.8;">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-dark position-absolute top-50 end-0 translate-middle-y z-3" onclick="scrollRiwayat(200)" style="opacity: 0.8;">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                                <table class="table table-bordered table-sm align-middle text-center" id="riwayatTable">
                                    <thead class="table-dark align-middle text-center">
                                        <tr>
                                            <th>No</th>
                                            <th>Nama</th>
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
                                            <th>Jam Lembur (Mulai)</th>
                                            <th>Jam Lembur (Selesai)</th>
                                            <th>Durasi Lembur</th>
                                            <th>Bukti</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $combinedData = collect();

                                            foreach (($absensiList ?? []) as $absen) {
                                                $combinedData->push([
                                                    'type' => 'absensi',
                                                    'tanggal_sort' => $absen->tanggal,
                                                    'tanggal_display' => \Carbon\Carbon::parse($absen->tanggal)->format('d/m/Y'),
                                                    'nama_karyawan' => $absen->user->name ?? '-',
                                                    'status_display' => $absen->status,
                                                    'keterangan_display' => $absen->keterangan ?? '-',
                                                    'jam_masuk_display' => $absen->jam_masuk ?? '-',
                                                    'lokasi_masuk_display' => $absen->lokasi_masuk ?? '-',
                                                    'foto_masuk_display' => $absen->foto_masuk,
                                                    'jam_pulang_display' => $absen->jam_pulang ?? '-',
                                                    'lokasi_pulang_display' => $absen->lokasi_pulang ?? '-',
                                                    'foto_pulang_display' => $absen->foto_pulang,
                                                    'lembur_flag' => $absen->lembur,
                                                    'jam_lembur_display' => $absen->jam_lembur ?? '-',
                                                    'waktu_lembur_selesai_display' => $absen->waktu_lembur_selesai ?? '-',
                                                    'durasi_lembur_display' => $absen->lembur == 1 && $absen->jam_lembur && $absen->waktu_lembur_selesai
                                                        ? (\Carbon\Carbon::parse($absen->tanggal . ' ' . $absen->jam_lembur)->diff(\Carbon\Carbon::parse($absen->tanggal . ' ' . $absen->waktu_lembur_selesai)->addDay(Carbon\Carbon::parse($absen->jam_lembur)->gt(\Carbon\Carbon::parse($absen->waktu_lembur_selesai))))->format('%H jam %I menit'))
                                                        : '-',
                                                    'bukti_display' => null,
                                                ]);
                                            }

                                            foreach (($izinListForMainTable ?? []) as $izin) {
                                                $start = \Carbon\Carbon::parse($izin->tanggal_mulai);
                                                $end = \Carbon\Carbon::parse($izin->tanggal_selesai);
                                                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
    if ($date->isWeekday()) { // Tambahkan ini agar hanya hari kerja
        $combinedData->push([
            'type' => 'izin',
            'tanggal_sort' => $date->format('Y-m-d'),
            'tanggal_display' => $date->format('d/m/Y'),
            'nama_karyawan' => $izin->user->name ?? '-',
            'status_display' => 'izin',
            'keterangan_display' => $izin->alasan ?? '-',
            'jam_masuk_display' => '-',
            'lokasi_masuk_display' => '-',
            'foto_masuk_display' => null,
            'jam_pulang_display' => '-',
            'lokasi_pulang_display' => '-',
            'foto_pulang_display' => null,
            'lembur_flag' => 0,
            'jam_lembur_display' => '-',
            'waktu_lembur_selesai_display' => '-',
            'durasi_lembur_display' => '-',
            'bukti_display' => $izin->bukti,
        ]);
    }
}
}


                                            $sortedData = $combinedData->sortByDesc('tanggal_sort')->values();
                                        @endphp

                                        @forelse ($sortedData as $index => $data)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td class="nama">{{ $data['nama_karyawan'] }}</td>
                                                <td class="tanggal-absensi" data-date="{{ $data['tanggal_sort'] }}">{{ $data['tanggal_display'] }}</td>
                                                <td>
                                                    @switch($data['status_display'])
                                                        @case('hadir') <span class="badge bg-success">Hadir</span> @break
                                                        @case('izin') <span class="badge bg-info text-dark">Izin</span> @break
                                                        @case('alpa') <span class="badge bg-danger">Alpa</span> @break
                                                        @default <span class="badge bg-secondary">{{ ucfirst($data['status_display']) }}</span>
                                                    @endswitch
                                                </td>
                                                <td>{{ $data['keterangan_display'] }}</td>
                                                <td>{{ $data['jam_masuk_display'] }}</td>
                                                <td>{{ $data['lokasi_masuk_display'] }}</td>
                                                <td>@if ($data['foto_masuk_display']) <a href="{{ asset('storage/' . $data['foto_masuk_display']) }}" target="_blank">Cek</a> @else - @endif</td>
                                                <td>{{ $data['jam_pulang_display'] }}</td>
                                                <td>{{ $data['lokasi_pulang_display'] }}</td>
                                                <td>@if ($data['foto_pulang_display']) <a href="{{ asset('storage/' . $data['foto_pulang_display']) }}" target="_blank">Cek</a> @else - @endif</td>
                                                <td>@if ($data['lembur_flag']) <span class="badge bg-success">Ya</span> @else <span class="badge bg-secondary">Tidak</span> @endif</td>
                                                <td>{{ $data['jam_lembur_display'] }}</td>
                                                <td>{{ $data['waktu_lembur_selesai_display'] }}</td>
                                                <td>{{ $data['durasi_lembur_display'] }}</td>
                                                <td>@if ($data['bukti_display']) <a href="{{ asset('storage/' . $data['bukti_display']) }}" target="_blank">Cek</a> @else - @endif</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="16" class="text-center">Tidak ada data absensi atau izin.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- === TAB REKAP === --}}
                    <div class="tab-pane fade" id="rekap" role="tabpanel" aria-labelledby="rekap-tab">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div class="d-flex gap-2 align-items-end flex-wrap">
                                <input type="text" id="searchNamaRekap" class="form-control" placeholder="Cari Nama..." style="max-width: 200px;" value="{{ $searchNamaAbsensi ?? '' }}">
                                <button type="button" class="btn btn-primary" onclick="applyFiltersRekap()">Cari</button>
                                <button type="button" class="btn btn-secondary" onclick="resetRekap()">Reset</button>
                            </div>

                            <div class="d-flex align-items-center gap-2 me-auto ms-md-5">
                                <form method="GET" action="{{ route('direktur.dashboard') }}" class="m-0">
                                    <input type="hidden" name="bulan" value="{{ \Carbon\Carbon::parse($searchBulan)->subMonth()->format('Y-m') }}">
                                    <input type="hidden" name="showModal" value="true">
                                    <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></button>
                                </form>
                                <h5 class="m-0 text-center">Rekap Absensi Bulan {{ \Carbon\Carbon::parse($searchBulan)->translatedFormat('F Y') }}</h5>
                                <form method="GET" action="{{ route('direktur.dashboard') }}" class="m-0">
                                    <input type="hidden" name="bulan" value="{{ \Carbon\Carbon::parse($searchBulan)->addMonth()->format('Y-m') }}">
                                    <input type="hidden" name="showModal" value="true">
                                    <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></button>
                                </form>
                            </div>
                        </div>

                        @if (empty($rekapPerKaryawan))
                            <div class="alert alert-warning">Tidak ada data karyawan untuk ditampilkan.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm table-striped text-center align-middle" id="rekapTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Nama</th>
                                            <th>Tepat Waktu</th>
                                            <th>Terlambat</th>
                                            <th>Izin</th>
                                            <th>Alpa</th>
                                            <th>Total Jam Lembur</th>
                                            <th>Bulan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rekapPerKaryawan as $rekap)
                                            <tr>
                                                <td class="nama">{{ $rekap['nama'] }}</td>
                                                <td>{{ $rekap['tepat_waktu'] }}</td>
                                                <td>{{ $rekap['terlambat'] }}</td>
                                                <td>{{ $rekap['izin'] }}</td>
                                                <td>{{ $rekap['alpa'] }}</td>
                                                <td>{{ $rekap['total_lembur_formatted'] }}</td>
                                                <td class="bulan-rekap" data-bulan="{{ $rekap['bulan_rekap'] }}">{{ \Carbon\Carbon::parse($rekap['bulan_rekap'] . '-01')->translatedFormat('F Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- Penting: Pastikan script ini berada di bagian bawah absensikaryawan.blade.php --}}
<script>
    // Fungsi untuk mengaplikasikan filter Riwayat
    function applyFiltersRiwayat() {
        const nama = document.getElementById('searchNamaRiwayat').value.toLowerCase();
        const tanggal = document.getElementById('searchTanggalRiwayat').value; // Format: YYYY-MM-DD

        document.querySelectorAll('#riwayatTable tbody tr').forEach(row => {
            const rowNama = row.querySelector('.nama')?.textContent.toLowerCase() || '';
            const rowTanggalData = row.querySelector('.tanggal-absensi')?.dataset.date || ''; // Get YYYY-MM-DD from data attribute

            const cocokNama = nama === '' || rowNama.includes(nama);
            const cocokTanggal = tanggal === '' || rowTanggalData === tanggal;

            row.style.display = (cocokNama && cocokTanggal) ? '' : 'none';
        });
    }

    // Fungsi untuk mereset filter Riwayat
    function resetRiwayat() {
        document.getElementById('searchNamaRiwayat').value = '';
        document.getElementById('searchTanggalRiwayat').value = '';
        // Anda perlu melakukan reload halaman atau memanggil ulang AJAX untuk memuat data tanpa filter
        // Untuk saat ini, kita hanya akan menyembunyikan/menampilkan baris berdasarkan filter yang dihapus
        applyFiltersRiwayat(); 
        // Jika data dimuat melalui AJAX, Anda akan memanggil fungsi AJAX di sini:
        // fetchRiwayatData(); 
    }

    // Fungsi untuk mengaplikasikan filter Rekap
    function applyFiltersRekap() {
        const nama = document.getElementById('searchNamaRekap').value.toLowerCase();
        const bulan = '{{ $searchBulan }}';

        document.querySelectorAll('#rekapTable tbody tr').forEach(row => {
            const rowNama = row.querySelector('.nama')?.textContent.toLowerCase() || '';
            const rowBulanData = row.querySelector('.bulan-rekap')?.dataset.bulan || ''; // Get YYYY-MM from data attribute

            const cocokNama = nama === '' || rowNama.includes(nama);
            const cocokBulan = rowBulanData === bulan;

            row.style.display = (cocokNama && cocokBulan) ? '' : 'none';
        });
    }

    // Fungsi untuk mereset filter Rekap
    function resetRekap() {
        document.getElementById('searchNamaRekap').value = '';
        document.getElementById('searchBulanRekap').value = '';
        // Anda perlu melakukan reload halaman atau memanggil ulang AJAX untuk memuat data tanpa filter
        // Untuk saat ini, kita hanya akan menyembunyikan/menampilkan baris berdasarkan filter yang dihapus
        applyFiltersRekap();
        // Jika data dimuat melalui AJAX, Anda akan memanggil fungsi AJAX di sini:
        // fetchRekapData();
    }


    document.addEventListener("DOMContentLoaded", function () {
        const scrollTop = document.getElementById('scrollTopRiwayat');
        const scrollMain = document.getElementById('scrollMainRiwayat');

        // Sinkron scroll horizontal
        if (scrollTop && scrollMain) {
            scrollTop.addEventListener('scroll', () => {
                scrollMain.scrollLeft = scrollTop.scrollLeft;
            });

            scrollMain.addEventListener('scroll', () => {
                scrollTop.scrollLeft = scrollMain.scrollLeft;
            });

            // Set lebar dummy div agar sama dengan isi tabel
            const riwayatTable = document.getElementById('riwayatTable');
            if (riwayatTable) {
                // Pastikan tabel sudah dirender sebelum mengambil scrollWidth
                setTimeout(() => {
                    scrollTop.firstElementChild.style.width = riwayatTable.scrollWidth + 'px';
                }, 100); // Sedikit delay untuk memastikan rendering
            }
        }

        // Terapkan filter awal saat modal dimuat jika ada nilai pencarian dari controller
        // Ini akan berjalan ketika absensikaryawan.blade.php di-include dan DOMContentLoaded terpicu
        // Perlu diperhatikan bahwa filter di blade ini hanya bekerja pada data yang SUDAH ADA.
        // Jika Anda ingin filter di sisi server, Anda perlu menggunakan form submit atau AJAX.
        // Untuk sekarang, saya asumsikan filter ini hanya untuk client-side filtering.
        applyFiltersRiwayat();
        applyFiltersRekap();
    });

    function scrollRiwayat(offset) {
        const scrollContainer = document.getElementById('scrollMainRiwayat');
        if (scrollContainer) {
            scrollContainer.scrollBy({
                left: offset,
                behavior: 'smooth'
            });
        }
    }

    // Tambahan: Script untuk memastikan tab aktif tetap setelah filter
    document.addEventListener('show.bs.tab', function (e) {
        // Simpan tab yang aktif ke localStorage atau cookie
        localStorage.setItem('activeTab', e.target.id);
    });

    document.addEventListener('DOMContentLoaded', function () {
        const activeTab = localStorage.getItem('activeTab');
        if (activeTab) {
            const tabButton = document.getElementById(activeTab);
            if (tabButton) {
                const bsTab = new bootstrap.Tab(tabButton);
                bsTab.show();
            }
        }
    });
</script>

<style>
    /* Styling untuk tabel di dalam modal */
    #riwayatTable th,
    #riwayatTable td {
        font-size: 12px;
        padding: 4px 6px;
        vertical-align: middle;
        white-space: nowrap; /* Mencegah teks di sel tabel wrap */
    }

    #rekapTable th,
    #rekapTable td {
        font-size: 13px;
        padding: 5px 7px;
        vertical-align: middle;
        white-space: nowrap; /* Mencegah teks di sel tabel wrap */
    }

    /* Penyesuaian untuk layar kecil jika diperlukan */
    @media (max-width: 768px) {
        .btn-scroll-horizontal {
            font-size: 10px;
            padding: 2px 6px;
        }
    }
</style>
@if ($bukaModal === true)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = new bootstrap.Modal(document.getElementById('rekapAbsensiModal'));
        modal.show();
    });
</script>
@endif

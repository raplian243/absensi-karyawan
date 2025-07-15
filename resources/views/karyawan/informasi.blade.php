<!-- Modal Informasi Riwayat -->
<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Riwayat Absen & Izin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs mb-3" id="infoTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="izin-tab" data-bs-toggle="tab" data-bs-target="#izin" type="button" role="tab">Riwayat Izin</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="absensi-tab" data-bs-toggle="tab" data-bs-target="#absensi" type="button" role="tab">Riwayat Absensi</button>
                    </li>
                </ul>

                <div class="tab-content" id="infoTabContent">
                    <!-- Tab Riwayat Izin -->
                    <div class="tab-pane fade show active" id="izin" role="tabpanel">
                        <div class="mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <input type="date" id="filterIzinTanggal" class="form-control form-control-sm" placeholder="Filter Tanggal">
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-sm btn-primary" onclick="applyFilterIzin()">Cari</button>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-sm btn-secondary" onclick="resetFilterIzin()">Reset</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle" id="izinTable">
                                <thead class="custom-dark-header text-center align-middle">
                                    <tr>
                                        <th>Tanggal Mulai</th>
                                        <th>Tanggal Selesai</th>
                                        <th>Alasan</th>
                                        <th>Lampiran</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($izinList as $izin)
                                        <tr>
                                            <td>{{ $izin->tanggal_mulai }}</td>
                                            <td>{{ $izin->tanggal_selesai }}</td>
                                            <td>{{ $izin->alasan }}</td>
                                            <td>
                                                @if ($izin->bukti)
                                                    <a href="{{ asset('storage/' . $izin->bukti) }}" target="_blank">Lihat</a>
                                                @else
                                                    Tidak ada Lampiran
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge 
                                                    @if($izin->status == 'Terima') bg-success
                                                    @elseif($izin->status == 'Tolak') bg-danger
                                                    @else bg-secondary @endif">
                                                    {{ $izin->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center">Tidak ada data izin.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Riwayat Absensi -->
                    <div class="tab-pane fade" id="absensi" role="tabpanel">
                        <div class="mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <input type="date" id="filterAbsenTanggal" class="form-control form-control-sm" placeholder="Filter Tanggal">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" id="filterAbsenStatus" class="form-control form-control-sm" placeholder="Cari Status">
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-sm btn-primary" onclick="applyFilterAbsen()">Cari</button>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-sm btn-secondary" onclick="resetFilterAbsen()">Reset</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle" id="absenTable">
                                <thead class="custom-dark-header text-center align-middle">
                                    <tr>
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
                                        <th>Waktu Mulai Lembur</th>
                                        <th>Waktu Selesai Lembur</th>
                                        <th>Durasi Lembur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($absenList as $absen)
                                        <tr>
                                            <td>{{ $absen->tanggal }}</td>
                                            <td>
                                                @switch($absen->status)
                                                    @case('hadir') <span class="badge bg-success">Hadir</span> @break
                                                    @case('izin') <span class="badge bg-info text-dark">Izin</span> @break
                                                    @case('alpa') <span class="badge bg-danger">Alpa</span> @break
                                                    @default <span class="badge bg-secondary">{{ $absen->status }}</span>
                                                @endswitch
                                            </td>
                                            <td>{{ $absen->keterangan ?? '-' }}</td>
                                            <td>{{ $absen->jam_masuk ?? '-' }}</td>
                                            <td>{{ $absen->lokasi_masuk ?? '-' }}</td>
                                            <td>
                                                @if ($absen->foto_masuk)
                                                    <a href="{{ asset('storage/' . $absen->foto_masuk) }}" target="_blank">Lihat</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $absen->jam_pulang ?? '-' }}</td>
                                            <td>{{ $absen->lokasi_pulang ?? '-' }}</td>
                                            <td>
                                                @if ($absen->foto_pulang)
                                                    <a href="{{ asset('storage/' . $absen->foto_pulang) }}" target="_blank">Lihat</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @if ($absen->lembur && $absen->waktu_lembur_selesai && $absen->durasi_lembur)
                                                    <span class="badge bg-success text-light">Ya</span>
                                                @else
                                                    <span class="badge bg-secondary text-light">Tidak</span>
                                                @endif
                                            </td>
                                            <td>{{ $absen->jam_lembur ?? '-' }}</td>
                                            <td>{{ $absen->waktu_lembur_selesai ?? '-' }}</td>
                                            <td>{{ $absen->durasi_lembur ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="12" class="text-center">Tidak ada data absensi.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Script Filter -->
<script>
    // Fungsi untuk memformat tanggal YYYY-MM-DD menjadi YYYY-MM-DD (konsisten)
    function formatDateForComparison(dateString) {
        if (!dateString) return '';
        // Coba parse dengan Date untuk format yang mungkin bervariasi
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return ''; // Tanggal tidak valid
        }
        return date.toISOString().split('T')[0];
    }

    // IZIN
    function applyFilterIzin() {
        const table = document.getElementById('izinTable');
        if (!table) return;

        const tanggalFilter = document.getElementById('filterIzinTanggal').value;
        const alasanFilter = document.getElementById('filterIzinAlasan').value.toLowerCase();

        const rows = table.querySelector("tbody").querySelectorAll("tr");

        rows.forEach(row => {
            const tanggalMulaiCell = row.querySelectorAll("td")[0]; // Kolom Tanggal Mulai
            const alasanCell = row.querySelectorAll("td")[2];     // Kolom Alasan

            const rowTanggalMulai = tanggalMulaiCell ? formatDateForComparison(tanggalMulaiCell.textContent) : '';
            const rowAlasan = alasanCell ? alasanCell.textContent.toLowerCase() : '';

            let showRow = true;

            // Filter Tanggal
            if (tanggalFilter && rowTanggalMulai !== tanggalFilter) {
                showRow = false;
            }

            // Filter Alasan
            if (alasanFilter && !rowAlasan.includes(alasanFilter)) {
                showRow = false;
            }

            row.style.display = showRow ? "" : "none";
        });
    }

    function resetFilterIzin() {
        document.getElementById('filterIzinTanggal').value = '';
        document.getElementById('filterIzinAlasan').value = '';
        resetTable('izinTable');
    }

    // ABSENSI
    function applyFilterAbsen() {
        const table = document.getElementById('absenTable');
        if (!table) return;

        const tanggalFilter = document.getElementById('filterAbsenTanggal').value;
        const statusFilter = document.getElementById('filterAbsenStatus').value.toLowerCase();

        const rows = table.querySelector("tbody").querySelectorAll("tr");

        rows.forEach(row => {
            const tanggalCell = row.querySelectorAll("td")[0]; // Kolom Tanggal
            const statusCell = row.querySelectorAll("td")[1]; // Kolom Status

            const rowTanggal = tanggalCell ? formatDateForComparison(tanggalCell.textContent) : '';
            const rowStatus = statusCell ? statusCell.textContent.toLowerCase() : '';

            let showRow = true;

            // Filter Tanggal
            if (tanggalFilter && rowTanggal !== tanggalFilter) {
                showRow = false;
            }

            // Filter Status
            if (statusFilter && !rowStatus.includes(statusFilter)) {
                showRow = false;
            }

            row.style.display = showRow ? "" : "none";
        });
    }

    function resetFilterAbsen() {
        document.getElementById('filterAbsenTanggal').value = '';
        document.getElementById('filterAbsenStatus').value = '';
        resetTable('absenTable');
    }

    function resetTable(tableId) {
        let table = document.getElementById(tableId);
        if (!table) return; // Tambahkan cek ini
        let rows = table.querySelector("tbody").querySelectorAll("tr");
        for (let row of rows) {
            row.style.display = "";
        }
    }
</script>

<style>
    /* Mengecilkan ukuran tabel */
    table#izinTable,
    table#absenTable {
        font-size: 13px; /* ukuran font lebih kecil */
    }

    /* Header tabel dengan warna khusus */
    thead.custom-dark-header {
        background-color: #343a40; /* warna dark */
        color: #fff;               /* teks putih */
    }

    /* Warna garis border */
    table.table-bordered th,
    table.table-bordered td {
        border-color: #dee2e6 !important;
    }

    /* Responsif dan padding lebih kecil */
    .table-sm th,
    .table-sm td {
        padding: 0.3rem;
    }
</style>

@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Kalender Kehadiran</h2>

    <!-- Navigasi Kalender -->
    <div class="d-flex justify-content-center align-items-center mb-3" style="gap: 16px;">
        <button id="prevBtn" class="btn btn-sm text-black border-0" style="font-size: 20px;">&#8592;</button>
        <h4 class="mb-0" id="calendarTitle" style="font-weight: bold;"></h4>
        <button id="nextBtn" class="btn btn-sm text-black border-0" style="font-size: 20px;">&#8594;</button>
    </div>

    <!-- Kalender -->
    <div id='calendar'></div>

    <!-- Legend -->
    <div class="mt-4">
        <strong>Keterangan:</strong>
        <ul class="list-inline">
            <li class="list-inline-item"><span class="legend-circle bg-success"></span> Hadir</li>
            <li class="list-inline-item"><span class="legend-circle bg-warning"></span> Terlambat</li>
            <li class="list-inline-item"><span class="legend-circle bg-primary"></span> Izin / Cuti</li>
            <li class="list-inline-item"><span class="legend-circle bg-danger"></span> Alpa</li>
        </ul>
    </div>
</div>

<!-- Modal Ringkasan -->
<div class="modal fade" id="absenModal" tabindex="-1" aria-labelledby="absenModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="absenModalLabel">Ringkasan Absensi</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p><strong>Tanggal:</strong> <span id="modalTanggal"></span></p>
        <p><strong>Status:</strong> <span id="modalStatus"></span></p>
        <p><strong>Keterangan:</strong> <span id="modalKeterangan"></span></p>
        <p><strong>Jam Masuk:</strong> <span id="modalMasuk"></span></p>
        <p><strong>Jam Pulang:</strong> <span id="modalPulang"></span></p>
        <p><strong>Lembur:</strong> <span id="modalLembur"></span></p>
<p><strong>Durasi Lembur:</strong> <span id="modalDurasiLembur"></span></p>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('fullcalendar/main.min.css') }}">
<style>
    #calendar { max-width: 420px; margin: 0 auto; }
    .fc-col-header-cell { background-color: #003366; }
    .fc-col-header-cell-cushion { color: white !important; font-weight: bold; padding: 10px 0; display: block; text-align: center; text-decoration: none; }
    .fc-daygrid-day { border-radius: 12px; overflow: hidden; transition: 0.3s; }
    .fc-daygrid-day-frame { display: flex; justify-content: center; align-items: center; height: 100%; }
    .fc-daygrid-day-number { position: static !important; font-weight: bold; font-size: 14px; color: #000000; }
    .fc-day-sun .fc-daygrid-day-number { color: red !important; }
    .fc-day-hadir .fc-daygrid-day-number { background: #28a745; color: #fff; border-radius: 50%; padding: 4px 8px; }
    .fc-day-terlambat .fc-daygrid-day-number { background: #ffc107; color: #fff; border-radius: 50%; padding: 4px 8px; }
    .fc-day-izin .fc-daygrid-day-number { background: #007bff; color: #fff; border-radius: 50%; padding: 4px 8px; }
    .fc-day-alpa .fc-daygrid-day-number { background: #dc3545; color: #fff; border-radius: 50%; padding: 4px 8px; }
    .legend-circle { width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 6px; }
    .fc-toolbar-title { display: none !important; }
    .fc-button { background-color: #007bff; border: none; }
    .fc-button:hover { background-color: #0056b3; }
    .fc-button-primary:not(:disabled):active { background-color: #0056b3; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('fullcalendar/main.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');
        const calendarTitle = document.getElementById('calendarTitle');

        // Ambil data dari Blade
        const status = {!! json_encode($statusPerDate ?? []) !!};
        const absenData = {!! json_encode($absenPerTanggal ?? []) !!};
        const events = {!! json_encode($events ?? []) !!};

        // Fungsi untuk mendapatkan tanggal lokal dengan format YYYY-MM-DD
        function formatDateToLocalYYYYMMDD(date) {
            const localDate = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
            return localDate.toISOString().split('T')[0];
        }

        if (calendarEl) {
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                initialDate: new Date(),
                headerToolbar: false,
                firstDay: 0,
                events: events,
                datesSet: function(info) {
    const currentMonth = new Date(info.view.currentStart);
    const options = { month: 'long', year: 'numeric' };
    calendarTitle.textContent = currentMonth.toLocaleDateString('id-ID', options);

    // Tambahan: Render ulang warna dot
    setTimeout(() => {
        document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
            const dateStr = cell.getAttribute('data-date');
            if (!dateStr) return;

            // Hapus class dot warna sebelumnya
            cell.classList.remove('fc-day-hadir', 'fc-day-terlambat', 'fc-day-izin', 'fc-day-alpa');

            // Tambahkan class baru jika ada status
            if (status[dateStr]) {
                cell.classList.add('fc-day-' + status[dateStr]);
            }
        });
    }, 10); // delay sedikit agar DOM sudah selesai dirender
},

                dayCellDidMount: function (info) {
                    const dateStr = formatDateToLocalYYYYMMDD(info.date);
                    if (status[dateStr]) {
                        info.el.classList.add('fc-day-' + status[dateStr]);
                    }
                },
                dateClick: function(info) {
                    const dateStr = info.dateStr;
                    const data = absenData[dateStr];

                    document.getElementById('modalTanggal').textContent = dateStr;

                   if (data) {
    document.getElementById('modalStatus').textContent = data.status ?? '-';
    document.getElementById('modalKeterangan').textContent = data.keterangan ?? '-';
    document.getElementById('modalMasuk').textContent = data.jam_masuk ?? '-';
    document.getElementById('modalPulang').textContent = data.jam_pulang ?? '-';
document.getElementById('modalLembur').textContent = data.lembur ?? '-';
document.getElementById('modalDurasiLembur').textContent = data.durasi_lembur ?? '-';
} else {
    document.getElementById('modalStatus').textContent = '-';
    document.getElementById('modalKeterangan').textContent = '-';
    document.getElementById('modalMasuk').textContent = '-';
    document.getElementById('modalPulang').textContent = '-';
document.getElementById('modalLembur').textContent = '-';
document.getElementById('modalDurasiLembur').textContent = '-';
}

                    const modal = new bootstrap.Modal(document.getElementById('absenModal'));
                    modal.show();
                }
            });

            calendar.render();

            document.getElementById('prevBtn').addEventListener('click', function () {
                calendar.prev();
            });

            document.getElementById('nextBtn').addEventListener('click', function () {
                calendar.next();
            });

        } else {
            console.error("Elemen #calendar tidak ditemukan.");
        }
    });
</script>
@endpush

@php
  use Carbon\Carbon;
  use App\Models\Absensi;
  use App\Models\Izin;

  $hariIni = Carbon::today()->toDateString();
  $userId = auth()->id();

  $absenHariIni = Absensi::where('user_id', $userId)
                  ->whereDate('tanggal', $hariIni)
                  ->first();

  $statusAbsen = $absenHariIni->status ?? null;
  $sudahAbsenMasuk = $absenHariIni && $absenHariIni->jam_masuk;
  $sudahAbsenPulang = $absenHariIni && $absenHariIni->jam_pulang;

  // Cari apakah hari ini masuk dalam rentang izin yang sudah DITERIMA
  $izinHariIni = Izin::where('user_id', $userId)
      ->where('status', 'Terima')
      ->whereDate('tanggal_mulai', '<=', $hariIni)
      ->whereDate('tanggal_selesai', '>=', $hariIni)
      ->exists();

@endphp


@extends('layouts.app')
<style>
  .custom-dark-header {
    background-color: #003366 !important;
    color: white !important;
  }
</style>
@section('content')
<div class="container py-3">

@if (session('error'))
  <div class="alert alert-danger">
    {{ session('error') }}
  </div>
@endif

@if(session('absen_success'))
  <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    {{ session('absen_success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if(session('izin_success'))
  <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    {{ session('izin_success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if ($statusAbsen === 'alpa' && !$izinHariIni)
  <div class="text-center alert alert-danger mt-3">
    Maaf Kamu Telah <strong>Alpa</strong>.
  </div>
@endif

@if ($izinHariIni)
  <div class="text-center alert alert-info mt-3">
    Kamu Sedang <strong>Izin</strong> dan Pengajuan Telah di Terima.
  </div>
@endif


  <!-- Judul -->
  <div class="text-center mb-0">
    <h3>Absensi</h3>
            <div class="d-flex ms-auto">
                <span class="navbar-text">
                    Selamat datang, <strong>{{ auth()->user()->name }}
</strong>
                </span>
            </div>
  </div>

  <!-- Tombol Ikon Besar: Form Izin & Form Hadir -->
  <div class="row g-5 justify-content-center">
    <div class="col-md-5 text-center">
@if($statusAbsen === 'alpa' || $sudahAbsenMasuk || $sudahAbsenPulang || $izinHariIni)
  <button class="btn btn-secondary btn-lg w-100 py-4" disabled>
    <i class="bi bi-pencil-square fs-2"></i><br>Form Izin
  </button>
@else
  <button class="btn btn-outline-primary btn-lg w-100 py-4" data-bs-toggle="modal" data-bs-target="#formizin">
    <i class="bi bi-pencil-square fs-2"></i><br>Form Izin
  </button>
@endif
    </div>
<div class="col-md-5 text-center">
  @if($statusAbsen === 'alpa' || $sudahAbsenPulang || $izinHariIni)
  <button class="btn btn-secondary btn-lg w-100 py-4" disabled>
    <i class="bi bi-check-circle fs-2"></i><br>Form Hadir
  </button>
@else
  <button class="btn btn-outline-success btn-lg w-100 py-4"
    data-bs-toggle="modal" data-bs-target="#formhadir">
    <i class="bi bi-check-circle fs-2"></i><br>Form Hadir
  </button>
@endif
</div>
  </div>

  <!-- Tombol Info Riwayat -->
  <div class="text-center mt-2">
    <button class="btn btn-info btn-sm px-4" data-bs-toggle="modal" data-bs-target="#infoModal">
      <i class="bi bi-info-circle"></i> Lihat Riwayat
    </button>
  </div>

<!-- Ketentuan Absensi -->
<div class="mt-4">
  <div class="card shadow-sm">
    <div class="card-header bg-dark text-white py-2">
      <strong class="small">Ketentuan Absensi</strong>
    </div>
    <div class="card-body small">
      <div class="row">
        <div class="col-md-6">
          <ul class="mb-0">
            <li>Absen masuk dilakukan sebelum pukul 08.00 WIB.</li>
            <li>Absen pulang dilakukan minimal pukul 17.00 WIB.</li>
            <li>Keterlambatan akan tercatat otomatis sebagai "Terlambat".</li>
            <li>Absensi dilakukan melalui aplikasi dengan foto selfie dan lokasi GPS.</li>
            <li>Upload foto harus menggunakan format jpg.jpeg, atau png.</li>
            <li>Foto harus jelas, menunjukkan wajah, dan berlatar tempat kerja.</li>
            <li>Jika tidak bisa hadir, wajib mengisi form izin dan melampirkan bukti.</li>
          </ul>
        </div>
        <div class="col-md-6">
          <ul class="mb-0">
            <li>Izin dianggap sah jika telah disetujui oleh atasan atau HRD.</li>
            <li>Jika lembur, cantumkan jam lembur saat absen pulang.</li>
            <li>Tidak absen tanpa keterangan dianggap "Alpa".</li>
            <li>Dilarang melakukan absensi untuk orang lain (titip absen).</li>
            <li>Penyalahgunaan sistem akan dikenakan sanksi sesuai aturan.</li>
            <li>Data absensi digunakan untuk rekap kehadiran dan penilaian kinerja.</li>
            <li>Setiap karyawan bertanggung jawab atas absensinya sendiri.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Modal: Form Izin -->
@include('karyawan.form-izin')

<!-- Modal: Form Hadir -->
@include('karyawan.form-hadir')

<!-- Modal: Informasi Riwayat -->
@include('karyawan.informasi')

{{-- Script untuk membuka tab "Riwayat Absensi" jika berhasil absen --}}
<script>
  window.onload = () => {
    const infoModal = new bootstrap.Modal(document.getElementById('infoModal'));

    @if (session('izin_success'))
      infoModal.show();
      const izinTab = document.getElementById('izin-tab');
      if (izinTab) izinTab.click();
    @endif

    @if (session('absen_success'))
      infoModal.show();
      const absensiTab = document.getElementById('absensi-tab');
      if (absensiTab) absensiTab.click();
    @endif
  };
</script>

@endsection

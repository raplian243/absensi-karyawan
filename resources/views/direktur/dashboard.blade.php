<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Direktur</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- CSRF Token --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Bootstrap --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <style>
    .navbar-custom .logo-wrapper {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
    }

    .top-actions {
      position: relative;
      margin-bottom: 2rem;
    }

    .top-actions .left-btn,
    .top-actions .right-btn {
      position: absolute;
      top: 0;
    }

    .top-actions .left-btn {
      left: 0;
    }

    .top-actions .right-btn {
      right: 0;
    }

    .top-actions h4 {
      text-align: center;
    }
  </style>
</head>
<body>
  {{-- Navbar --}}
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary navbar-custom position-relative">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      
      {{-- Kiri: Nama User --}}
      <div class="d-flex align-items-center gap-3">
        <span class="nav-link text-white mb-0">
          <i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }}
        </span>
      </div>

      {{-- Tengah: Logo --}}
      <div class="logo-wrapper text-center">
        <a class="navbar-brand d-flex align-items-center" href="#">
          <img src="{{ asset('logo.png') }}" alt="Logo" class="img-fluid" style="max-height: 45px;">
        </a>
      </div>

      {{-- Kanan: Logout --}}
      <div class="d-flex align-items-center gap-3">
        <form method="POST" action="{{ route('logout') }}" class="m-0">
          @csrf
          <button type="submit" class="btn btn-link text-white text-decoration-none">
            <i class="bi bi-box-arrow-right me-1"></i> Keluar
          </button>
        </form>
      </div>
    </div>
  </nav>

  {{-- Main Content --}}
  <div class="container mt-4">
    <div class="top-actions">
      <div class="left-btn">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#rekapAbsensiModal">
          <i class="bi bi-clipboard-check"></i> Absensi Karyawan
        </button>
      </div>

<div class="d-flex justify-content-center align-items-center mb-3" style="gap: 16px;">
    <a href="{{ route('direktur.dashboard', ['bulan' => \Carbon\Carbon::parse($searchBulan)->subMonth()->format('Y-m')]) }}" class="btn btn-sm text-black border-0" style="font-size: 20px;">&#8592;</a>
    <h4 class="mb-0" style="font-weight: bold;">Grafik Absensi Bulan {{ $currentMonth }}</h4>
    <a href="{{ route('direktur.dashboard', ['bulan' => \Carbon\Carbon::parse($searchBulan)->addMonth()->format('Y-m')]) }}" class="btn btn-sm text-black border-0" style="font-size: 20px;">&#8594;</a>
</div>




      <div class="right-btn">
        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#rekapGajiModal">
          <i class="bi bi-cash-coin"></i> Gaji Karyawan
        </button>
      </div>
    </div>

    {{-- Grafik Total --}}
    <div class="card shadow-sm mx-auto mb-4 p-4 rounded-4 border-0" style="max-width: 1000px;">
      <canvas id="grafikAbsensi"></canvas>
    </div>

    {{-- Grafik Per Karyawan --}}
    <div class="card shadow-sm mx-auto p-4 rounded-4 border-0" style="max-width: 1000px;">
      <h5 class="text-center mb-3">Grafik Kehadiran per Karyawan</h5>
      <div style="overflow-x: auto;">
        <canvas id="grafikPerKaryawan" height="200"></canvas>
      </div>
    </div>
  </div>

  {{-- Scripts --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

  <script>
    const chartTotal = new Chart(document.getElementById('grafikAbsensi'), {
      type: 'bar',
      data: {
        labels: ['Tepat Waktu', 'Terlambat', 'Izin', 'Alpa'],
        datasets: [{
          label: 'Jumlah',
          data: [
            {{ $totalTepatWaktu ?? 0 }},
            {{ $totalTerlambat ?? 0 }},
            {{ $totalIzin ?? 0 }},
            {{ $totalAlpa ?? 0 }}
          ],
          backgroundColor: ['#28a745', '#ffc107', '#0dcaf0', '#dc3545'],
          borderRadius: 8,
          barThickness: 40
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: { enabled: true },
          datalabels: {
            display: ctx => ctx.dataset.data[ctx.dataIndex] > 0,
            color: '#333',
            anchor: 'end',
            align: 'top',
            font: {
              size: 12,
              weight: 'bold'
            }
          }
        },
        scales: {
  y: {
    beginAtZero: true,
    grace: '10%', // Tambahkan ruang 10% di atas batang tertinggi
    ticks: {
      stepSize: 1,
      precision: 0
    }
  }
}
      },
      plugins: [ChartDataLabels]
    });

    const chartData = @json($rekapPerKaryawan);
    const labels = chartData.map(item => item.nama);
    const tepatWaktu = chartData.map(item => item.tepat_waktu);
    const terlambat = chartData.map(item => item.terlambat);
    const izin = chartData.map(item => item.izin);
    const alpa = chartData.map(item => item.alpa);

    const chartPerKaryawan = new Chart(document.getElementById('grafikPerKaryawan'), {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          { label: 'Tepat Waktu', data: tepatWaktu, backgroundColor: '#28a745' },
          { label: 'Terlambat', data: terlambat, backgroundColor: '#ffc107' },
          { label: 'Izin', data: izin, backgroundColor: '#0dcaf0' },
          { label: 'Alpa', data: alpa, backgroundColor: '#dc3545' }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 15,
              font: { size: 12 }
            }
          },
          tooltip: { enabled: true },
          datalabels: {
            display: ctx => ctx.dataset.data[ctx.dataIndex] > 0,
            color: '#333',
            anchor: 'end',
            align: 'top',
            font: {
              size: 10,
              weight: 'bold'
            }
          }
        },
        scales: {
          x: {
            ticks: {
              autoSkip: false,
              maxRotation: 45,
              minRotation: 30,
              font: {
                size: 10
              }
            }
          },
          y: {
            beginAtZero: true,
grace: '10%',
            ticks: {
              stepSize: 1,
              precision: 0
            }
          }
        }
      },
      plugins: [ChartDataLabels]
    });
  </script>

  {{-- Modals --}}
  @include('direktur.absensikaryawan')
  @include('direktur.gajikaryawan')
</body>
</html>

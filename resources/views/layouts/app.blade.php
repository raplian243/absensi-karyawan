<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <meta charset="UTF-8">
    <title>@yield('title', 'Sistem Absensi')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    {{-- Bootstrap CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    @stack('styles')
</head>
<body>
    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            {{-- Logo kiri --}}
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="{{ asset('logo.png') }}" alt="Logo" class="img-fluid" style="max-height: 40px;">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-between" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    @auth
@if (auth()->user()->role == env('ROLE_ADMIN'))
    <li class="nav-item border-start border-white">
        <a class="nav-link text-white" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-clipboard-check"></i> Absensi Karyawan
        </a>
    </li>
    <li class="nav-item border-start border-white">
        <a class="nav-link text-white" href="{{ route('admin.gaji.index') }}">
            <i class="bi bi-file-text"></i> Gaji Karyawan
        </a>
    </li>
    <li class="nav-item border-start border-white">
        <a class="nav-link text-white" href="{{ route('admin.slip.index') }}">
            <i class="bi bi-file-earmark-spreadsheet"></i> Rekap & Slip Gaji Karyawan
        </a>
    </li>
    <li class="nav-item border-start border-white">
        <a class="nav-link text-white" href="{{ route('admin.users.index') }}">
            <i class="bi bi-people"></i> Daftar Pengguna
        </a>
    </li>
    <li class="nav-item border-start border-white">
    </li>
@elseif (auth()->user()->role == env('ROLE_KARYAWAN'))
    <li class="nav-item border-start border-white">
        <a class="nav-link text-white" href="{{ route('karyawan.absensi') }}">
            <i class="bi bi-clipboard-check"></i> Absensi
        </a>
    </li>
    <li class="nav-item border-start border-white">
        <a class="nav-link text-white" href="{{ route('kalender.index') }}">
            <i class="bi bi-calendar-event"></i> Kalender
        </a>
    </li>
    <li class="nav-item border-start border-white">
        <a class="nav-link text-white" href="{{ route('slip.index') }}">
            <i class="bi bi-file-text"></i> Rekap & Slip Gaji
        </a>
</li>
    <li class="nav-item border-start border-white">
    </li>
@elseif (auth()->user()->role == env('ROLE_DIREKTUR'))
    <li class="nav-item border-start border-white">
        <a class="nav-link text-white" href="{{ route('direktur.dashboard') }}">
            <i class="bi bi-bar-chart-line"></i> Dashboard Direktur
        </a>
    </li>
@endif
                    @endauth
                </ul>

               <ul class="navbar-nav ms-auto align-items-center d-flex gap-2">
    @auth
        <li class="nav-item">
            <span class="nav-link text-white d-flex align-items-center">
                <i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }}
            </span>
        </li>
        <li class="nav-item d-flex align-items-center">
    <form action="{{ route('logout') }}" method="POST" id="logout-form" class="m-0 p-0">
        @csrf
        <button type="submit" class="btn btn-link nav-link text-white p-0 d-flex align-items-center">
            <i class="bi bi-box-arrow-right me-1"></i> Keluar
        </button>
    </form>
</li>
    @endauth
</ul>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main class="py-4">
        @yield('content')
    </main>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        if (window.axios) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }
    </script>

    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Absensi Karyawan</a>
            <div class="ms-auto">
                <span class="text-white me-3">👤 {{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-8 mx-auto">

                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Dashboard Karyawan</h5>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('absen.form') }}" class="btn btn-success mb-3">📥 Form Absensi</a>

                        <h6>📋 Riwayat Absensi Terakhir</h6>
                        @if($absensis->isEmpty())
                            <div class="alert alert-warning mt-2">Belum ada data absensi.</div>
                        @else
                            <ul class="list-group mt-2">
                                @foreach($absensis as $absen)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $absen->tanggal }} - {{ $absen->lokasi }}
                                        <span class="badge bg-primary">✅</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>

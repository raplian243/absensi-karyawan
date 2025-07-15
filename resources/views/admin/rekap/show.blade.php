@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Rekap Absensi Bulan {{ \Carbon\Carbon::parse($bulan . '-01')->translatedFormat('F Y') }}</h4>

    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Alpa</th>
                <th>Lembur (jam)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr>
                    <td>{{ $row['user']->name }}</td>
                    <td>{{ $row['hadir'] }}</td>
                    <td>{{ $row['terlambat'] }}</td>
                    <td>{{ $row['alpa'] }}</td>
                    <td>{{ $row['lembur_jam'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

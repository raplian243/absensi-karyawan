@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Rekap Absensi Per Bulan</h4>
    <ul>
        @foreach ($bulanList as $bulan)
            <li>
                <a href="{{ route('admin.rekap.show', $bulan) }}">
                    {{ \Carbon\Carbon::parse($bulan . '-01')->translatedFormat('F Y') }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
@endsection

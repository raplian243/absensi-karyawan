@extends('layouts.app')

@section('content')
<div class="container">
    <div class="alert alert-success">
        <h1>Dashboard Umum</h1>
        <p>Selamat datang, {{ Auth::user()->name }}</p>
    </div>
</div>
@endsection

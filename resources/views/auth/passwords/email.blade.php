@extends('layouts.app')

@section('content')
<style>
    .checkmark {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: block;
        stroke-width: 2;
        stroke: #4CAF50;
        stroke-miterlimit: 10;
        box-shadow: inset 0px 0px 0px #4CAF50;
        animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        margin: 10px auto;
    }

    .checkmark__circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #4CAF50;
        fill: none;
        animation: stroke .6s cubic-bezier(.65,.05,.36,1) forwards;
    }

    .checkmark__check {
        stroke: #4CAF50;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        fill: none;
        animation: stroke .3s cubic-bezier(.65,.05,.36,1) .6s forwards;
    }

    @keyframes stroke {
        100% { stroke-dashoffset: 0; }
    }

    @keyframes scale {
        0%, 100% { transform: none; }
        50% { transform: scale3d(1.1, 1.1, 1); }
    }

    @keyframes fill {
        100% { box-shadow: inset 0px 0px 0px 30px #4CAF50; }
    }
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Reset Kata Sandi') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="text-center">
                            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                                <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                                <path class="checkmark__check" d="M14 27l8 8 16-16"/>
                            </svg>
                            <div class="alert alert-success mt-2" role="alert">
                                link reset kata sandi telah dikirim ke email anda ^_^
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">Alamat Email</label>

                            <div class="col-md-6">
                                <input id="email" type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Kirim Link Reset
                                </button>
                                <a href="{{ route('login') }}" class="btn btn-secondary">
                                    Login Kembali
                                </a>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

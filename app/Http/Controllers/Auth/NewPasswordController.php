<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules;
use Illuminate\Auth\Events\PasswordReset;

class NewPasswordController extends Controller
{
    /**
     * Menampilkan form reset password.
     */
    public function create(Request $request): View
    {
        return view('auth.passwords.reset', [
            'request' => $request,
            'token' => $request->route('token'),
            'email' => $request->email,
        ]);
    }

    /**
     * Memproses permintaan reset password.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'token.required'     => 'token reset tidak ditemukan.',
            'email.required'     => 'email wajib diisi.',
            'email.email'        => 'format email tidak valid.',
            'password.required'  => 'kata sandi wajib diisi.',
            'password.confirmed' => 'konfirmasi kata sandi tidak cocok.',
            'password.min'       => 'kata sandi minimal harus :min karakter.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                Auth::login($user);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $user = Auth::user();
            if ($user && $user->role === 'admin') {
                return redirect()->intended('/admin/dashboard')->with('status', __($status));
            } else {
                return redirect()->intended('/dashboard')->with('status', __($status));
            }
        }

        return back()->withErrors(['email' => [__($status)]]);
    }
}

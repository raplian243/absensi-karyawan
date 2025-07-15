<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * Ke mana user diarahkan setelah reset password berhasil.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Aturan validasi untuk form reset password.
     */
    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ];
    }

    /**
     * Pesan error kustom untuk validasi reset password.
     */
    protected function validationErrorMessages()
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal harus terdiri dari 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }
public function showResetForm(Request $request, $token = null)
{
    return view('auth.passwords.reset')->with([
        'token' => $token,
        'email' => $request->email,
    ]);
}
}

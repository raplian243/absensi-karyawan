<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Redirect setelah login berdasarkan role.
     */
protected function redirectTo()
{
    $user = Auth::user();

    if (!$user || !in_array($user->role, ['admin', 'karyawan', 'direktur'])) {
        Auth::logout();
        return '/login';
    }

    if ($user->role === 'admin') {
        return '/admin/dashboard';
    } elseif ($user->role === 'karyawan') {
        return '/karyawan/absensi';
    } elseif ($user->role === 'direktur') {
        return '/direktur/dashboard';
    }

    return '/login';
}


    /**
     * Middleware hanya untuk guest, kecuali logout.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Proses login dengan validasi dan redirect sesuai role.
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if (Auth::attempt(
            ['email' => $request->email, 'password' => $request->password],
            $request->filled('remember')
        )) {
            $request->session()->regenerate();
            return redirect()->intended($this->redirectPath());
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ]);
    }

    /**
     * Logout user dengan aman.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('status', 'Anda berhasil logout.');
    }
}

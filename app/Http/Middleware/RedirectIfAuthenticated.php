<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle($request, Closure $next, ...$guards)
    {
        $guard = $guards[0] ?? null;

        if (Auth::guard($guard)->check()) {
            $user = Auth::user();
            if ($user->role === env('ROLE_ADMIN')) {
                return redirect('/admin/dashboard');
            } elseif ($user->role === env('ROLE_KARYAWAN')) {
                return redirect('/dashboard');
            }
        }

        return $next($request);
    }
}

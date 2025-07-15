<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define the route that users are redirected to after login/reset password.
     */
    public static function redirectTo()
    {
        $user = Auth::user();

        if (!$user || !in_array($user->role, ['admin', 'karyawan'])) {
            return '/login';
        }

        return $user->role === 'admin' ? '/admin/dashboard' : '/karyawan/absensi';
    }

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map()
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }
}

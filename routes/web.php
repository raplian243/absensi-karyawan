<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AkunController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\IzinController; // tambahkan controller izin
use App\Http\Controllers\KalenderController;
use App\Http\Controllers\AdminRekapAbsensiController;
use App\Http\Controllers\AdminSlipGajiController;
use App\Http\Controllers\KaryawanSlipGajiController;
use App\Http\Controllers\AdminGajiController;
use App\Http\Controllers\AdminExportController;
use App\Http\Controllers\DirekturController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root ke login
Route::get('/', fn() => redirect('/login'));

// Auth default
Auth::routes();

// Login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// ✅ Logout harus POST
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Reset password (manual override)
Route::middleware('guest')->group(function () {
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

// Tes email
Route::get('/tes-email', function () {
    Mail::raw('Ini adalah tes email dari Laravel menggunakan Gmail SMTP.', function ($message) {
        $message->to('alamatpenerima@gmail.com')
                ->subject('Tes Email dari Laravel');
    });
    return 'Email berhasil dikirim!';
});

/*
|--------------------------------------------------------------------------
| Route untuk ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', RoleMiddleware::class . ':admin'])
->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

        Route::view('/absensi', 'admin.dashboard')->name('admin.absensi');
        Route::view('/rekap', 'admin.rekap')->name('admin.rekap');
        Route::view('/karyawan', 'admin.karyawan')->name('admin.karyawan');
Route::put('/izin/{id}', [AdminController::class, 'updateIzin'])->name('admin.izin.update');
    Route::get('/admin/export-excel', [AdminExportController::class, 'exportExcel'])
        ->name('admin.exportExcel');


        // Route validasi izin
        Route::post('/izin/{id}/setujui', [IzinController::class, 'setujui'])->name('admin.izin.setujui');
        Route::post('/izin/{id}/tolak', [IzinController::class, 'tolak'])->name('admin.izin.tolak');
        Route::get('/izin', [IzinController::class, 'index'])->name('admin.izin.index');

	// Admin - menyetujui dan menolak izin
Route::put('/admin/izin/{id}/setujui', [IzinController::class, 'setujui'])->name('admin.izin.setujui');
Route::put('/admin/izin/{id}/tolak', [IzinController::class, 'tolak'])->name('admin.izin.tolak');
Route::put('/izin/{id}', [IzinController::class, 'update'])->name('admin.izin.update');



   Route::get('/rekap-absensi', [AdminRekapAbsensiController::class, 'index'])->name('admin.rekap.index');
    Route::get('/rekap-absensi/{bulan}', [AdminRekapAbsensiController::class, 'show'])->name('admin.rekap.show');
    Route::get('/slip/', [AdminSlipGajiController::class, 'index'])->name('admin.slip.index');
    Route::get('/slip/{userId}/{bulan}', [AdminSlipGajiController::class, 'show'])->name('admin.slip.show');
Route::get('slip/{userId}/{bulan}/modal-content', [AdminSlipGajiController::class, 'getSlipGajiModalContent'])->name('slip.modal_content');
Route::get('/slip/{user}/{bulan}/cetak', [AdminSlipGajiController::class, 'cetak'])->name('admin.slip.cetak');
    Route::get('/rekap-absensi', [AdminRekapAbsensiController::class, 'index'])->name('admin.rekap.index');
Route::get('/slip/rekap', [App\Http\Controllers\AdminRekapAbsensiController::class, 'rekap'])->name('admin.slip.rekap');

    });

Route::middleware(['auth', RoleMiddleware::class . ':admin'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    });
Route::middleware(['auth', RoleMiddleware::class . ':admin'])
    ->prefix('admin')->name('admin.users.')->group(function () {
    Route::get('/users', [AkunController::class, 'index'])->name('index');
    // ...
});

Route::middleware(['auth', RoleMiddleware::class . ':admin'])
    ->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/gaji', [AdminGajiController::class, 'index'])->name('gaji.index');
    Route::get('/gaji/create', [AdminGajiController::class, 'create'])->name('gaji.create');
    Route::post('/gaji', [AdminGajiController::class, 'store'])->name('gaji.store');
    Route::get('/gaji/{gaji}/edit', [AdminGajiController::class, 'edit'])->name('gaji.edit');
    Route::put('/gaji/{gaji}', [AdminGajiController::class, 'update'])->name('gaji.update');
    Route::delete('/gaji/{gaji}', [AdminGajiController::class, 'destroy'])->name('gaji.destroy');
    
    Route::get('/gaji/create-modal-content', [AdminGajiController::class, 'createModalContent'])->name('gaji.create_modal_content');
    Route::get('/gaji/{gaji}/edit-modal-content', [AdminGajiController::class, 'editModalContent'])->name('gaji.edit_modal_content');
});


/*
|--------------------------------------------------------------------------
| Route untuk KARYAWAN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', RoleMiddleware::class . ':karyawan'])
    ->prefix('karyawan')
    ->group(function () {

    // Halaman dashboard absensi
    Route::get('/absensi', [AbsensiController::class, 'dashboardKaryawan'])->name('karyawan.absensi');

    // Absen masuk dan pulang
    Route::post('/absensi/masuk', [AbsensiController::class, 'store'])->name('absensi.masuk');
Route::post('/absen/masuk', [AbsensiController::class, 'store'])->name('absen.store');
Route::post('/absensi/pulang', [AbsensiController::class, 'pulang'])->name('absen.pulang');
Route::post('/absen/lembur', [AbsensiController::class, 'lembur'])->name('absen.lembur');



    // Proses pengajuan izin
    Route::post('/izin/ajukan', [IzinController::class, 'ajukan'])->name('izin.ajukan');

    // Kalender
    Route::get('/kalender', [KalenderController::class, 'index'])->name('kalender.index');
Route::get('/modal/{bulan}', [KaryawanSlipGajiController::class, 'getSlipGajiModalContent'])->name('modal'); // Rute untuk modal
    Route::get('/detail/{bulan}', [KaryawanSlipGajiController::class, 'show'])->name('show'); // Jika Anda punya halaman detail terpisah
    Route::get('/cetak/{bulan}', [KaryawanSlipGajiController::class, 'cetak'])->name('cetak'); // Rute untuk cetak PDF
    Route::get('/slip-gaji', [KaryawanSlipGajiController::class, 'index'])->name('karyawan.slipgaji');
    Route::get('/slip-gaji', [KaryawanSlipGajiController::class, 'index'])->name('karyawan.slip.index');
    Route::get('/slip-gaji/{bulan}', [KaryawanSlipGajiController::class, 'show'])->name('karyawan.slip.show');
    Route::get('/slip-gaji/cetak', [KaryawanSlipGajiController::class, 'cetak'])->name('karyawan.slipgaji.cetak'); // opsional untuk PDF
Route::get('/slip', [KaryawanSlipGajiController::class, 'index'])->name('slip.index');
Route::get('/slip-gaji/modal/{bulan}', [KaryawanSlipGajiController::class, 'getSlipGajiModalContent']);
Route::get('/karyawan/slip-gaji/modal/{bulan}', [KaryawanSlipGajiController::class, 'getSlipGajiModalContent'])
    ->name('karyawan.slip.modal');
Route::get('/slip/{bulan}/cetak', [KaryawanSlipGajiController::class, 'cetak'])->name('karyawan.slip.cetak');

    });

/*
|--------------------------------------------------------------------------
| Route untuk DIREKTUR
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', RoleMiddleware::class . ':direktur'])
    ->group(function () {
Route::get('/direktur/dashboard', [DirekturController::class, 'index'])->name('direktur.dashboard');
Route::get('/direktur/riwayat', [DirekturController::class, 'riwayat'])->name('direktur.riwayat');
    Route::get('/direktur/absensi', [DirekturController::class, 'absensiKaryawan'])->name('direktur.absensi');
    Route::get('/direktur/gaji', [DirekturController::class, 'gajiKaryawan'])->name('direktur.gaji');

});

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\GajiKaryawan;
use App\Models\Izin;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log; // Tambahkan ini untuk logging

class KaryawanSlipGajiController extends Controller
{
    /**
     * Menampilkan halaman rekap slip gaji bulanan karyawan.
     * Mengambil data absensi dan izin untuk user yang sedang login,
     * lalu menghitung rekapitulasi per bulan.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            $userId = Auth::id(); // Mendapatkan ID user yang sedang login
            $filterBulan = $request->bulan; // Mengambil filter bulan dari request

            // Mengambil daftar bulan unik dari absensi user yang sedang login
            $absensiBulan = Absensi::where('user_id', $userId)
                ->selectRaw('DATE_FORMAT(tanggal, "%Y-%m") as bulan');

            // Mengambil daftar bulan unik dari izin user yang sedang login (status 'Terima')
            // Menggunakan union untuk menggabungkan tanggal mulai dan tanggal selesai izin
            $izinBulan = Izin::where('user_id', $userId)
                ->where('status', 'Terima')
                ->selectRaw('DATE_FORMAT(tanggal_mulai, "%Y-%m") as bulan_data') // Alias 'bulan_data'
                ->union(
                    Izin::where('user_id', $userId)
                        ->where('status', 'Terima')
                        ->selectRaw('DATE_FORMAT(tanggal_selesai, "%Y-%m") as bulan_data') // Alias 'bulan_data'
                );

            // Menggabungkan bulan dari absensi dan izin, mengambil yang unik, lalu mengurutkan secara descending
            $bulanList = collect($absensiBulan->get()->pluck('bulan'))
                ->merge($izinBulan->get()->pluck('bulan_data')) // Gunakan alias yang sama di sini
                ->unique()
                ->sortDesc()
                ->values(); // Reset keys setelah unique dan sort

            // Jika ada filter bulan, saring bulanList
            if ($filterBulan) {
                $bulanList = $bulanList->filter(fn($b) => $b == $filterBulan);
            }

            // Mengambil semua data izin yang diterima untuk user ini
            $izinList = Izin::where('user_id', $userId)
                ->where('status', 'Terima')
                ->get();

            // Menghitung jumlah hari izin per bulan
            $izinPerBulan = [];
            foreach ($izinList as $izin) {
                // Membuat periode tanggal dari tanggal_mulai hingga tanggal_selesai
                $period = CarbonPeriod::create($izin->tanggal_mulai, $izin->tanggal_selesai);
                foreach ($period as $date) {
                    // Hanya hitung hari kerja (Senin-Jumat)
                    if ($date->dayOfWeek !== Carbon::SATURDAY && $date->dayOfWeek !== Carbon::SUNDAY) {
                        $bulan = $date->format('Y-m');
                        $izinPerBulan[$bulan] = ($izinPerBulan[$bulan] ?? 0) + 1;
                    }
                }
            }

            $rekapans = []; // Array untuk menyimpan rekapitulasi per bulan
            foreach ($bulanList as $bulan) {
                // Mengambil semua absensi untuk bulan ini
                $absensisBulanIni = Absensi::where('user_id', $userId)
                    ->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$bulan])
                    ->get();

                $hadir = 0;
                $alpa = 0;
                $terlambat = 0;
                $totalLemburMenit = 0;

                // Loop melalui absensi untuk menghitung status kehadiran dan lembur
                foreach ($absensisBulanIni as $absen) {
                    if ($absen->status === 'hadir') {
                        $hadir++;
                        if ($absen->keterangan === 'Terlambat') {
                            $terlambat++;
                        }
                    } elseif ($absen->status === 'alpa') {
                        $alpa++;
                    }

                    // Menghitung durasi lembur
                    if ($absen->lembur == 1 && $absen->jam_lembur && $absen->waktu_lembur_selesai) {
                        try {
                            $start = Carbon::parse($absen->tanggal . ' ' . $absen->jam_lembur);
                            $end = Carbon::parse($absen->tanggal . ' ' . $absen->waktu_lembur_selesai);
                            // Jika waktu selesai lembur lebih awal dari waktu mulai lembur (melewati tengah malam)
                            if ($end->lt($start)) {
                                $end->addDay();
                            }
                            $totalLemburMenit += abs($end->diffInMinutes($start));
                        } catch (\Exception $e) {
                            Log::error("Error calculating overtime for user {$userId}, absensi ID {$absen->id}: " . $e->getMessage());
                        }
                    }
                }

                $izin = $izinPerBulan[$bulan] ?? 0; // Jumlah hari izin untuk bulan ini
                $gaji = GajiKaryawan::where('user_id', $userId)->first(); // Mengambil data gaji karyawan

                $potonganAlpa = 0;
                $potonganTerlambat = 0;
                $bonusLembur = 0;
                $totalGaji = 0;

                // Menghitung potongan dan bonus jika data gaji tersedia
                if ($gaji) {
                    $potonganAlpa = $gaji->gaji_pokok * ($alpa * config('app.potongan_alpa', 3)) / 100;
                    $potonganTerlambat = $gaji->gaji_pokok * ($terlambat * config('app.potongan_terlambat', 1)) / 100;
                    $bonusLembur = ($totalLemburMenit / 60) * config('app.bonus_lembur_per_jam', 10000);
                    $totalGaji = $gaji->gaji_pokok + $gaji->tunjangan + $bonusLembur - $potonganAlpa - $potonganTerlambat;
                }

                // Menyimpan data rekapitulasi untuk bulan ini
                $rekapans[$bulan] = [
                    'hadir' => $hadir,
                    'alpa' => $alpa,
                    'terlambat' => $terlambat,
                    'izin' => $izin,
                    'total_lembur_menit' => $totalLemburMenit,
                    'total_gaji' => round($totalGaji), // Pembulatan total gaji
                ];
            }

            return view('karyawan.slip.index', compact('rekapans', 'filterBulan'));
        } catch (\Exception $e) {
            Log::error("Error in KaryawanSlipGajiController@index: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat halaman rekap: ' . $e->getMessage());
        }
    }

    /**
     * Mengambil konten detail slip gaji untuk ditampilkan di modal.
     * Ini adalah method yang akan dipanggil via AJAX/Fetch dari modal.
     *
     * @param string $bulan Bulan dalam format YYYY-MM
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function getSlipGajiModalContent($bulan)
    {
        try {
            // Validasi format bulan
            if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
                return '<p class="text-danger">Format bulan tidak valid.</p>';
            }

            $user = Auth::user(); // Mendapatkan user yang sedang login

            // Mengambil data absensi untuk bulan yang diminta
            $absensis = Absensi::where('user_id', $user->id)
                ->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$bulan])
                ->get();

            // Mengambil data izin yang diterima untuk bulan yang diminta
            $izins = Izin::where('user_id', $user->id)
                ->where('status', 'Terima')
                ->where(function ($query) use ($bulan) {
                    $startOfMonth = Carbon::parse($bulan . '-01');
                    $endOfMonth = $startOfMonth->copy()->endOfMonth();

                    $query->whereBetween('tanggal_mulai', [$startOfMonth, $endOfMonth])
                        ->orWhereBetween('tanggal_selesai', [$startOfMonth, $endOfMonth])
                        ->orWhere(function ($q2) use ($startOfMonth, $endOfMonth) {
                            $q2->where('tanggal_mulai', '<=', $startOfMonth)
                                ->where('tanggal_selesai', '>=', $endOfMonth);
                        });
                })
                ->get();

            // Jika tidak ada data absensi maupun izin untuk bulan ini
            if ($absensis->isEmpty() && $izins->isEmpty()) {
                return '<p class="text-danger">Data absensi atau izin tidak ditemukan untuk bulan ini.</p>';
            }

            // Mengambil data gaji karyawan
            $gaji = GajiKaryawan::where('user_id', $user->id)->first();
            if (!$gaji) {
                return '<p class="text-danger">Data gaji Anda belum tersedia. Silakan hubungi admin.</p>';
            }

            // --- Perhitungan detail rekap (mirip dengan logika di index/show/cetak) ---
            $jumlahHadir = $absensis->where('status', 'hadir')->count();
            $jumlahAlpa = $absensis->where('status', 'alpa')->count();
            $jumlahTerlambat = $absensis->where('status', 'hadir')
                ->filter(fn($absen) => $absen->keterangan === 'Terlambat')->count();

            $jumlahHariIzin = 0;
            foreach ($izins as $izin) {
                $startDate = Carbon::parse($izin->tanggal_mulai);
                $endDate = Carbon::parse($izin->tanggal_selesai);
                $startOfMonth = Carbon::parse($bulan . '-01');
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                $current = $startDate->copy()->max($startOfMonth);
                $end = $endDate->copy()->min($endOfMonth);

                while ($current->lte($end)) {
                    if ($current->dayOfWeekIso < 6) { // Hanya hitung hari kerja (Senin-Jumat)
                        $jumlahHariIzin++;
                    }
                    $current->addDay();
                }
            }

            $totalLemburMenit = 0;
            foreach ($absensis as $absen) {
                if ($absen->lembur == 1 && $absen->jam_lembur && $absen->waktu_lembur_selesai) {
                    try {
                        $start = Carbon::parse($absen->tanggal . ' ' . $absen->jam_lembur);
                        $end = Carbon::parse($absen->tanggal . ' ' . $absen->waktu_lembur_selesai);
                        if ($end->lt($start)) $end->addDay();
                        $totalLemburMenit += abs($end->diffInMinutes($start));
                    } catch (\Exception $e) {
                        Log::error("Error calculating overtime in modal for user {$user->id}, absensi ID {$absen->id}: " . $e->getMessage());
                    }
                }
            }

            $potonganAlpa = $gaji->gaji_pokok * ($jumlahAlpa * config('app.potongan_alpa', 3)) / 100;
            $potonganTerlambat = $gaji->gaji_pokok * ($jumlahTerlambat * config('app.potongan_terlambat', 1)) / 100;
            $bonusLembur = ($totalLemburMenit / 60) * config('app.bonus_lembur_per_jam', 10000);
            $totalGaji = $gaji->gaji_pokok + $gaji->tunjangan + $bonusLembur - $potonganAlpa - $potonganTerlambat;

            // Siapkan array rekapDetail untuk kompatibilitas dengan Blade modal
            $rekapDetail = [
                'alpa' => $jumlahAlpa,
                'terlambat' => $jumlahTerlambat,
                'izin' => $jumlahHariIzin,
                'jam_lembur' => $totalLemburMenit / 60, // Konversi menit ke jam
                'absensis_raw' => $absensis, // Data absensi mentah untuk rincian
                'izins_raw' => $izins, // Data izin mentah untuk rincian
            ];

            // Render view khusus modal
            return view('karyawan.slip.modal_detail_content', compact(
                'user', 'bulan', 'gaji', 'potonganAlpa', 'potonganTerlambat', 'bonusLembur', 'totalGaji',
                'rekapDetail' // Kirim rekapDetail
            ));
        } catch (\Exception $e) {
            Log::error("Error in KaryawanSlipGajiController@getSlipGajiModalContent for user {$user->id}, bulan {$bulan}: " . $e->getMessage());
            return '<p class="text-danger">Terjadi kesalahan server: ' . $e->getMessage() . '</p>';
        }
    }

    /**
     * Menampilkan detail slip gaji untuk bulan tertentu (full page view).
     * Method ini bisa digunakan jika ada rute terpisah untuk menampilkan detail
     * slip gaji di halaman penuh, bukan hanya di modal.
     *
     * @param string $bulan Bulan dalam format YYYY-MM
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show($bulan)
    {
        // Validasi format bulan
        if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) abort(404);

        $user = Auth::user();

        // Mengambil data absensi untuk bulan yang diminta
        $absensis = Absensi::where('user_id', $user->id)
            ->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$bulan])
            ->get();

        // Mengambil data izin yang diterima untuk bulan yang diminta
        $izins = Izin::where('user_id', $user->id)
            ->where('status', 'Terima')
            ->where(function ($query) use ($bulan) {
                $startOfMonth = Carbon::parse($bulan . '-01');
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                $query->whereBetween('tanggal_mulai', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('tanggal_selesai', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($q2) use ($startOfMonth, $endOfMonth) {
                        $q2->where('tanggal_mulai', '<=', $startOfMonth)
                            ->where('tanggal_selesai', '>=', $endOfMonth);
                    });
            })
            ->get();

        // Jika tidak ada data absensi maupun izin
        if ($absensis->isEmpty() && $izins->isEmpty()) {
            return back()->with('error', 'Data absensi atau izin bulan tersebut tidak tersedia.');
        }

        // Mengambil data gaji karyawan, atau gagal jika tidak ditemukan
        $gaji = GajiKaryawan::where('user_id', $user->id)->firstOrFail();

        // --- Perhitungan detail rekap (sama seperti di getSlipGajiModalContent) ---
        $jumlahHadir = $absensis->where('status', 'hadir')->count();
        $jumlahAlpa = $absensis->where('status', 'alpa')->count();
        $jumlahTerlambat = $absensis->where('status', 'hadir')
            ->filter(fn($absen) => $absen->keterangan === 'Terlambat')->count();

        $jumlahHariIzin = 0;
        foreach ($izins as $izin) {
            $startDate = Carbon::parse($izin->tanggal_mulai);
            $endDate = Carbon::parse($izin->tanggal_selesai);
            $startOfMonth = Carbon::parse($bulan . '-01');
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            $current = $startDate->copy()->max($startOfMonth);
            $end = $endDate->copy()->min($endOfMonth);

            while ($current->lte($end)) {
                if ($current->dayOfWeekIso < 6) { // Hanya hitung hari kerja (Senin-Jumat)
                    $jumlahHariIzin++;
                }
                $current->addDay();
            }
        }

        $totalLemburMenit = 0;
        foreach ($absensis as $absen) {
            if ($absen->lembur == 1 && $absen->jam_lembur && $absen->waktu_lembur_selesai) {
                try {
                    $start = Carbon::parse($absen->tanggal . ' ' . $absen->jam_lembur);
                    $end = Carbon::parse($absen->tanggal . ' ' . $absen->waktu_lembur_selesai);
                    if ($end->lt($start)) $end->addDay();
                    $totalLemburMenit += abs($end->diffInMinutes($start));
                } catch (\Exception $e) {
                    Log::error("Error calculating overtime in show method for user {$user->id}, absensi ID {$absen->id}: " . $e->getMessage());
                }
            }
        }

        $potonganAlpa = $gaji->gaji_pokok * ($jumlahAlpa * config('app.potongan_alpa', 3)) / 100;
        $potonganTerlambat = $gaji->gaji_pokok * ($jumlahTerlambat * config('app.potongan_terlambat', 1)) / 100;
        $bonusLembur = ($totalLemburMenit / 60) * config('app.bonus_lembur_per_jam', 10000);
        $totalGaji = $gaji->gaji_pokok + $gaji->tunjangan + $bonusLembur - $potonganAlpa - $potonganTerlambat;

        // Siapkan array rekapDetail untuk kompatibilitas dengan Blade
        $rekapDetail = [
            'alpa' => $jumlahAlpa,
            'terlambat' => $jumlahTerlambat,
            'izin' => $jumlahHariIzin,
            'jam_lembur' => $totalLemburMenit / 60,
            'absensis_raw' => $absensis,
            'izins_raw' => $izins,
        ];

        return view('karyawan.slip.detail', compact(
            'user', 'gaji', 'bulan',
            'potonganAlpa', 'potonganTerlambat', 'bonusLembur', 'totalGaji',
            'rekapDetail' // Kirim rekapDetail
        ));
    }

    /**
     * Mencetak slip gaji ke PDF untuk bulan tertentu.
     *
     * @param string $bulan Bulan dalam format YYYY-MM
     * @return \Illuminate\Http\Response
     */
    public function cetak($bulan)
    {
        // Validasi format bulan
        if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) abort(404);

        $user = Auth::user();

        // Mengambil data absensi untuk bulan yang diminta
        $absensis = Absensi::where('user_id', $user->id)
            ->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$bulan])
            ->get();

        // Mengambil data izin yang diterima untuk bulan yang diminta
        $izins = Izin::where('user_id', $user->id)
            ->where('status', 'Terima')
            ->where(function ($query) use ($bulan) {
                $startOfMonth = Carbon::parse($bulan . '-01');
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                $query->whereBetween('tanggal_mulai', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('tanggal_selesai', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($q2) use ($startOfMonth, $endOfMonth) {
                        $q2->where('tanggal_mulai', '<=', $startOfMonth)
                            ->where('tanggal_selesai', '>=', $endOfMonth);
                    });
            })
            ->get();

        // Jika tidak ada data absensi maupun izin
        if ($absensis->isEmpty() && $izins->isEmpty()) {
            return back()->with('error', 'Data absensi atau izin tidak ditemukan.');
        }

        // Mengambil data gaji karyawan, atau gagal jika tidak ditemukan
        $gaji = GajiKaryawan::where('user_id', $user->id)->firstOrFail();

        // --- Perhitungan detail rekap (sama seperti di getSlipGajiModalContent) ---
        $jumlahHadir = $absensis->where('status', 'hadir')->count();
        $jumlahAlpa = $absensis->where('status', 'alpa')->count();
        $jumlahTerlambat = $absensis->where('status', 'hadir')
            ->filter(fn($absen) => $absen->keterangan === 'Terlambat')->count();

        $jumlahHariIzin = 0;
        foreach ($izins as $izin) {
            $startDate = Carbon::parse($izin->tanggal_mulai);
            $endDate = Carbon::parse($izin->tanggal_selesai);
            $startOfMonth = Carbon::parse($bulan . '-01');
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            $current = $startDate->copy()->max($startOfMonth);
            $end = $endDate->copy()->min($endOfMonth);

            while ($current->lte($end)) {
                if ($current->dayOfWeekIso < 6) { // Hanya hitung hari kerja (Senin-Jumat)
                    $jumlahHariIzin++;
                }
                $current->addDay();
            }
        }

        $totalLemburMenit = 0;
        foreach ($absensis as $absen) {
            if ($absen->lembur == 1 && $absen->jam_lembur && $absen->waktu_lembur_selesai) {
                try {
                    $start = Carbon::parse($absen->tanggal . ' ' . $absen->jam_lembur);
                    $end = Carbon::parse($absen->tanggal . ' ' . $absen->waktu_lembur_selesai);
                    if ($end->lt($start)) $end->addDay();
                    $totalLemburMenit += abs($end->diffInMinutes($start));
                } catch (\Exception $e) {
                    Log::error("Error calculating overtime in cetak method for user {$user->id}, absensi ID {$absen->id}: " . $e->getMessage());
                }
            }
        }

        $potonganAlpa = $gaji->gaji_pokok * ($jumlahAlpa * config('app.potongan_alpa', 3)) / 100;
        $potonganTerlambat = $gaji->gaji_pokok * ($jumlahTerlambat * config('app.potongan_terlambat', 1)) / 100;
        $bonusLembur = ($totalLemburMenit / 60) * config('app.bonus_lembur_per_jam', 10000);
        $totalGaji = $gaji->gaji_pokok + $gaji->tunjangan + $bonusLembur - $potonganAlpa - $potonganTerlambat;

        // Siapkan array rekapDetail untuk kompatibilitas dengan Blade PDF
        $rekapDetail = [
'jumlahHadir' => $jumlahHadir,
            'alpa' => $jumlahAlpa,
            'terlambat' => $jumlahTerlambat,
            'izin' => $jumlahHariIzin,
            'jam_lembur' => $totalLemburMenit / 60,
            'absensis_raw' => $absensis,
            'izins_raw' => $izins,
        ];

        // Memuat view PDF dengan data yang sudah dihitung
        $pdf = Pdf::loadView('karyawan.slip.pdf', compact(
            'user', 'bulan', 'gaji',
            'potonganAlpa', 'potonganTerlambat', 'bonusLembur', 'totalGaji',
            'rekapDetail' // Kirim rekapDetail
        ))->setPaper('A4', 'portrait');

        // Mengunduh PDF
        return $pdf->download('slip_gaji_' . $user->name . '_' . $bulan . '.pdf');
    }
}

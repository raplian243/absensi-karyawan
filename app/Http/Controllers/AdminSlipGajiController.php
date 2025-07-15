<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\GajiKaryawan;
use App\Models\User;
use App\Models\Izin;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log; // Tetap aktifkan logging untuk debugging

class AdminSlipGajiController extends Controller
{
    /**
     * Menampilkan halaman rekap slip gaji bulanan karyawan.
     */
    public function index(Request $request)
    {
        try {
            $namaFilter = $request->input('nama');
            $bulanFilter = $request->input('bulan');

            // Mengambil daftar bulan unik dari absensi dan izin
            $bulanList = Absensi::selectRaw('DATE_FORMAT(tanggal, "%Y-%m") as bulan')
                ->distinct()
                ->union(Izin::where('status', 'Terima') // Hanya izin yang diterima
                    ->selectRaw('DATE_FORMAT(tanggal_mulai, "%Y-%m") as bulan')
                    ->distinct()
                )
                ->orderByDesc('bulan')
                ->pluck('bulan');

            // Mengambil data karyawan (users dengan role karyawan)
            $usersQuery = User::where('role', config('app.role_karyawan'));
            if ($namaFilter) {
                $usersQuery->where('name', 'like', '%' . $namaFilter . '%');
            }
            $users = $usersQuery->get();

            $rekapData = [];

            foreach ($users as $user) {
    foreach ($bulanList as $bulan) {
        $rekapData[$user->id][$bulan] = $this->calculateRekapDataForUserAndMonth(
            $user,
            $bulan
        );
    }
}

            
            Log::info("AdminSlipGajiController - Final Rekap Data (Index): " . json_encode($rekapData));

            return view('admin.slip.index', compact('users', 'bulanList', 'rekapData', 'bulanFilter', 'namaFilter'));
        } catch (\Exception $e) {
            Log::error("Error in AdminSlipGajiController@index: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat halaman rekap: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan detail slip gaji untuk bulan tertentu (full page view, tidak digunakan oleh modal).
     */
    public function show($userId, $bulan)
    {
        try {
            $user = User::findOrFail($userId);
            
            // Memanggil helper untuk menghitung semua data rekap
            $rekapDetail = $this->calculateRekapDataForUserAndMonth($user, $bulan);

            if (empty($rekapDetail['absensis_raw']) && empty($rekapDetail['izins_raw'])) {
                return back()->with('error', 'Data absensi atau izin tidak ditemukan untuk bulan ini.');
            }

            $gaji = GajiKaryawan::where('user_id', $userId)->first();
            if (!$gaji) {
                return back()->with('error', 'Data gaji untuk karyawan ini belum tersedia.');
            }

            // Hitung potongan dan bonus berdasarkan rekapDetail yang sudah ada
            $potonganAlpa = $gaji->gaji_pokok * ($rekapDetail['alpa'] * config('app.potongan_alpa', 3)) / 100;
            $potonganTerlambat = $gaji->gaji_pokok * ($rekapDetail['terlambat'] * config('app.potongan_terlambat', 1)) / 100;
            $bonusLembur = $rekapDetail['jam_lembur'] * config('app.bonus_lembur_per_jam', 10000);
            $totalGaji = $gaji->gaji_pokok + $gaji->tunjangan + $bonusLembur - $potonganAlpa - $potonganTerlambat;

            // Mengirim rekapDetail dan variabel lainnya secara eksplisit ke view
            return view('admin.slip.detail', compact(
                'user', 'bulan', 'gaji', 'potonganAlpa', 'potonganTerlambat', 'bonusLembur', 'totalGaji', 'rekapDetail'
            ));
        } catch (\Exception $e) {
            Log::error("Error in AdminSlipGajiController@show: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat detail slip gaji: ' . $e->getMessage());
        }
    }

    /**
     * Mengambil konten detail slip gaji untuk ditampilkan di modal.
     */
    public function getSlipGajiModalContent($userId, $bulan)
    {
        try {
            $user = User::findOrFail($userId);
            $rekapDetail = $this->calculateRekapDataForUserAndMonth($user, $bulan);

            if (empty($rekapDetail['absensis_raw']) && empty($rekapDetail['izins_raw'])) {
                 return '<p class="text-danger">Data absensi atau izin tidak ditemukan untuk bulan ini.</p>';
            }

            $gaji = GajiKaryawan::where('user_id', $userId)->first();
            if (!$gaji) {
                return '<p class="text-danger">Data gaji untuk karyawan ini belum tersedia.</p>';
            }

            $potonganAlpa = $gaji->gaji_pokok * ($rekapDetail['alpa'] * config('app.potongan_alpa', 3)) / 100;
            $potonganTerlambat = $gaji->gaji_pokok * ($rekapDetail['terlambat'] * config('app.potongan_terlambat', 1)) / 100;
            $bonusLembur = $rekapDetail['jam_lembur'] * config('app.bonus_lembur_per_jam', 10000);
            $totalGaji = $gaji->gaji_pokok + $gaji->tunjangan + $bonusLembur - $potonganAlpa - $potonganTerlambat;

            // Render view khusus modal tanpa layout utama
            return view('admin.slip.modal_detail_content', compact(
                'user', 'bulan', 'gaji', 'potonganAlpa', 'potonganTerlambat', 'bonusLembur', 'totalGaji', 'rekapDetail'
            ));
        } catch (\Exception $e) {
            Log::error("Error in AdminSlipGajiController@getSlipGajiModalContent for user {$userId}, bulan {$bulan}: " . $e->getMessage());
            // Mengembalikan pesan error ke frontend yang akan ditampilkan di modal
            return '<p class="text-danger">Terjadi kesalahan server: ' . $e->getMessage() . '</p>';
        }
    }

    /**
     * Mencetak slip gaji ke PDF untuk bulan tertentu.
     */
    public function cetak(User $user, $bulan)
    {
        try {
            $rekapDetail = $this->calculateRekapDataForUserAndMonth($user, $bulan);

            if (empty($rekapDetail['absensis_raw']) && empty($rekapDetail['izins_raw'])) {
                return back()->with('error', 'Data absensi atau izin tidak ditemukan.');
            }

            $gaji = GajiKaryawan::where('user_id', $user->id)->first();
            if (!$gaji) {
                return back()->with('error', 'Data gaji untuk karyawan ini belum tersedia.');
            }

            $potonganAlpa = $gaji->gaji_pokok * ($rekapDetail['alpa'] * config('app.potongan_alpa', 3)) / 100;
            $potonganTerlambat = $gaji->gaji_pokok * ($rekapDetail['terlambat'] * config('app.potongan_terlambat', 1)) / 100;
            $bonusLembur = $rekapDetail['jam_lembur'] * config('app.bonus_lembur_per_jam', 10000);
            $totalGaji = $gaji->gaji_pokok + $gaji->tunjangan + $bonusLembur - $potonganAlpa - $potonganTerlambat;

            $pdf = Pdf::loadView('admin.slip.pdf', compact(
                'user', 'bulan', 'gaji', 'potonganAlpa', 'potonganTerlambat', 'bonusLembur', 'totalGaji', 'rekapDetail'
            ))->setPaper('A4', 'portrait');

            return $pdf->download('slip_gaji_' . $user->name . '_' . $bulan . '.pdf');
        } catch (\Exception $e) {
            Log::error("Error in AdminSlipGajiController@cetak for user {$user->id}, bulan {$bulan}: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mencetak slip gaji: ' . $e->getMessage());
        }
    }

    /**
     * Helper function untuk menghitung rekap data absensi dan izin untuk user dan bulan tertentu.
     */
    private function calculateRekapDataForUserAndMonth(User $user, ?string $bulan): array
    {
        if (empty($bulan)) {
            return $this->getDefaultRekapData(null);
        }

        $absensis = Absensi::where('user_id', $user->id)
            ->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$bulan])
            ->get();

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

        $tepatWaktu = $absensis->where('status', 'hadir')->where('keterangan', '!=', 'Terlambat')->count();
        $terlambat = $absensis->where('status', 'hadir')->where('keterangan', 'Terlambat')->count();
        $alpa = $absensis->where('status', 'alpa')->count();
        $jumlahHadir = $tepatWaktu + $terlambat;

        $jumlahHariIzin = 0;
        foreach ($izins as $izin) {
            $startDate = Carbon::parse($izin->tanggal_mulai);
            $endDate = Carbon::parse($izin->tanggal_selesai);
            
            $startOfMonth = Carbon::parse($bulan . '-01');
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            
            $current = $startDate->copy()->max($startOfMonth);
            $end = $endDate->copy()->min($endOfMonth);

            while ($current->lte($end)) {
                if ($current->dayOfWeekIso < 6) { // 6 = Sabtu, 7 = Minggu
                    $jumlahHariIzin++;
                }
                $current->addDay();
            }
        }

        $totalLemburMenit = 0;
        foreach ($absensis as $absen) {
            if ($absen->lembur == 1 && $absen->jam_lembur && $absen->waktu_lembur_selesai) {
                try {
                    $startLembur = Carbon::parse($absen->tanggal . ' ' . $absen->jam_lembur);
                    $endLembur = Carbon::parse($absen->tanggal . ' ' . $absen->waktu_lembur_selesai);

                    if ($endLembur->lt($startLembur)) {
                        $endLembur->addDay();
                    }
                    
                    $durasiMenit = abs($endLembur->diffInMinutes($startLembur));
                    $totalLemburMenit += $durasiMenit;
                    
                    Log::info("[Lembur Calculation Detailed] User ID: {$user->id}, Absen ID: {$absen->id}, Date: {$absen->tanggal}, Start: {$absen->jam_lembur}, End: {$absen->waktu_lembur_selesai}, Duration (min): {$durasiMenit}, Total Minutes: {$totalLemburMenit}");

                } catch (\Exception | \TypeError $e) {
                    Log::error("[Lembur Error Detailed] User ID: {$user->id}, Absen ID {$absen->id}: " . $e->getMessage());
                }
            }
        }
        $jumlahJamLembur = $totalLemburMenit / 60;

        return [
            'jumlahHadir' => $jumlahHadir,
            'tepat_waktu' => $tepatWaktu, 
            'terlambat' => $terlambat, 
            'alpa' => $alpa,
            'izin' => $jumlahHariIzin,
            'jam_lembur' => $jumlahJamLembur,
            'bulan_rekap' => $bulan,
            'absensis_raw' => $absensis,
            'izins_raw' => $izins,
        ];
    }

    /**
     * Helper function untuk default rekap data.
     */
    private function getDefaultRekapData(?string $bulan): array
    {
        return [
            'jumlahHadir' => 0,
            'tepat_waktu' => 0,
            'terlambat' => 0,
            'alpa' => 0,
            'izin' => 0,
            'jam_lembur' => 0,
            'bulan_rekap' => $bulan,
            'absensis_raw' => collect(),
            'izins_raw' => collect(),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Izin;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        // Jalankan pengecekan otomatis untuk karyawan yang tidak absen hari ini
        $this->cekAlpaOtomatis();
// Perbaiki data alpa yang seharusnya izin
Absensi::where('status', 'alpa')
    ->whereDate('tanggal', now()->toDateString())
    ->get()
    ->each(function ($absen) {
        $izinAda = Izin::where('user_id', $absen->user_id)
            ->where('status', 'Terima')
            ->whereDate('tanggal_mulai', '<=', $absen->tanggal)
            ->whereDate('tanggal_selesai', '>=', $absen->tanggal)
            ->exists();

        if ($izinAda) {
            $absen->delete(); // Hapus data alpa yang salah
        }
    });



        // --- Query untuk Absensi ---
        $absensiQuery = Absensi::with('user')
            ->whereHas('user', function ($q) {
                $q->where('role', 'karyawan');
            });

        // Filter berdasarkan nama karyawan pada absensi
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $absensiQuery->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter berdasarkan tanggal pada absensi
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $absensiQuery->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $absensiQuery->whereDate('tanggal', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $absensiQuery->whereDate('tanggal', '<=', $request->end_date);
        }

        // --- Filter Lembur pada Absensi ---
        if ($request->filled('lembur')) {
            if ($request->lembur == 'ya') {
                $absensiQuery->where('lembur', 1)
                             ->whereNotNull('jam_lembur')
                             ->whereNotNull('waktu_lembur_selesai');
            } elseif ($request->lembur == 'tidak') {
                // 'Tidak' lembur berarti flag 0, ATAU flag 1 tapi jam lembur/selesai kosong
                $absensiQuery->where(function ($q) {
                    $q->where('lembur', 0)
                      ->orWhereNull('jam_lembur')
                      ->orWhereNull('waktu_lembur_selesai');
                });
            }
        }

        // --- Filter Status pada Absensi ---
        // Jika filter status diatur dan BUKAN 'izin', terapkan pada absensi
        if ($request->filled('status') && $request->status !== 'izin') {
            $absensiQuery->where('status', $request->status);
        } elseif ($request->filled('status') && $request->status === 'izin') {
            // Jika filter status adalah 'izin', kita tidak ingin menampilkan absensi
            // karena data izin akan datang dari tabel izin.
            $absensiQuery->where('id', null); // Paksa absensiQuery mengembalikan hasil kosong
        }
        // Jika status filter kosong, semua status absensi (hadir, alpa) akan diambil.

        // Urutkan data absensi
        $absensis = $absensiQuery->orderByDesc('tanggal')->get();

        // Hitung durasi lembur dan format untuk setiap record absensi
        foreach ($absensis as $absen) {
            $absen->durasi_lembur_formatted = '-';
            if ($absen->lembur == 1 && $absen->jam_lembur && $absen->waktu_lembur_selesai) {
                try {
                    $startLembur = Carbon::parse($absen->tanggal . ' ' . $absen->jam_lembur);
                    $endLembur = Carbon::parse($absen->tanggal . ' ' . $absen->waktu_lembur_selesai);

                    if ($endLembur->lt($startLembur)) {
                        $endLembur->addDay();
                    }
                    
                    $durasiMenit = abs($endLembur->diffInMinutes($startLembur));
                    
                    $hours = floor($durasiMenit / 60);
                    $minutes = $durasiMenit % 60;
                    
                    if ($hours > 0 || $minutes > 0) {
                        $absen->durasi_lembur_formatted = $hours . ' jam ' . $minutes . ' menit';
                    } else {
                        $absen->durasi_lembur_formatted = '0 jam 0 menit';
                    }
                    
                } catch (\Exception | \TypeError $e) {
                    $absen->durasi_lembur_formatted = '-';
                }
            }
        }

        // --- Query untuk Izin (untuk tabel utama) ---
        $izinListForMainTableQuery = Izin::with('user');

        // Filter berdasarkan nama karyawan pada izin (tabel utama)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $izinListForMainTableQuery->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter berdasarkan tanggal pada izin (tabel utama)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $izinListForMainTableQuery->where(function($q) use ($request) {
                $q->whereBetween('tanggal_mulai', [$request->start_date, $request->end_date])
                  ->orWhereBetween('tanggal_selesai', [$request->start_date, $request->end_date])
                  ->orWhere(function($q) use ($request) {
                      $q->where('tanggal_mulai', '<=', $request->start_date)
                        ->where('tanggal_selesai', '>=', $request->end_date);
                  });
            });
        } elseif ($request->filled('start_date')) {
            $izinListForMainTableQuery->where('tanggal_selesai', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $izinListForMainTableQuery->where('tanggal_mulai', '<=', $request->end_date);
        }
        
        // --- Filter Status pada Izin (untuk tabel utama) ---
        if ($request->filled('status')) {
            if ($request->status == 'izin') {
                 // Jika filter status 'izin' dipilih, tampilkan SEMUA status izin di tabel utama (Tunggu, Terima, Tolak)
                 $izinListForMainTableQuery->whereIn('status', ['Tunggu', 'Terima', 'Tolak']);
            } else {
                // Jika status lain dipilih, kita tidak ingin menampilkan izin di tabel utama
                $izinListForMainTableQuery->where('id', null); // Paksa izinListForMainTableQuery mengembalikan hasil kosong
            }
        } else {
             // Jika tidak ada filter status, tampilkan hanya izin yang disetujui (default tabel utama)
             $izinListForMainTableQuery->where('status', 'Terima');
        }

        // --- PENTING: Jika filter LEMBUR 'ya' aktif, JANGAN tampilkan data izin di tabel utama ---
        if ($request->filled('lembur') && $request->lembur == 'ya') {
            $izinListForMainTableQuery->where('id', null); // Paksa query izin mengembalikan hasil kosong
        }

        $izinListForMainTable = $izinListForMainTableQuery->orderBy('created_at', 'desc')->get();

        // --- Data Izin untuk MODAL KELOLA IZIN (modalIzinList) ---
        // Ini adalah daftar izin yang akan muncul di modal, tanpa filter status yang ketat dari dashboard utama.
        // Modal ini adalah tempat untuk mengelola SEMUA status izin.
        $modalIzinList = Izin::with('user')->orderBy('created_at', 'desc')->get();
        
        // Hitung jumlah izin menunggu untuk notifikasi modal (menggunakan $modalIzinList)
        $izinMenungguCount = $modalIzinList->where('status', 'Tunggu')->count();

        // Kirim semua data yang diperlukan ke view
        return view('admin.dashboard', compact('absensis', 'izinListForMainTable', 'modalIzinList', 'izinMenungguCount'));
    }

    public function izinUpdate(Request $request, $id)
    {
        $izin = Izin::findOrFail($id);
        $izin->status = $request->status;
        $izin->save();

        return back()->with('success', 'Status izin berhasil diperbarui.');
    }

    private function cekAlpaOtomatis()
    {
        $tanggalHariIni = Carbon::now()->toDateString();
        $hariIni = Carbon::parse($tanggalHariIni)->dayOfWeekIso; // 6 = Sabtu, 7 = Minggu

        // Lewati Sabtu dan Minggu
        if ($hariIni >= 6) {
            return;
        }

        $jamSekarang = Carbon::now()->format('H:i');
        $jamPulang = env('BATAS_ABSEN_PULANG', '17:00');

        if (!preg_match('/^\d{2}:\d{2}$/', $jamPulang)) {
            $jamPulang = '17:00';
        }

        $cacheKey = 'alpa_checked_' . $tanggalHariIni;

        if ($jamSekarang >= $jamPulang && !Cache::has($cacheKey)) {
            $userIds = User::where('role', env('ROLE_KARYAWAN', 'karyawan'))->pluck('id');

            foreach ($userIds as $userId) {
                $sudahAbsen = Absensi::where('user_id', $userId)
                    ->whereDate('tanggal', $tanggalHariIni)
                    ->exists();

                // Periksa apakah ada izin yang disetujui untuk hari ini
                $sudahIzin = Izin::where('user_id', $userId)
                    ->where('status', 'Terima') // Hanya izin yang sudah diterima
                    ->whereDate('tanggal_mulai', '<=', $tanggalHariIni)
                    ->whereDate('tanggal_selesai', '>=', $tanggalHariIni)
                    ->exists();

                if (!$sudahAbsen && !$sudahIzin) {
                    Absensi::create([
                        'user_id' => $userId,
                        'tanggal' => $tanggalHariIni,
                        'status' => 'alpa',
                        'keterangan' => '-'
                    ]);
                }
            }

            Cache::put($cacheKey, true, now()->addDay());
        }
    }
    
    public function exportExcel(Request $request)
    {
        // Query data yang sama seperti di dashboard method
        $absensiQuery = Absensi::with('user')
            ->whereHas('user', function ($q) {
                $q->where('role', 'karyawan');
            });

        // Apply filters (copy-paste dari dashboard method)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $absensiQuery->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%');
            });
        }
        
        // --- Filter Status Absensi untuk Export ---
        if ($request->filled('status')) {
            if ($request->status !== 'izin') {
                $absensiQuery->where('status', $request->status);
            } else {
                $absensiQuery->where('id', null); // Paksa kosong jika filter 'izin'
            }
        }
        
        if ($request->filled('start_date')) {
            $absensiQuery->where('tanggal', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $absensiQuery->where('tanggal', '<=', $request->end_date);
        }
        
        // --- Filter Lembur untuk ExportExcel ---
        if ($request->filled('lembur')) {
            if ($request->lembur == 'ya') {
                $absensiQuery->where('lembur', 1)
                             ->whereNotNull('jam_lembur')
                             ->whereNotNull('waktu_lembur_selesai');
            } elseif ($request->lembur == 'tidak') {
                $absensiQuery->where(function ($q) {
                    $q->where('lembur', 0)
                      ->orWhereNull('jam_lembur')
                      ->orWhereNull('waktu_lembur_selesai');
                });
            }
        }
        
        $absensis = $absensiQuery->orderByDesc('tanggal')->get();

        // Format durasi lembur untuk export
        foreach ($absensis as $absen) {
            $absen->durasi_lembur_formatted = '-';
            if ($absen->lembur == 1 && $absen->jam_lembur && $absen->waktu_lembur_selesai) {
                try {
                    $startLembur = Carbon::parse($absen->tanggal . ' ' . $absen->jam_lembur);
                    $endLembur = Carbon::parse($absen->tanggal . ' ' . $absen->waktu_lembur_selesai);

                    if ($endLembur->lt($startLembur)) {
                        $endLembur->addDay();
                    }
                    $durasiMenit = abs($endLembur->diffInMinutes($startLembur));
                    $hours = floor($durasiMenit / 60);
                    $minutes = $durasiMenit % 60;
                    if ($hours > 0 || $minutes > 0) {
                        $absen->durasi_lembur_formatted = $hours . ' jam ' . $minutes . ' menit';
                    } else {
                        $absen->durasi_lembur_formatted = '0 jam 0 menit';
                    }
                } catch (\Exception | \TypeError $e) {
                    $absen->durasi_lembur_formatted = '-';
                }
            }
        }

        // Query untuk Izin (untuk export)
        $izinListQuery = Izin::with('user');
        
        // Filter nama karyawan untuk izin
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $izinListQuery->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter tanggal untuk Izin
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $izinListQuery->where(function($q) use ($request) {
                $q->whereBetween('tanggal_mulai', [$request->start_date, $request->end_date])
                  ->orWhereBetween('tanggal_selesai', [$request->start_date, $request->end_date])
                  ->orWhere(function($q) use ($request) {
                      $q->where('tanggal_mulai', '<=', $request->start_date)
                        ->where('tanggal_selesai', '>=', $request->end_date);
                  });
            });
        } elseif ($request->filled('start_date')) {
            $izinListQuery->where('tanggal_selesai', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $izinListQuery->where('tanggal_mulai', '<=', $request->end_date);
        }
        
        // --- Filter Status untuk Izin Export ---
        if ($request->filled('status')) {
            if ($request->status == 'izin') {
                // Jika filter status 'izin' dipilih, ambil semua status izin untuk export
                $izinListQuery->whereIn('status', ['Tunggu', 'Terima', 'Tolak']);
            } else {
                // Jika status lain dipilih, jangan sertakan izin dalam export
                $izinListQuery->where('id', null); 
            }
        } else {
            // Jika tidak ada filter status, ambil semua status izin untuk export
            $izinListQuery->whereIn('status', ['Tunggu', 'Terima', 'Tolak']);
        }

        // --- PENTING: Jika filter LEMBUR 'ya' aktif, JANGAN tampilkan data izin di export ---
        if ($request->filled('lembur') && $request->lembur == 'ya') {
            $izinListQuery->where('id', null); // Paksa query izin mengembalikan hasil kosong
        }

        $izinList = $izinListQuery->orderByDesc('created_at')->get();

        return "Export data will be processed here (Absensi count: {$absensis->count()}, Izin count: {$izinList->count()})";
    }
}

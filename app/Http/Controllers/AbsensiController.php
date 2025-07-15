<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Absensi;
use App\Models\Izin;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    // Tampilkan dashboard karyawan
    public function dashboardKaryawan()
    {
        $user = Auth::user();
        $userId = $user->id;

        $absenList = Absensi::where('user_id', $userId)
            ->orderByDesc('tanggal')
            ->get();

        $izinList = Izin::where('user_id', $userId)
            ->orderByDesc('tanggal_mulai')
            ->get();

        $absenHariIni = Absensi::where('user_id', $userId)
            ->whereDate('tanggal', today())
            ->first();

// Tambahkan di controller sebelum return view
$hariIni = Carbon::today()->toDateString();
$jamSekarang = Carbon::now()->format('H:i');
$jamPulang = '17:00';

$absensiHariIni = Absensi::where('user_id', auth()->id())
    ->whereDate('tanggal', $hariIni)
    ->first();

        return view('karyawan.absensi', compact('absenList', 'izinList', 'absenHariIni'));
    }

    // Absen Masuk
    public function store(Request $request)
    {
        if (now()->isWeekend()) {
            return back()->with('error', 'Hari ini adalah hari libur (Sabtu/Minggu), absensi tidak tersedia.');
        }

        $request->validate([
            'lokasi_masuk' => 'required|string|max:255',
            'foto_masuk' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        $user = auth()->user();
        $existing = Absensi::where('user_id', $user->id)
            ->whereDate('tanggal', today())
            ->first();

        if ($existing) {
            return back()->with('error', 'Kamu sudah melakukan absen masuk hari ini.');
        }

        $fotoPath = $request->file('foto_masuk')->store('uploads/foto_masuk', 'public');

        Absensi::create([
            'user_id' => $user->id,
            'tanggal' => today(),
            'jam_masuk' => now()->format('H:i:s'),
            'lokasi_masuk' => $request->lokasi_masuk,
            'foto_masuk' => $fotoPath,
            'status' => 'hadir',
            'keterangan' => now()->gt(now()->setTime(8, 0)) ? 'Terlambat' : 'Tepat Waktu',
        ]);

        return back()->with('absen_success', 'Absen masuk berhasil!');
    }

    // Absen Pulang
    public function pulang(Request $request)
    {
        $request->validate([
            'lokasi_pulang' => 'required|string|max:255',
            'foto_pulang' => 'required|image|mimes:jpeg,jpg,png|max:2048',
            'jam_lembur' => 'nullable|date_format:H:i',
        ]);

        $user = auth()->user();
        $absen = Absensi::where('user_id', $user->id)
            ->whereDate('tanggal', today())
            ->first();

        if (!$absen) {
            return back()->with('error', 'Kamu belum absen masuk hari ini.');
        }

        if ($absen->jam_pulang) {
            return back()->with('error', 'Kamu sudah melakukan absen pulang.');
        }

        $fotoPulangPath = $request->file('foto_pulang')->store('uploads/foto_pulang', 'public');

        $dataUpdate = [
            'jam_pulang' => now()->format('H:i:s'),
            'lokasi_pulang' => $request->lokasi_pulang,
            'foto_pulang' => $fotoPulangPath,
        ];

        // Hitung lembur jika jam_lembur diinput
        if ($request->jam_lembur) {
            try {
                $mulai = Carbon::createFromFormat('H:i', $request->jam_lembur);
                $selesai = now();
                $durasiMenit = $mulai->diffInMinutes($selesai);
                $jam = floor($durasiMenit / 60);
                $menit = $durasiMenit % 60;

                $dataUpdate['lembur'] = true;
                $dataUpdate['jam_lembur'] = $mulai->format('H:i:s');
                $dataUpdate['waktu_lembur_selesai'] = $selesai->format('H:i:s');
                $dataUpdate['durasi_lembur'] = trim(($jam ? $jam . ' jam' : '') . ($menit ? ' ' . $menit . ' menit' : ''));
            } catch (\Exception $e) {
                // Jika parsing jam lembur gagal
                $dataUpdate['lembur'] = false;
                $dataUpdate['jam_lembur'] = null;
                $dataUpdate['waktu_lembur_selesai'] = null;
                $dataUpdate['durasi_lembur'] = null;
            }
        } else {
            // Bukan lembur
            $dataUpdate['lembur'] = false;
            $dataUpdate['jam_lembur'] = null;
            $dataUpdate['waktu_lembur_selesai'] = null;
            $dataUpdate['durasi_lembur'] = null;
        }

        $absen->update($dataUpdate);
        return back()->with('absen_success', 'Absen pulang berhasil!');
    }

    // Absen Lembur (tanpa absen masuk)
    public function lembur(Request $request)
{
    $request->validate([
        'lokasi_pulang' => 'required|string|max:255',
        'foto_lembur' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        'jam_lembur_weekend' => 'required|date_format:H:i',
        'waktu_lembur_selesai' => 'required|date_format:H:i|after:jam_lembur_weekend',
    ]);

    $user = auth()->user();
    $absen = Absensi::where('user_id', $user->id)
        ->whereDate('tanggal', today())
        ->first();

    if (!$absen) {
        $absen = Absensi::create([
            'user_id' => $user->id,
            'tanggal' => today(),
            'status' => 'hadir',
            'keterangan' => 'Lembur',
        ]);
    }

    $fotoPath = $request->file('foto_lembur')->store('uploads/foto_lembur', 'public');

    try {
        $mulai = Carbon::createFromFormat('H:i', $request->jam_lembur_weekend);
        $selesai = Carbon::createFromFormat('H:i', $request->waktu_lembur_selesai);

        $durasiMenit = $mulai->diffInMinutes($selesai);
        $jam = floor($durasiMenit / 60);
        $menit = $durasiMenit % 60;
        $durasiFormatted = trim(($jam ? $jam . ' jam' : '') . ($menit ? ' ' . $menit . ' menit' : ''));

        $absen->update([
            'lembur' => true,
            'jam_lembur' => $mulai->format('H:i:s'),
            'waktu_lembur_selesai' => $selesai->format('H:i:s'),
            'durasi_lembur' => $durasiFormatted,
            'lokasi_pulang' => $request->lokasi_pulang,
            'foto_pulang' => $fotoPath,
        ]);

    } catch (\Exception $e) {
        dd('Gagal parsing waktu:', $e->getMessage());
    }

    return back()->with('absen_success', 'Absen lembur berhasil!');
}
}

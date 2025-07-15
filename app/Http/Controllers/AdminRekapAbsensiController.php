<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;

class AdminRekapAbsensiController extends Controller
{
    public function index()
    {
        // Ambil bulan yang ada datanya
        $bulanList = Absensi::selectRaw('DATE_FORMAT(tanggal, "%Y-%m") as bulan')
            ->distinct()
            ->orderByDesc('bulan')
            ->pluck('bulan');

        return view('admin.rekap.index', compact('bulanList'));
    }

    public function show($bulan)
    {
        $users = User::all();
        $data = [];

        foreach ($users as $user) {
            $absensis = Absensi::where('user_id', $user->id)
                ->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$bulan])
                ->get();

            $hadir = $absensis->where('status', 'hadir')->count();
            $alpa = $absensis->where('status', 'alpa')->count();
            $terlambat = $absensis->where('status', 'hadir')
                ->filter(fn($a) => $a->keterangan === 'Terlambat')->count();
            $lembur = $absensis->where('lembur', 1)->sum(function ($a) {
                if ($a->jam_pulang && $a->waktu_lembur_selesai) {
                    return Carbon::parse($a->jam_pulang)->diffInHours(Carbon::parse($a->waktu_lembur_selesai));
                }
                return 0;
            });

            $data[] = [
                'user' => $user,
                'hadir' => $hadir,
                'alpa' => $alpa,
                'terlambat' => $terlambat,
                'lembur_jam' => $lembur,
            ];
        }

        return view('admin.rekap.show', compact('data', 'bulan'));
    }
}

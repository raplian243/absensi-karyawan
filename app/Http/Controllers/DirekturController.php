<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\Izin;
use App\Models\User;
use App\Models\GajiKaryawan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DirekturController extends Controller
{
    public function index(Request $request)
    {
$bukaGajiModal = $request->input('showGajiModal') === 'true';
$bukaModal = $request->input('showModal') === 'true';
$searchBulan = $request->input('bulan') ?? Carbon::now()->format('Y-m');
$currentMonth = Carbon::parse($searchBulan)->translatedFormat('F Y'); // Untuk ditampilkan di view

$izinListForMainTable = Izin::with('user')->where('status', 'Terima')->get();
$riwayatIzinTanggal = collect();

foreach ($izinListForMainTable as $izin) {
    $start = Carbon::parse($izin->tanggal_mulai);
    $end = Carbon::parse($izin->tanggal_selesai);

    while ($start->lte($end)) {
        if ($start->isWeekday()) { // Hanya Senin - Jumat
            $riwayatIzinTanggal->push([
                'nama' => $izin->user->name,
                'tanggal' => $start->format('Y-m-d'),
                'keterangan' => $izin->keterangan,
                'status' => $izin->status
            ]);
        }
        $start->addDay();
    }
}

        // --- Filter Input (jika ada) ---
        $searchNama = $request->input('nama');

        // --- Ambil Semua Karyawan ---
        $karyawanList = User::where('role', 'karyawan')->get();

        // --- Rekap Absensi per Karyawan (untuk grafik dan tab rekap) ---
        $rekapPerKaryawan = $karyawanList->map(function ($karyawan) use ($searchBulan) {

            // --- Query Absensi ---
            $queryAbsensi = Absensi::where('user_id', $karyawan->id);
            if ($searchBulan) {
                $queryAbsensi->whereYear('tanggal', '=', Carbon::parse($searchBulan)->year)
                             ->whereMonth('tanggal', '=', Carbon::parse($searchBulan)->month);
            }
            $absensi = $queryAbsensi->get();

            // --- Query dan Perhitungan Izin (Logika Diperbaiki) ---
            $queryIzin = Izin::where('user_id', $karyawan->id)
                ->where('status', 'Terima'); // PERBAIKAN: Filter hanya izin yang statusnya 'Terima'

            if ($searchBulan) {
                $bulanCarbon = Carbon::parse($searchBulan);
                // PERBAIKAN: Logika query tanggal yang lebih akurat dari AdminController
                $queryIzin->where(function ($query) use ($bulanCarbon) {
                    $startOfMonth = $bulanCarbon->copy()->startOfMonth();
                    $endOfMonth = $bulanCarbon->copy()->endOfMonth();

                    $query->whereBetween('tanggal_mulai', [$startOfMonth, $endOfMonth])
                          ->orWhereBetween('tanggal_selesai', [$startOfMonth, $endOfMonth])
                          ->orWhere(function ($q2) use ($startOfMonth, $endOfMonth) {
                              $q2->where('tanggal_mulai', '<', $startOfMonth)
                                 ->where('tanggal_selesai', '>', $endOfMonth);
                          });
                });
            }
            $izin = $queryIzin->get();

            // PERBAIKAN: Logika perhitungan hari izin yang akurat (hanya hari kerja & dalam bulan terkait)
            $jumlahHariIzin = 0;
            if ($searchBulan) {
                foreach ($izin as $itemIzin) {
                    $startDate = Carbon::parse($itemIzin->tanggal_mulai);
                    $endDate = Carbon::parse($itemIzin->tanggal_selesai);
                    
                    $startOfMonth = Carbon::parse($searchBulan)->startOfMonth();
                    $endOfMonth = Carbon::parse($searchBulan)->endOfMonth();
                    
                    $current = $startDate->isAfter($startOfMonth) ? $startDate : $startOfMonth;
                    $end = $endDate->isBefore($endOfMonth) ? $endDate : $endOfMonth;

                    while ($current->lte($end)) {
                        if ($current->isWeekday()) { // Hanya hitung Senin-Jumat
                            $jumlahHariIzin++;
                        }
                        $current->addDay();
                    }
                }
            }


            // --- Perhitungan Status Kehadiran (Logika Diperbaiki) ---
            // PERBAIKAN: Gunakan kolom 'keterangan' untuk menghitung tepat waktu dan terlambat
            $tepatWaktu = $absensi->where('status', 'hadir')->where('keterangan', '!=', 'Terlambat')->count();
            $terlambat = $absensi->where('status', 'hadir')->where('keterangan', 'Terlambat')->count();
            $alpa = $absensi->where('status', 'alpa')->count();

            // --- Perhitungan Lembur (Logika Diperbaiki) ---
            $totalLemburMenit = $absensi->filter(function ($a) {
                return $a->lembur && $a->jam_lembur && $a->waktu_lembur_selesai;
            })->sum(function ($a) {
                $start = Carbon::parse($a->tanggal . ' ' . $a->jam_lembur);
                $end = Carbon::parse($a->tanggal . ' ' . $a->waktu_lembur_selesai);

                if ($end->lt($start)) { // Jika lembur lewat tengah malam
                    $end->addDay();
                }
                
                return $start->diffInMinutes($end);
            });

            return [
                'nama' => $karyawan->name,
                'bulan_rekap' => $searchBulan ?? Carbon::now()->format('Y-m'),
                'tepat_waktu' => $tepatWaktu,
                'terlambat' => $terlambat,
                'izin' => $jumlahHariIzin, // Menggunakan variabel hasil perhitungan baru
                'alpa' => $alpa,
                'total_lembur' => $totalLemburMenit,
                // PERBAIKAN: Format waktu lembur yang lebih aman menggunakan abs()
                'total_lembur_formatted' => floor(abs($totalLemburMenit) / 60) . ' jam ' . (abs($totalLemburMenit) % 60) . ' menit',
                'jam_lembur_decimal' => round($totalLemburMenit / 60, 2)
            ];
        });

        // --- Total untuk Grafik Utama ---
        $totalTepatWaktu = $rekapPerKaryawan->sum('tepat_waktu');
        $totalTerlambat = $rekapPerKaryawan->sum('terlambat');
        $totalIzin = $rekapPerKaryawan->sum('izin');
        $totalAlpa = $rekapPerKaryawan->sum('alpa');

        // --- Ambil Data GajiKaryawan ---
        $gajiList = GajiKaryawan::with('user')->get();

        // --- Buat Data Slip Gaji Berdasarkan rekapPerKaryawan ---
        $slipList = $rekapPerKaryawan->map(function ($rekap) {
            return [
                'name' => $rekap['nama'],
                'bulan' => $rekap['bulan_rekap'],
                'terlambat' => $rekap['terlambat'],
                'alpa' => $rekap['alpa'],
                'jam_lembur' => $rekap['jam_lembur_decimal'],
            ];
        });

        // --- Ambil Data Absensi & Izin Lengkap untuk Modal Riwayat ---
        $absensiList = Absensi::with('user')->get();

        return view('direktur.dashboard', compact(
            'rekapPerKaryawan',
            'totalTepatWaktu',
            'totalTerlambat',
            'totalIzin',
            'totalAlpa',
            'gajiList',
            'slipList',
            'absensiList',
            'izinListForMainTable',
            'searchNama',
            'searchBulan',
'bukaGajiModal',
'bukaModal',
'riwayatIzinTanggal',
'currentMonth'
        ));
    }
}
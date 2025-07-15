<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Absensi;
use App\Models\Izin; // Impor model Izin

class KalenderController extends Controller
{
    /**
     * Menampilkan halaman kalender kehadiran karyawan.
     */
    public function index()
    {
        $userId = auth()->id();

        // 1. Ambil data absensi
        $absensis = Absensi::where('user_id', $userId)->get();

        // 2. Ambil data izin yang statusnya 'Terima'
        $izins = Izin::where('user_id', $userId)
                        ->where('status', 'Terima') // Hanya izin yang disetujui
                        ->get();

        $statusPerDate = []; // Untuk warna kalender (berdasarkan 'type')
        $absenPerTanggal = []; // Untuk detail pop-up

        // Array sementara untuk menyimpan semua event per tanggal
        // Dengan prioritas: Izin > Alpa > Hadir (Termasuk Terlambat)
        $eventsByDate = [];

        // Proses data Izin terlebih dahulu (Prioritas tertinggi)
        foreach ($izins as $izin) {
            $startDate = Carbon::parse($izin->tanggal_mulai);
            $endDate = Carbon::parse($izin->tanggal_selesai);

            // Iterasi setiap hari dalam rentang izin
            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                $tanggal = $date->format('Y-m-d');

                // Izin memiliki prioritas tertinggi
                $eventsByDate[$tanggal] = [
                    'type'          => 'izin', // Untuk CSS class
                    'status'        => 'Izin', // Untuk tampilan di modal
                    'keterangan'    => $izin->alasan ?? 'Izin / Cuti',
                    'jam_masuk'     => '-',
                    'jam_pulang'    => '-',
                    'lembur'        => '-',
                    'durasi_lembur' => '-',
                    'source'        => 'izin_model'
                ];
            }
        }

        // Proses data Absensi (Prioritas lebih rendah dari Izin)
        foreach ($absensis as $absen) {
            $tanggal = Carbon::parse($absen->tanggal)->format('Y-m-d');

            // Hanya proses absensi jika tanggal tersebut belum ditandai sebagai 'izin'
            if (!isset($eventsByDate[$tanggal])) {
                $keterangan = $absen->keterangan ?? '-';
                $visualType = 'hadir'; // Default untuk dot warna
                $displayStatus = 'Hadir'; // Default untuk teks status di modal

                if ($absen->status === 'alpa') {
                    $visualType = 'alpa';
                    $displayStatus = 'Alpa';
                } elseif ($absen->status === 'hadir') {
                    // Jika hadir dan keterangan terlambat, ubah tipe visualnya
                    if ($keterangan === 'Terlambat') {
                        $visualType = 'terlambat'; // Dot warna menjadi kuning
                    }
                    // displayStatus tetap 'Hadir'
                }

                // Ambil detail lembur (dari absensi)
                $lembur = ($absen->status == 'hadir' && $absen->lembur == 1) ? 'Ya' : 'Tidak';
                $durasiLembur = '-';

                if ($lembur == 'Ya' && $absen->jam_lembur && $absen->waktu_lembur_selesai) {
                    try {
                        $mulaiLembur = Carbon::createFromFormat('H:i:s', $absen->jam_lembur);
                        $selesaiLembur = Carbon::createFromFormat('H:i:s', $absen->waktu_lembur_selesai);
                        
                        if ($selesaiLembur->lt($mulaiLembur)) {
                            $selesaiLembur->addDay();
                        }
                        $selisihMenit = abs($selesaiLembur->diffInMinutes($mulaiLembur));
                        $hours = floor($selisihMenit / 60);
                        $minutes = $selisihMenit % 60;
                        $durasiLembur = $hours . ' jam ' . $minutes . ' menit';

                    } catch (\Exception $e) {
                        $durasiLembur = 'Gagal menghitung';
                    }
                }

                $eventsByDate[$tanggal] = [
                    'type'          => $visualType, // 'hadir', 'terlambat', 'alpa'
                    'status'        => $displayStatus, // 'Hadir', 'Alpa'
                    'keterangan'    => $keterangan,
                    'jam_masuk'     => $absen->jam_masuk ?? '-',
                    'jam_pulang'    => $absen->jam_pulang ?? '-',
                    'lembur'        => $lembur,
                    'durasi_lembur' => $durasiLembur,
                    'source'        => 'absensi_model'
                ];
            }
        }

        // Finalisasi data untuk dikirim ke View
        foreach ($eventsByDate as $tanggal => $dataEvent) {
            $statusPerDate[$tanggal] = $dataEvent['type']; // Mengirim 'type' untuk warna dot
            $absenPerTanggal[$tanggal] = $dataEvent;       // Mengirim semua detail ke pop-up
        }

        return view('karyawan.kalender', [
            'events' => [],
            'statusPerDate' => $statusPerDate,
            'absenPerTanggal' => $absenPerTanggal,
        ]);
    }
}
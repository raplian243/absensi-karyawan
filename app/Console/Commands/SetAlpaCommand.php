<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Absensi;
use App\Models\Izin;
use Carbon\Carbon;

class SetAlpaCommand extends Command
{
    protected $signature = 'absensi:set-alpa';
    protected $description = 'Set status alpa untuk karyawan yang tidak absen dan tidak izin hari ini';

    public function handle()
    {
        $today = now()->toDateString();
        $batasPulang = env('BATAS_ABSEN_PULANG', '17:00:00');

        if (now()->format('H:i:s') < $batasPulang) {
            $this->info("Belum waktu set alpa. Batas: $batasPulang");
            return;
        }

        $karyawanList = User::where('role', env('ROLE_KARYAWAN', 'karyawan'))->get();
        $totalAlpa = 0;

        foreach ($karyawanList as $user) {
            $sudahAbsen = Absensi::where('user_id', $user->id)->whereDate('tanggal', $today)->exists();

            $sudahIzin = Izin::where('user_id', $user->id)
                ->where('status', 'Terima')
                ->whereDate('tanggal_mulai', '<=', $today)
                ->whereDate('tanggal_selesai', '>=', $today)
                ->exists();

            if (!$sudahAbsen && !$sudahIzin) {
                Absensi::create([
                    'user_id' => $user->id,
                    'tanggal' => $today,
                    'status' => 'alpa',
                    'keterangan' => 'Tidak hadir tanpa keterangan',
                ]);

                $this->info("Set ALPA untuk: {$user->name}");
                $totalAlpa++;
            }
        }

        $this->info("Selesai. Total karyawan alpa hari ini: $totalAlpa");
    }
}

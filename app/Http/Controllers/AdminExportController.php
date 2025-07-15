<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Absensi;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminExportController extends Controller
{
    public function exportExcel(Request $request): StreamedResponse
    {
        // Buat spreadsheet baru
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $sheet->fromArray([
            ['Nama', 'Tanggal', 'Jam Masuk', 'Jam Pulang', 'Status', 'Keterangan', 'Lembur', 'Jam Lembur']
        ], null, 'A1');

        // Ambil data absensi dengan filter (jika ada)
        $absensis = Absensi::with('user')
            ->when($request->start_date, fn($q) =>
                $q->whereDate('tanggal', '>=', $request->start_date)
            )
            ->when($request->end_date, fn($q) =>
                $q->whereDate('tanggal', '<=', $request->end_date)
            )
            ->when($request->search, fn($q) =>
                $q->whereHas('user', fn($u) =>
                    $u->where('name', 'like', '%' . $request->search . '%')
                )
            )
            ->orderBy('tanggal', 'desc')
            ->get();

        // Isi baris data
        $row = 2;
        foreach ($absensis as $a) {
            $sheet->fromArray([
                $a->user->name,
                $a->tanggal,
                $a->jam_masuk ?? '-',
                $a->jam_pulang ?? '-',
                ucfirst($a->status),
                $a->keterangan ?? '-',
                $a->lembur ? 'Ya' : 'Tidak',
                $a->waktu_lembur_selesai ?? '-'
            ], null, "A{$row}");
            $row++;
        }

        // Tulis dan stream file ke browser
        $writer = new Xlsx($spreadsheet);
        $fileName = 'data_absensi_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

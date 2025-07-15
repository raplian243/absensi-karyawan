<table class="table table-bordered table-sm" style="width: center;">
    <tbody>
        <tr>
            <th width="50%">Gaji Pokok</th>
            <td>Rp {{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Tunjangan</th>
            <td>Rp {{ number_format($gaji->tunjangan, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Bonus Lembur</th>
            <td>
                Rp {{ number_format($bonusLembur, 0, ',', '.') }}
                @php
                    $totalHoursFloat = $rekapDetail['jam_lembur'] ?? 0;
                    $hours = floor($totalHoursFloat);
                    $minutes = round(($totalHoursFloat - $hours) * 60);
                    $lemburFormatted = '';
                    
                    if ($hours > 0) {
                        $lemburFormatted .= $hours . ' jam ';
                    }
                    if ($minutes > 0 || ($hours == 0 && $minutes == 0)) { 
                        $lemburFormatted .= $minutes . ' menit';
                    }
                    if (empty($lemburFormatted)) {
                        $lemburFormatted = '0 jam 0 menit';
                    }
                @endphp
                ({{ $lemburFormatted }})
            </td>
        </tr>
        <tr>
            <th>Potongan Alpa</th>
            <td>
                - Rp {{ number_format($potonganAlpa, 0, ',', '.') }}
                ({{ $rekapDetail['alpa'] ?? 0 }} hari)
            </td>
        </tr>
        <tr>
            <th>Potongan Terlambat</th>
            <td>
                - Rp {{ number_format($potonganTerlambat, 0, ',', '.') }}
                ({{ $rekapDetail['terlambat'] ?? 0 }} kali)
            </td>
        </tr>
        <tr class="table-success fw-bold">
            <th>Total Gaji Bersih</th>
            <td>Rp {{ number_format($totalGaji, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>
<!-- resources/views/karyawan/slip/modal_detail_content.blade.php -->

<div class="modal-header">
    <h5 class="modal-title" id="modalSlipGajiLabel">Detail Slip Gaji Bulan {{ \Carbon\Carbon::parse($bulan . '-01')->translatedFormat('F Y') }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body text-start">
    <div class="table-responsive d-flex justify-content-center">
        {{-- BARIS INI BERUBAH: width: 100% untuk melebarkan tabel --}}
        <table class="table table-bordered table-sm" style="width: 100%;">
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
                            if ($minutes > 0 || ($hours == 0 && $minutes == 0 && $totalHoursFloat == 0)) {
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
    </div>
</div>
{{-- BARIS INI BERUBAH: justify-content-start dan urutan tombol --}}
<div class="modal-footer d-flex justify-content-start">
    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Tutup</button>
    <a href="{{ route('karyawan.slip.cetak', $bulan) }}" class="btn btn-danger" target="_blank">
        <i class="fas fa-file-pdf"></i> Download PDF
    </a>
</div>

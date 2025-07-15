<!-- Modal Popup Direktur: Gaji dan Slip Gaji Karyawan -->
<div class="modal fade" id="rekapGajiModal" tabindex="-1" aria-labelledby="rekapGajiModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Daftar & Slip Gaji Karyawan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <!-- Tab Navigasi -->
        <ul class="nav nav-tabs mb-3" id="gajiTab" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" id="daftar-tab" data-bs-toggle="tab" data-bs-target="#daftar" type="button" role="tab">Daftar Gaji</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" id="slip-tab" data-bs-toggle="tab" data-bs-target="#slip" type="button" role="tab">Slip Gaji</button>
          </li>
        </ul>

        <div class="tab-content" id="gajiTabContent">
          <!-- Tab Daftar Gaji -->
          <div class="tab-pane fade show active" id="daftar" role="tabpanel">
            <div class="d-flex flex-wrap gap-2 mb-3 align-items-end">
              <input type="text" id="searchNamaGaji" class="form-control" placeholder="Cari Nama..." style="max-width: 250px;">
              <button type="button" class="btn btn-primary" onclick="filterGaji()">Cari</button>
              <button type="button" class="btn btn-secondary" onclick="resetGaji()">Reset</button>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-sm align-middle text-center" id="gajiTable">
                <thead class="table-dark">
                  <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Gaji Pokok</th>
                    <th>Tunjangan</th>
                    <th>Total Gaji</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($gajiList as $gaji)
                    <tr>
                      <td class="nama">{{ $gaji->user->name ?? '-' }}</td>
                      <td>{{ $gaji->user->email ?? '-' }}</td>
                      <td>Rp{{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                      <td>Rp{{ number_format($gaji->tunjangan, 0, ',', '.') }}</td>
                      <td>Rp{{ number_format($gaji->gaji_pokok + $gaji->tunjangan, 0, ',', '.') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tab Slip Gaji -->
          <div class="tab-pane fade" id="slip" role="tabpanel" aria-labelledby="slip-tab">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"> 

	              <div class="d-flex gap-2 align-items-end flex-wrap">
                                <input type="text" id="searchNamaSlip" class="form-control" placeholder="Cari Nama..." style="max-width: 205px;">
                                <button type="button" class="btn btn-primary" onclick="filterSlip()">Cari</button>
                                <button type="button" class="btn btn-secondary" onclick="resetSlip()">Reset</button>
              </div>

              <div class="d-flex align-items-center gap-2 me-auto ms-md-5">
                <form method="GET" action="{{ route('direktur.dashboard') }}" class="m-0">
                  <input type="hidden" name="bulan" value="{{ \Carbon\Carbon::parse($searchBulan)->subMonth()->format('Y-m') }}">
                  <input type="hidden" name="showGajiModal" value="true">
                  <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></button>
                </form>

                <h5 class="m-0 text-center">Slip Gaji Bulan {{ \Carbon\Carbon::parse($searchBulan)->translatedFormat('F Y') }}</h5>

                <form method="GET" action="{{ route('direktur.dashboard') }}" class="m-0">
                  <input type="hidden" name="bulan" value="{{ \Carbon\Carbon::parse($searchBulan)->addMonth()->format('Y-m') }}">
                  <input type="hidden" name="showGajiModal" value="true">
                  <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></button>
                </form>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-sm align-middle text-center" id="slipTable">
                <thead class="table-dark">
                  <tr>
                    <th>Nama</th>
                    <th>Bulan</th>
                    <th>Total Terlambat</th>
                    <th>Total Alpa</th>
                    <th>Bonus Lembur</th>
                    <th>Potongan Terlambat</th>
                    <th>Potongan Alpa</th>
                    <th>Total Gaji</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($slipList as $slip)
                    @php
                      $gaji = $gajiList->firstWhere('user.name', $slip['name'] ?? null);
                      $gajiPokok = $gaji->gaji_pokok ?? 0;
                      $tunjangan = $gaji->tunjangan ?? 0;

                      $terlambat = $slip['terlambat'] ?? 0;
                      $alpa = $slip['alpa'] ?? 0;
                      $jamLembur = $slip['jam_lembur'] ?? 0;

                      $bonusLembur = $jamLembur * config('app.bonus_lembur_per_jam', 10000);
                      $potonganAlpa = $gajiPokok * ($alpa * config('app.potongan_alpa', 3)) / 100;
                      $potonganTerlambat = $gajiPokok * ($terlambat * config('app.potongan_terlambat', 1)) / 100;

                      $totalGaji = $gajiPokok + $tunjangan + $bonusLembur - $potonganAlpa - $potonganTerlambat;

                      $jam = floor($jamLembur);
                      $menit = round(($jamLembur - $jam) * 60);
                      $lemburString = $jam . ' jam ' . $menit . ' menit';
                    @endphp
                    <tr>
                      <td class="nama">{{ $slip['name'] }}</td>
                      <td class="bulan">{{ $slip['bulan'] }}</td>
                      <td>{{ $terlambat }} kali</td>
                      <td>{{ $alpa }} hari</td>
                      <td>Rp{{ number_format($bonusLembur, 0, ',', '.') }}<br>({{ $lemburString }})</td>
                      <td>- Rp{{ number_format($potonganTerlambat, 0, ',', '.') }}</td>
                      <td>- Rp{{ number_format($potonganAlpa, 0, ',', '.') }}</td>
                      <td class="fw-bold table-success">Rp{{ number_format($totalGaji, 0, ',', '.') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- JS untuk filter -->
<script>
  function filterGaji() {
    const nama = document.getElementById('searchNamaGaji').value.toLowerCase();
    document.querySelectorAll('#gajiTable tbody tr').forEach(row => {
      const rowNama = row.querySelector('.nama')?.textContent.toLowerCase() || '';
      row.style.display = rowNama.includes(nama) ? '' : 'none';
    });
  }

  function resetGaji() {
    document.getElementById('searchNamaGaji').value = '';
    filterGaji();
  }

  function filterSlip() {
    const nama = document.getElementById('searchNamaSlip').value.toLowerCase();
    document.querySelectorAll('#slipTable tbody tr').forEach(row => {
      const rowNama = row.querySelector('.nama')?.textContent.toLowerCase() || '';
      const cocokNama = !nama || rowNama.includes(nama);
      row.style.display = (cocokNama) ? '' : 'none';
    });
  }

  function resetSlip() {
    document.getElementById('searchNamaSlip').value = '';
    filterSlip();
  }
</script>

<!-- Script Tampilkan Modal Otomatis -->
@if ($bukaGajiModal ?? false)
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('rekapGajiModal'));
    modal.show();
  });
</script>
@endif

<!-- Modal: Form Izin -->
<div class="modal fade" id="formizin" tabindex="-1" aria-labelledby="formIzinModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('izin.ajukan') }}" method="POST" enctype="multipart/form-data" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title" id="formIzinModalLabel">Form Pengajuan Izin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
          <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
          <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="alasan" class="form-label">Alasan Izin</label>
          <textarea name="alasan" id="alasan" class="form-control" rows="3" required></textarea>
        </div>
        <div class="mb-3">
          <label for="bukti" class="form-label">Upload Lampiran (Opsional)</label>
          <input type="file" name="bukti" id="bukti" class="form-control" accept="image/*,application/pdf">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Kirim Izin</button>
      </div>
    </form>
  </div>
</div>

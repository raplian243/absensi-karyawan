<form id="editGajiForm" action="{{ route('admin.gaji.update', $gaji->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label for="gaji_pokok" class="form-label">Gaji Pokok</label>
        <input type="number" name="gaji_pokok" id="gaji_pokok" class="form-control" value="{{ $gaji->gaji_pokok }}" required>
        <div class="invalid-feedback" id="gaji_pokok-error"></div>
    </div>

    <div class="mb-3">
        <label for="tunjangan" class="form-label">Tunjangan</label>
        <input type="number" name="tunjangan" id="tunjangan" class="form-control" value="{{ $gaji->tunjangan }}">
        <div class="invalid-feedback" id="tunjangan-error"></div>
    </div>
</form>

<form id="{{ isset($isEdit) ? 'editGajiForm' : 'addGajiForm' }}"
      action="{{ isset($isEdit) ? route('admin.gaji.update', $gaji->id) : route('admin.gaji.store') }}"
      method="POST">
    @csrf
    @isset($isEdit)
        @method('PUT')
    @endisset

    {{-- Pilih Karyawan --}}
    <div class="mb-3">
        <label for="user_id" class="form-label">Pilih Karyawan</label>
        <select name="user_id" id="user_id" class="form-control" {{ isset($isEdit) ? 'disabled' : 'required' }}>
            <option value="">-- Pilih Karyawan --</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}"
                    {{ old('user_id', $gaji->user_id ?? '') == $user->id ? 'selected' : '' }}>
                    {{ $user->name }} ({{ $user->email }})
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" id="user_id-error"></div>
    </div>

    {{-- Gaji Pokok --}}
    <div class="mb-3">
        <label for="gaji_pokok" class="form-label">Gaji Pokok</label>
        <input type="number" name="gaji_pokok" id="gaji_pokok" class="form-control"
               value="{{ old('gaji_pokok', $gaji->gaji_pokok ?? '') }}"
               required min="0" autocomplete="off">
        <div class="invalid-feedback" id="gaji_pokok-error"></div>
    </div>

    {{-- Tunjangan --}}
    <div class="mb-3">
        <label for="tunjangan" class="form-label">Tunjangan</label>
        <input type="number" name="tunjangan" id="tunjangan" class="form-control"
               value="{{ old('tunjangan', $gaji->tunjangan ?? '') }}"
               min="0" autocomplete="off">
        <div class="invalid-feedback" id="tunjangan-error"></div>
    </div>
</form>

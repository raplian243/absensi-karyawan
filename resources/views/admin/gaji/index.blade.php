@extends('layouts.app')

@section('content')
<div class="container">

    {{-- Judul --}}
    <h3 class="mb-4">Daftar Gaji Karyawan</h3>

    {{-- Tombol Tambah + Filter --}}
    <div class="d-flex align-items-center justify-content-start mb-4 flex-wrap gap-2">
        {{-- Tombol Tambah --}}
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addGajiModal" id="addGajiBtn">
            + Tambah Data Gaji
        </button>

        {{-- Form Filter --}}
        <form class="d-flex align-items-center gap-2" method="GET" action="{{ route('admin.gaji.index') }}">
            <input type="text" name="nama" class="form-control" placeholder="Cari nama..." value="{{ request('nama') }}">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('admin.gaji.index') }}" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    {{-- Alert --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Tabel --}}
    <table class="text-center table table-bordered">
        <thead>
            <tr>
                <th>Nama Karyawan</th>
                <th>Email</th>
                <th>Gaji Pokok</th>
                <th>Tunjangan</th>
                <th>Total Gaji</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gajis as $gaji)
            <tr>
                <td>{{ $gaji->user->name }}</td>
                <td>{{ $gaji->user->email }}</td>
                <td>Rp{{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                <td>Rp{{ number_format($gaji->tunjangan, 0, ',', '.') }}</td>
                <td>Rp{{ number_format($gaji->gaji_pokok + $gaji->tunjangan, 0, ',', '.') }}</td>
                <td>
                    <a href="#" class="btn btn-warning btn-sm" onclick="openEditModal({{ $gaji->id }})">Ubah</a>
                    <form action="{{ route('admin.gaji.destroy', $gaji->id) }}" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?')">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center">Tidak ada data gaji karyawan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal --}}
<div class="modal fade" id="addGajiModal" tabindex="-1" aria-labelledby="addGajiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGajiModalLabel">Tambah Data Gaji Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="addGajiModalBody">
    <div class="text-center">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2">Memuat form...</p>
    </div>
</div>

<div class="modal-footer justify-content-start">
 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-success me-2" id="modalSubmitButton" form="addGajiForm">Simpan</button>
</div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('addGajiModal'));
    const modalEl = document.getElementById('addGajiModal');
    const body = document.getElementById('addGajiModalBody');
    const title = document.getElementById('addGajiModalLabel');
    const submitBtn = document.getElementById('modalSubmitButton');

    // Handler Tambah
    document.getElementById('addGajiBtn').addEventListener('click', () => {
        title.textContent = 'Tambah Data Gaji Karyawan';
        body.innerHTML = `<div class="text-center"><div class="spinner-border"></div><p>Memuat...</p></div>`;
        submitBtn.setAttribute('form', 'addGajiForm');

        fetch("{{ route('admin.gaji.create_modal_content') }}")
            .then(res => res.text())
            .then(html => body.innerHTML = html)
            .catch(err => body.innerHTML = `<div class="text-danger">Gagal memuat form.</div>`);
    });

    // Handler Edit
    window.openEditModal = function (id) {
        title.textContent = 'Edit Data Gaji';
        body.innerHTML = `<div class="text-center"><div class="spinner-border"></div><p>Memuat...</p></div>`;
        submitBtn.setAttribute('form', 'editGajiForm');

        fetch(`/admin/gaji/${id}/edit-modal-content`)
            .then(res => res.text())
            .then(html => body.innerHTML = html)
            .catch(err => body.innerHTML = `<div class="text-danger">Gagal memuat form edit.</div>`);

        modal.show();
    }

    // Handle Submit Form (Create/Edit)
    submitBtn.addEventListener('click', () => {
        const form = document.getElementById('addGajiForm') || document.getElementById('editGajiForm');
        if (!form) return;

        const formData = new FormData(form);
        const action = form.getAttribute('action');
const method = form.querySelector('input[name="_method"]')?.value || form.method;

if (method.toUpperCase() !== 'POST') {
    formData.append('_method', method); // tambahkan baris ini
}


        submitBtn.disabled = true;
        submitBtn.textContent = 'Menyimpan...';

        fetch(action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(res => res.json().then(data => ({ status: res.status, body: data })))
        .then(({ status, body }) => {
            if (status === 200 || status === 201) {
                alert(body.success || 'Berhasil disimpan.');
                modal.hide();
                location.reload();
            } else if (status === 422) {
                // Validasi
                Object.entries(body.errors || {}).forEach(([key, msgs]) => {
                    const input = form.querySelector(`[name="${key}"]`);
                    const errorEl = form.querySelector(`#${key}-error`);
                    if (input) input.classList.add('is-invalid');
                    if (errorEl) errorEl.textContent = msgs.join(', ');
                });
            } else {
                alert(body.message || 'Terjadi kesalahan saat menyimpan.');
            }
        })
        .catch(err => alert('Gagal mengirim data.'))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Simpan';
        });
    });
});
</script>
@endpush

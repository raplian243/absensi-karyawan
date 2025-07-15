<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GajiKaryawan;
use App\Models\User; // Pastikan ini diimpor
use Illuminate\Validation\Rule; // Pastikan ini diimpor

class AdminGajiController extends Controller
{
    /**
     * Menampilkan daftar gaji karyawan.
     */
    public function index(Request $request)
{
    try {
        $query = GajiKaryawan::with('user');

        // Filter nama
        if ($request->filled('nama')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->nama . '%');
            });
        }

        $gajis = $query->get();

        return view('admin.gaji.index', compact('gajis'));
    } catch (\Exception $e) {
        return back()->with('error', 'Gagal memuat daftar gaji: ' . $e->getMessage());
    }
}


    /**
     * Menampilkan form untuk membuat gaji baru (ini tidak akan dipanggil langsung oleh modal).
     */
    public function create()
    {
        try {
            $users = User::where('role', config('app.role_karyawan'))
                         ->whereDoesntHave('gajiKaryawan') // Hanya user yang belum punya data gaji
                         ->get();
            return view('admin.gaji.form', compact('users'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memuat form tambah gaji: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan konten form tambah gaji untuk modal.
     * Ini adalah metode yang akan dipanggil via AJAX.
     */
    public function createModalContent()
    {
        try {
            $users = User::where('role', config('app.role_karyawan'))
                         ->whereDoesntHave('gajiKaryawan') // Hanya user yang belum punya data gaji
                         ->get();
            // Mengembalikan view tanpa layout utama
            return view('admin.gaji.create_modal_content', compact('users'));
        } catch (\Exception $e) {
            // Jika ada error saat memuat konten modal, kirim pesan error HTML
            return '<div class="alert alert-danger">Gagal memuat form: ' . $e->getMessage() . '</div>';
        }
    }

    /**
     * Menyimpan data gaji baru.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'user_id' => [
                    'required',
                    'exists:users,id',
                    // Memastikan user_id belum memiliki gaji_karyawan
                    Rule::unique('gaji_karyawan')->where(function ($query) use ($request) {
                        return $query->where('user_id', $request->user_id);
                    }),
                ],
                'gaji_pokok' => 'required|numeric|min:0',
                'tunjangan' => 'nullable|numeric|min:0',
            ], [
                'user_id.unique' => 'Karyawan ini sudah memiliki data gaji.',
                'user_id.required' => 'Karyawan harus dipilih.',
                'gaji_pokok.required' => 'Gaji pokok harus diisi.',
                'gaji_pokok.numeric' => 'Gaji pokok harus berupa angka.',
                'gaji_pokok.min' => 'Gaji pokok tidak boleh negatif.',
                'tunjangan.numeric' => 'Tunjangan harus berupa angka.',
                'tunjangan.min' => 'Tunjangan tidak boleh negatif.',
            ]);

            GajiKaryawan::create($request->all());

            return response()->json(['success' => 'Data gaji berhasil ditambahkan!']);
            // Untuk halaman penuh, biasanya return redirect()->route('admin.gaji.index')->with('success', 'Data gaji berhasil ditambahkan!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menambahkan data gaji: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan form edit gaji.
     */
    public function edit(GajiKaryawan $gaji)
    {
        try {
            $users = User::where('role', config('app.role_karyawan'))->get(); // Mungkin hanya user ini atau semua user
            return view('admin.gaji.form', compact('gaji', 'users'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memuat form edit gaji: ' . $e->getMessage());
        }
    }

    /**
     * Memperbarui data gaji.
     */
    public function update(Request $request, GajiKaryawan $gaji)
{
    try {
        $request->validate([
            'gaji_pokok' => 'required|numeric|min:0',
            'tunjangan' => 'nullable|numeric|min:0',
        ], [
            'gaji_pokok.required' => 'Gaji pokok harus diisi.',
            'gaji_pokok.numeric' => 'Gaji pokok harus berupa angka.',
            'gaji_pokok.min' => 'Gaji pokok tidak boleh negatif.',
            'tunjangan.numeric' => 'Tunjangan harus berupa angka.',
            'tunjangan.min' => 'Tunjangan tidak boleh negatif.',
        ]);

        $gaji->update($request->all());

        // ✅ Jika request dari AJAX, kirim response JSON
        if ($request->ajax()) {
            return response()->json(['success' => 'Data gaji berhasil diperbarui!']);
        }

        // Jika request biasa (non-AJAX)
        return redirect()->route('admin.gaji.index')->with('success', 'Data gaji berhasil diperbarui!');
    } catch (\Illuminate\Validation\ValidationException $e) {
        if ($request->ajax()) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        return back()->withErrors($e->errors())->withInput();
    } catch (\Exception $e) {
        if ($request->ajax()) {
            return response()->json(['error' => 'Gagal memperbarui data gaji: ' . $e->getMessage()], 500);
        }

        return back()->with('error', 'Gagal memperbarui data gaji: ' . $e->getMessage());
    }
}


public function editModalContent(GajiKaryawan $gaji)
{
    try {
        $users = User::where('role', config('app.role_karyawan'))->get();
        return view('admin.gaji.create_modal_content', [
            'gaji' => $gaji,
            'users' => $users,
            'isEdit' => true,
        ]);
    } catch (\Exception $e) {
        return '<div class="alert alert-danger">Gagal memuat form edit: ' . $e->getMessage() . '</div>';
    }
}


    /**
     * Menghapus data gaji.
     */
    public function destroy(GajiKaryawan $gaji)
    {
        try {
            $gaji->delete();
            return redirect()->route('admin.gaji.index')->with('success', 'Data gaji berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data gaji: ' . $e->getMessage());
        }
    }
}

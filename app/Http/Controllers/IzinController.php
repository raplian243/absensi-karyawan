<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Absensi;
use App\Models\Izin;

class IzinController extends Controller
{

public function update(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:Tunggu,Terima,Tolak',
    ]);

    $izin = Izin::findOrFail($id);
    $izin->status = $request->status;
    $izin->save();

    return back()->with('success', 'Status izin berhasil diperbarui.');
}

public function ajukan(Request $request)
{
    $request->validate([
        'tanggal_mulai' => 'required|date',
        'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        'alasan' => 'required|string|max:255',
        'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    $buktiPath = $request->hasFile('bukti')
        ? $request->file('bukti')->store('izin/bukti', 'public')
        : null;

    Izin::create([
        'user_id' => auth()->id(),
        'tanggal_mulai' => $request->tanggal_mulai,
        'tanggal_selesai' => $request->tanggal_selesai,
        'alasan' => $request->alasan,
        'bukti' => $buktiPath,
        'status' => 'Tunggu',
    ]);

    return back()->with('izin_success', 'Permohonan izin berhasil diajukan!');
}

public function store(Request $request)
{
    $request->validate([
        'tanggal_mulai' => 'required|date',
        'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        'alasan' => 'required|string|max:255',
        'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    $buktiPath = null;

    if ($request->hasFile('bukti')) {
        $buktiPath = $request->file('bukti')->store('bukti_izin', 'public'); // Folder tujuan
    }

    Izin::create([
        'user_id' => auth()->id(),
        'tanggal_mulai' => $request->tanggal_mulai,
        'tanggal_selesai' => $request->tanggal_selesai,
        'alasan' => $request->alasan,
        'bukti' => $buktiPath, // Pakai field "bukti"
        'status' => 'Tunggu',
    ]);

    return redirect()->back()->with('izin_success', 'Pengajuan izin berhasil dikirim.');
}

}
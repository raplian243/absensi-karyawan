<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absensi;

class DashboardController extends Controller
{
    public function index()
    {
        $dataAbsensi = Absensi::with('user')->orderByDesc('tanggal')->get();

        return view('admin.dashboard', compact('dataAbsensi'));
    }
}

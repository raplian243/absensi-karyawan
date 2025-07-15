<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_masuk',
        'lokasi_masuk',
        'foto_masuk',
        'jam_pulang',
        'lokasi_pulang',
        'foto_pulang',
        'lembur',
        'jam_lembur',
'waktu_lembur_selesai',
'durasi_lembur',
        'status',
        'keterangan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

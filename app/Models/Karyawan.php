<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'nip',
        'jabatan',
    ];

    // (Opsional) relasi jika kamu pakai user_id misalnya
    public function user()
    {
         return $this->belongsTo(User::class);
     }
}


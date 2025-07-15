<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Relasi Eloquent
    public function gaji()
    {
        return $this->hasOne(\App\Models\GajiKaryawan::class);
    }

    public function absensis()
    {
        return $this->hasMany(\App\Models\Absensi::class);
    }

    public function izins()
    {
        return $this->hasMany(\App\Models\Izin::class);
    }

public function gajiKaryawan()
{
    return $this->hasOne(\App\Models\GajiKaryawan::class, 'user_id');
}


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

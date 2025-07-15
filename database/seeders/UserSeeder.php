<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {

User::create([
            'name' => 'Direktur PT Global Internet Data',
            'email' => 'direktur@contoh.com',
            'password' => Hash::make('direktur123'),
            'role' => 'direktur',
        ]);

        User::create([
            'name' => 'Wira ApriLian',
            'email' => 'wiraprilian243@gmail.com',
            'password' => Hash::make('wira123'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Raplian',
            'email' => 'raplian243@gmail.com',
            'password' => Hash::make('raplian123'),
            'role' => 'karyawan',
        ]);

User::create([
            'name' => 'Wahyu',
            'email' => 'wahyu@contoh.com',
            'password' => Hash::make('wahyu123'),
            'role' => 'karyawan',
        ]);

    }
}

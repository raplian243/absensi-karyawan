<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DirekturSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Direktur PT Global Internet Data',
            'email' => 'direktur@contoh.com',
            'password' => Hash::make('direktur123'), 
            'role' => 'direktur',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

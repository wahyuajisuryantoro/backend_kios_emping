<?php

namespace Database\Seeders;

use App\Models\Toko;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TokoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Kios Emping Bu Fatah',
            'email' => 'kiosemping@gmail.com',
            'password' => Hash::make('password'),
        ]);

        Toko::create([
            'user_id' => $user->id,
            'nama_toko' => 'Toko Test',
            'alamat' => 'Jl. Test No. 123',
            'telepon' => '08123456789',
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatuanSeeder extends Seeder
{
    public function run()
    {
        $satuans = [
            // Satuan Berat
            ['tipe_satuan' => 'berat', 'nama_satuan' => 'Gram', 'nilai_konversi' => 1.00, 'simbol' => 'g'],
            ['tipe_satuan' => 'berat', 'nama_satuan' => 'Kilogram', 'nilai_konversi' => 1000.00, 'simbol' => 'kg'],
            ['tipe_satuan' => 'berat', 'nama_satuan' => 'Ons', 'nilai_konversi' => 100.00, 'simbol' => 'ons'],
            ['tipe_satuan' => 'berat', 'nama_satuan' => 'Kuintal', 'nilai_konversi' => 100000.00, 'simbol' => 'kw'],
            
            // Satuan Unit
            ['tipe_satuan' => 'unit', 'nama_satuan' => 'Pcs', 'nilai_konversi' => 1.00, 'simbol' => 'pcs'],
            ['tipe_satuan' => 'unit', 'nama_satuan' => 'Lusin', 'nilai_konversi' => 12.00, 'simbol' => 'lsn'],
            ['tipe_satuan' => 'unit', 'nama_satuan' => 'Gross', 'nilai_konversi' => 144.00, 'simbol' => 'grs'],
            ['tipe_satuan' => 'unit', 'nama_satuan' => 'Ball', 'nilai_konversi' => 1.00, 'simbol' => 'ball'],
            
            // Satuan Isi
            ['tipe_satuan' => 'isi', 'nama_satuan' => 'Milliliter', 'nilai_konversi' => 1.00, 'simbol' => 'ml'],
            ['tipe_satuan' => 'isi', 'nama_satuan' => 'Liter', 'nilai_konversi' => 1000.00, 'simbol' => 'l'],
        ];

        foreach ($satuans as $satuan) {
            DB::table('tbl_satuan')->insert([
                'tipe_satuan' => $satuan['tipe_satuan'],
                'nama_satuan' => $satuan['nama_satuan'],
                'nilai_konversi' => $satuan['nilai_konversi'],
                'simbol' => $satuan['simbol'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}

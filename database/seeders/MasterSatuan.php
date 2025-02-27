<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterSatuan extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Insert Master Satuan
        $masterSatuan = [
            // Satuan Berat/Massa
            ['nama_satuan' => 'Ton', 'kode_satuan' => 'ton', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Megaton', 'kode_satuan' => 'mton', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Gigaton', 'kode_satuan' => 'gton', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Kilogram', 'kode_satuan' => 'kg', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Hektogram', 'kode_satuan' => 'hg', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Gram', 'kode_satuan' => 'g', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Miligram', 'kode_satuan' => 'mg', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Mikrogram', 'kode_satuan' => 'µg', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Nanogram', 'kode_satuan' => 'ng', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Picogram', 'kode_satuan' => 'pg', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Kuintal', 'kode_satuan' => 'kw', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Ons', 'kode_satuan' => 'ons', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Pon', 'kode_satuan' => 'pon', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Karat', 'kode_satuan' => 'karat', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Cental', 'kode_satuan' => 'ctl', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Slug', 'kode_satuan' => 'slug', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],
            ['nama_satuan' => 'Stone', 'kode_satuan' => 'st', 'tipe_satuan' => 'berat', 'satuan_dasar' => 'gram'],

            // Satuan Volume
            ['nama_satuan' => 'Liter', 'kode_satuan' => 'l', 'tipe_satuan' => 'volume', 'satuan_dasar' => 'milliliter'],
            ['nama_satuan' => 'Desiliter', 'kode_satuan' => 'dl', 'tipe_satuan' => 'volume', 'satuan_dasar' => 'milliliter'],
            ['nama_satuan' => 'Centiliter', 'kode_satuan' => 'cl', 'tipe_satuan' => 'volume', 'satuan_dasar' => 'milliliter'],
            ['nama_satuan' => 'Mililiter', 'kode_satuan' => 'ml', 'tipe_satuan' => 'volume', 'satuan_dasar' => 'milliliter'],

            // Satuan Unit
            ['nama_satuan' => 'Pieces', 'kode_satuan' => 'pcs', 'tipe_satuan' => 'satuan', 'satuan_dasar' => 'pieces'],
            ['nama_satuan' => 'Lusin', 'kode_satuan' => 'lsn', 'tipe_satuan' => 'satuan', 'satuan_dasar' => 'pieces'],
            ['nama_satuan' => 'Gross', 'kode_satuan' => 'grs', 'tipe_satuan' => 'satuan', 'satuan_dasar' => 'pieces'],
            ['nama_satuan' => 'Kodi', 'kode_satuan' => 'kdi', 'tipe_satuan' => 'satuan', 'satuan_dasar' => 'pieces'],
        ];

        DB::table('tbl_master_satuan')->insert($masterSatuan);

        // Insert Konversi Satuan
        $konversiSatuan = [
            // Konversi Berat ke Gram
            ['satuan_asal_id' => 1, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 1000000], // 1 Ton = 1000000 g
            ['satuan_asal_id' => 2, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 1000000000000], // 1 Megaton = 1000000000000 g
            ['satuan_asal_id' => 3, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 1000000000000000], // 1 Gigaton
            ['satuan_asal_id' => 4, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 1000], // 1 kg = 1000 g
            ['satuan_asal_id' => 5, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 100], // 1 hg = 100 g
            ['satuan_asal_id' => 7, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 0.001], // 1 mg = 0.001 g
            ['satuan_asal_id' => 8, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 0.000001], // 1 µg
            ['satuan_asal_id' => 9, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 0.000000001], // 1 ng
            ['satuan_asal_id' => 10, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 0.00000000001], // 1 pg
            ['satuan_asal_id' => 11, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 100000], // 1 kuintal = 100000 g
            ['satuan_asal_id' => 12, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 100], // 1 ons = 100 g
            ['satuan_asal_id' => 13, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 500], // 1 pon = 500 g
            ['satuan_asal_id' => 14, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 0.2], // 1 karat = 0.2 g
            ['satuan_asal_id' => 15, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 45350], // 1 cental = 45.35 kg
            ['satuan_asal_id' => 16, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 15590], // 1 slug = 15.59 kg
            ['satuan_asal_id' => 17, 'satuan_tujuan_id' => 6, 'nilai_konversi' => 6350], // 1 stone = 6.35 kg

            // Konversi Volume ke Milliliter
            ['satuan_asal_id' => 18, 'satuan_tujuan_id' => 21, 'nilai_konversi' => 1000], // 1 l = 1000 ml
            ['satuan_asal_id' => 19, 'satuan_tujuan_id' => 21, 'nilai_konversi' => 100], // 1 dl = 100 ml
            ['satuan_asal_id' => 20, 'satuan_tujuan_id' => 21, 'nilai_konversi' => 10], // 1 cl = 10 ml

            // Konversi Unit ke Pieces
            ['satuan_asal_id' => 22, 'satuan_tujuan_id' => 23, 'nilai_konversi' => 12], // 1 lusin = 12 pcs
            ['satuan_asal_id' => 23, 'satuan_tujuan_id' => 24, 'nilai_konversi' => 144], // 1 gross = 144 pcs
            ['satuan_asal_id' => 24, 'satuan_tujuan_id' => 25, 'nilai_konversi' => 20], // 1 kodi = 20 pcs
        ];

        DB::table('tbl_konversi_satuan')->insert($konversiSatuan);
    }
}

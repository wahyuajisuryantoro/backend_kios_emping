<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stok extends Model
{
    use SoftDeletes;
    
    protected $table = 'tbl_stok';
    protected $primaryKey = 'id_stok';
    
    protected $fillable = [
        'kode_barang',
        'stok_awal',
        'stok_masuk',
        'stok_keluar',
        'stok_akhir',
        'batas_minimum_stok',
        'lokasi_penyimpanan',
        'tanggal_stok',
        'status_barang'
    ];

    protected $casts = [
        'stok_awal' => 'integer',
        'stok_masuk' => 'integer',
        'stok_keluar' => 'integer',
        'stok_akhir' => 'integer',
        'batas_minimum_stok' => 'integer',
        'tanggal_stok' => 'date'
    ];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }
}

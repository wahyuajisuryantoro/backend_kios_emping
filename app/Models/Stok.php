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
        'toko_id',
        'kode_barang',
        'satuan_input_id',
        'stok_awal',
        'stok_masuk',
        'stok_keluar',
        'stok_akhir',
        'satuan_akhir_id',
        'batas_minimum_stok',
        'lokasi_penyimpanan',
        'tanggal_stok',
        'status_barang'
    ];
    
    protected $casts = [
        'tanggal_stok' => 'date',
        'stok_awal' => 'decimal:2',
        'stok_masuk' => 'decimal:2',
        'stok_keluar' => 'decimal:2',
        'stok_akhir' => 'decimal:2',
        'batas_minimum_stok' => 'decimal:2'
    ];

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }

    public function satuanInput(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_input_id');
    }

    public function satuanAkhir(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_akhir_id');
    }
}

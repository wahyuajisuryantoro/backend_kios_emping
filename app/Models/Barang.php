<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barang extends Model
{
    use SoftDeletes;
    
    protected $table = 'tbl_barang';
    protected $primaryKey = 'kode_barang';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'kategori_id',
        'merk_barang',
        'deskripsi_barang',
        'berat_barang',
        'volume_barang',
        'satuan_barang',
        'gambar_barang',
        'tanggal_kadaluarsa'
    ];

    protected $casts = [
        'berat_barang' => 'decimal:2',
        'tanggal_kadaluarsa' => 'date'
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function stok(): HasOne
    {
        return $this->hasOne(Stok::class, 'kode_barang', 'kode_barang');
    }

    public function harga(): HasOne
    {
        return $this->hasOne(Harga::class, 'kode_barang', 'kode_barang');
    }
}

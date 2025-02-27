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
        'toko_id',
        'nama_barang',
        'kategori_id',
        'tipe_perhitungan',
        'satuan_dasar_id',
        'nilai_barang',
        'merk_barang',
        'deskripsi_barang',
        'gambar_barang',
        'tanggal_kadaluarsa'
    ];
    
    protected $casts = [
        'tanggal_kadaluarsa' => 'date',
        'nilai_barang' => 'decimal:2'
    ];

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function satuanDasar(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_dasar_id');
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

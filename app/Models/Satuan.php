<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Satuan extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_satuan';

    protected $fillable = [
        'tipe_satuan',
        'nama_satuan',
        'nilai_konversi',
        'simbol',
        'keterangan'
    ];

    protected $casts = [
        'nilai_konversi' => 'decimal:2'
    ];

    public function barang(): HasMany
    {
        return $this->hasMany(Barang::class, 'satuan_dasar_id');
    }

    public function stokInput(): HasMany
    {
        return $this->hasMany(Stok::class, 'satuan_input_id');
    }

    public function stokAkhir(): HasMany
    {
        return $this->hasMany(Stok::class, 'satuan_akhir_id');
    }

    public function harga(): HasMany
    {
        return $this->hasMany(Harga::class, 'satuan_jual_id');
    }

    public function transaksiDetail(): HasMany
    {
        return $this->hasMany(TransaksiDetails::class, 'satuan_transaksi_id');
    }
}

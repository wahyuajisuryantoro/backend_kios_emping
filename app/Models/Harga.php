<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Harga extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_harga';
    protected $primaryKey = 'id_harga';

    protected $fillable = [
        'kode_barang',
        'satuan_jual_id',
        'harga_beli',
        'harga_jual_eceran',
        'harga_jual_grosir',
        'min_qty_grosir',
        'min_quantity_satuan_id',
        'diskon_eceran',
        'diskon_grosir',
        'tanggal_perubahan_harga'
    ];

    protected $casts = [
        'harga_beli' => 'decimal:2',
        'harga_jual_eceran' => 'decimal:2',
        'harga_jual_grosir' => 'decimal:2',
        'diskon_eceran' => 'decimal:2',
        'diskon_grosir' => 'decimal:2',
        'min_qty_grosir' => 'integer',
        'tanggal_perubahan_harga' => 'date'
    ];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }

    public function satuanJual(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_jual_id');
    }

}

<?php

namespace App\Models;

use App\Models\Barang;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiDetails extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_transaksi_detail';

    protected $fillable = [
        'no_transaksi',
        'kode_barang',
        'quantity',
        'harga_satuan',
        'diskon',
        'subtotal'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'harga_satuan' => 'decimal:2',
        'diskon' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'no_transaksi', 'no_transaksi');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiDetails extends Model
{
    protected $table = 'tbl_transaksi_detail';
    
    protected $fillable = [
        'no_transaksi',
        'kode_barang',
        'quantity',
        'harga_satuan',
        'diskon',
        'subtotal'
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'no_transaksi', 'no_transaksi');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }
}
